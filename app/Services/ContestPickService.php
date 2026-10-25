<?php

namespace App\Services;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Odd;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContestPickService
{
    /**
     * Submit (or replace) a user's picks for a contest.
     *
     * Replaces the entire pick set atomically — no partial updates.
     * Validates:
     *   - no duplicate leg_ids
     *   - every leg belongs to this contest
     *   - every selection is a valid option for that leg
     *
     * @param  array<int, array{leg_id: int|string, selection: string}>  $picks
     *
     * @throws ValidationException
     */
    public function submit(Contest $contest, ContestEntry $entry, array $picks): void
    {
        if (empty($picks)) {
            throw ValidationException::withMessages([
                'picks' => 'At least one pick is required.',
            ]);
        }

        $legIds = array_map(fn($p) => (int) $p['leg_id'], $picks);

        if (count($legIds) !== count(array_unique($legIds))) {
            throw ValidationException::withMessages([
                'picks' => 'You cannot submit two picks for the same leg.',
            ]);
        }

        // Confirm every submitted leg belongs to this contest.
        $validLegs = ContestLeg::where('contest_id', $contest->id)
            ->whereIn('id', $legIds)
            ->get()
            ->keyBy('id');

        $foreign = array_diff($legIds, $validLegs->keys()->all());
        if (!empty($foreign)) {
            throw ValidationException::withMessages([
                'picks' => 'One or more picks referenced a leg from another contest.',
            ]);
        }

        // ── Jackpot guard: reject attempts to change an already-revealed pick ──
        // For jackpot contests, once a pick is created its `revealed_at` is set.
        // The pick is permanently locked: the user has seen the crowd and
        // cannot revise their answer. This is enforced before the delete+create
        // below, which would otherwise bypass the observer's updating() guard.
        if ($contest->type === 'jackpot') {
            $revealedPickLegIds = ContestPick::where('contest_entry_id', $entry->id)
                ->whereIn('contest_leg_id', $legIds)
                ->whereNotNull('revealed_at')
                ->pluck('contest_leg_id')
                ->all();

            if (!empty($revealedPickLegIds)) {
                throw ValidationException::withMessages([
                    'picks' => 'You have already picked this leg. Jackpot picks are locked once revealed.',
                ]);
            }
        }

        // Validate each selection against the leg's available options.
        $toUpsert = [];
        foreach ($picks as $pick) {
            $legId = (int) $pick['leg_id'];
            $selection = (string) $pick['selection'];
            $confidence = (int) $pick['confidence'];
            $leg = $validLegs[$legId];

            $options = $this->optionsFor($leg);

            if (!collect($options)->contains('value', $selection)) {
                throw ValidationException::withMessages([
                    'picks' => "Invalid selection '{$selection}' for leg {$legId}.",
                ]);
            }

            $oddValue = collect($options)->firstWhere('value', $selection)['odd'];

            $toUpsert[] = [
                'contest_entry_id' => $entry->id,
                'contest_leg_id' => $legId,
                'selection' => $selection,
                'odds_at_pick' => $oddValue,
                'status' => 'pending',
                'points' => null,
            ];

            // Record the prediction
            app(PredictionRecorderService::class)->record(
                userId: $entry->user_id,
                fixtureId: $leg->fixture_id,
                marketId: $leg->market_id,
                selection: $selection,
                odds: (float) $oddValue,
                confidence: $confidence,
                sourceType: $contest->type, // 'jackpot' or 'matchday'
                sourceId: $contest->id
            );
        }

        try {
            DB::transaction(function () use ($entry, $toUpsert, $contest) {
                // For non-jackpot contests, delete-and-recreate is fine — users
                // can revise picks up to the deadline.
                //
                // For jackpots, the guard above ensures we never reach this point
                // with a revealed pick. Any new picks created here will have
                // revealed_at = now() via the observer, which is correct.
                if ($contest->type !== 'jackpot') {
                    ContestPick::where('contest_entry_id', $entry->id)->delete();
                }

                foreach ($toUpsert as $row) {
                    ContestPick::create($row);
                }
            });
        } catch (\RuntimeException $e) {
            // Observer-level guard (defense in depth). Surface as a validation
            // error rather than a 500.
            throw ValidationException::withMessages([
                'picks' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Return the available selections for a leg as [['value' => ..., 'odd' => ...], ...].
     *
     * @return array<int, array{value: string, odd: float}>
     */
    public function optionsFor(ContestLeg $leg): array
    {
        return Odd::where('fixture_id', $leg->fixture_id)
            ->where('market_id', $leg->market_id)
            ->orderBy('id')
            ->get(['value', 'odd'])
            ->map(fn($o) => [
                'value' => $o->value,
                'odd' => (float) $o->odd,
            ])
            ->values()
            ->all();
    }
}