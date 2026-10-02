<?php

namespace App\Services;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use Illuminate\Support\Facades\DB;
use App\Notifications\ContestSettledNotification;
use RuntimeException;

class ContestScoringService
{
    /**
     * Score a contest: rank every entry by correct picks, then units won.
     *
     * Idempotent — a settled contest is a no-op.
     *
     * @throws RuntimeException  if any leg is still pending.
     */
    public function score(Contest $contest): void
    {
        if ($contest->status === 'settled') {
            return;
        }

        $this->assertAllLegsResolved($contest);

        DB::transaction(function () use ($contest) {
            // Resolve each leg's winning selection. Void legs contribute
            // nothing — they neither count as a correct pick nor carry units.
            $legs = ContestLeg::where('contest_id', $contest->id)
                ->get()
                ->keyBy('id');

            $entries = ContestEntry::where('contest_id', $contest->id)
                ->where('status', 'accepted')
                ->with('picks')
                ->get();

            foreach ($entries as $entry) {
                [$correct, $units] = $this->scoreEntry($entry, $legs);

                $entry->update([
                    'score_correct' => $correct,
                    'score_units'   => $units,
                    'settled_at'    => now(),
                ]);
            }

            // Rank: correct desc, units desc, joined_at asc (earliest join
            // wins the rare full tie).
            $ranked = ContestEntry::where('contest_id', $contest->id)
            ->where('status', 'accepted')
            ->orderByDesc('score_correct')
            ->orderByDesc('score_units')
            ->orderBy('joined_at')
            ->with('user')
            ->get();

            $totalParticipants = $ranked->count();

            $rank = 1;
            foreach ($ranked as $entry) {
            $entry->update(['rank_final' => $rank]);

            $entry->user->notify(new ContestSettledNotification(
                contest:           $contest,
                rank:              $rank,
                correct:           (int) $entry->score_correct,
                units:             (float) $entry->score_units,
                totalParticipants: $totalParticipants,
            ));

            $rank++;
            }

            $contest->update([
                'status'     => 'settled',
                'settled_at' => now(),
            ]);
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ContestLeg>  $legs
     * @return array{0: int, 1: string}  [correct count, units as a 2dp string]
     */
    private function scoreEntry(ContestEntry $entry, $legs): array
    {
        $correct = 0;
        $units   = 0.0;

        foreach ($entry->picks as $pick) {
            $leg = $legs->get($pick->contest_leg_id);
            if (!$leg) {
                continue;
            }

            // Void leg: exit early, mark the pick void, contribute nothing.
            if ($leg->status === 'void') {
                $pick->update(['status' => 'void', 'points' => 0]);
                continue;
            }

            $isCorrect = $leg->result_selection !== null
                && $pick->selection === $leg->result_selection;

            if ($isCorrect) {
                $correct++;
                $points = (float) $pick->odds_at_pick;
                $units += $points;
                $pick->update(['status' => 'correct', 'points' => $points]);
            } else {
                $pick->update(['status' => 'incorrect', 'points' => 0]);
            }
        }

        return [$correct, number_format($units, 2, '.', '')];
    }

    private function assertAllLegsResolved(Contest $contest): void
    {
        $pending = ContestLeg::where('contest_id', $contest->id)
            ->where('status', 'pending')
            ->count();

        if ($pending > 0) {
            throw new RuntimeException(
                "Contest {$contest->uuid} has {$pending} unresolved leg(s); cannot score yet."
            );
        }
    }
}