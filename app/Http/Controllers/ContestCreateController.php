<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestLeg;
use App\Models\Fixture;
use App\Models\League;
use App\Models\ContestEntry;
use App\Models\ContestPick;
use App\Models\Odd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContestCreateController extends Controller
{
    private const MIN_LEGS = 5;
    private const MAX_LEGS = 50;
    private const LOOKAHEAD_DAYS = 14;
    private const FIXTURE_LIMIT = 200;

    public function create(): Response
    {
        $from = now();
        $to   = now()->addDays(self::LOOKAHEAD_DAYS);

        $leagues = League::with([
            'fixtures' => function ($q) use ($from, $to) {
                $q->whereBetween('date', [$from, $to])
                    ->where('status_short', 'NS')
                    ->orderBy('date')
                    ->limit(self::FIXTURE_LIMIT)
                    ->with([
                        'homeTeam',
                        'awayTeam',
                        'Odds' => fn ($o) => $o->select('id', 'fixture_id', 'market_id', 'value', 'odd'),
                    ]);
            },
        ])
            ->whereHas('fixtures', function ($q) use ($from, $to) {
                $q->whereBetween('date', [$from, $to])
                    ->where('status_short', 'NS');
            })
            ->get()
            ->map(fn (League $l) => [
                'id'   => $l->id,
                'name' => $l->name,
                'fixtures' => $l->fixtures->map(fn (Fixture $f) => [
                    'id'        => $f->id,
                    'home_team' => $f->homeTeam?->name ?? 'Unknown',
                    'away_team' => $f->awayTeam?->name ?? 'Unknown',
                    'kickoff'   => $f->date,
                    'markets'   => $f->Odds
                        ->groupBy('market_id')
                        ->map(fn ($odds, $marketId) => [
                            'id'      => (int) $marketId,
                            'label'   => 'Market ' . $marketId,
                            'options' => $odds->map(fn ($o) => [
                                'value' => $o->value,
                                'odd'   => (float) $o->odd,
                            ])->values()->all(),
                        ])
                        ->values()
                        ->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();

        // Attach market names in one query.
        $marketNames = \App\Models\Market::pluck('name', 'id');
        foreach ($leagues as &$league) {
            foreach ($league['fixtures'] as &$fixture) {
                foreach ($fixture['markets'] as &$market) {
                    $market['label'] = $marketNames[$market['id']] ?? 'Unknown Market';
                }
            }
        }
        unset($league, $fixture, $market);

        return Inertia::render('Contests/Create', [
            'leagues'  => $leagues,
            'min_legs' => self::MIN_LEGS,
            'max_legs' => self::MAX_LEGS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:80'],
            'description'             => ['nullable', 'string', 'max:500'],
            'entry_deadline_at'       => ['required', 'date', 'after:now'],
            'legs'                    => ['required', 'array', 'min:' . self::MIN_LEGS, 'max:' . self::MAX_LEGS],
            'legs.*.fixture_id'       => ['required', 'integer'],
            'legs.*.market_id'        => ['required', 'integer'],
            'legs.*.selection'        => ['required', 'string', 'max:50'],
        ]);

        $deadline = \Carbon\Carbon::parse($validated['entry_deadline_at']);

        // Reject duplicate (fixture_id, market_id) pairs.
        $pairs = array_map(
            fn ($l) => $l['fixture_id'] . '-' . $l['market_id'],
            $validated['legs']
        );

        if (count($pairs) !== count(array_unique($pairs))) {
            throw ValidationException::withMessages([
                'legs' => 'You cannot include the same fixture and market twice.',
            ]);
        }

        $fixtureIds = array_column($validated['legs'], 'fixture_id');
        $fixtures = Fixture::whereIn('id', $fixtureIds)->get()->keyBy('id');

        if ($fixtures->count() !== count(array_unique($fixtureIds))) {
            throw ValidationException::withMessages([
                'legs' => 'One or more fixtures no longer exist.',
            ]);
        }

        // Confirm each fixture has the requested market and the selection
        // is a valid option for it.
        foreach ($validated['legs'] as $i => $leg) {
            $fixture = $fixtures[$leg['fixture_id']];

            $validSelections = $fixture->odds()
                ->where('market_id', $leg['market_id'])
                ->pluck('value')
                ->all();

            if (empty($validSelections)) {
                throw ValidationException::withMessages([
                    "legs.{$i}.market_id" => "Market {$leg['market_id']} has no odds for this fixture.",
                ]);
            }

            if (!in_array($leg['selection'], $validSelections, true)) {
                throw ValidationException::withMessages([
                    "legs.{$i}.selection" => "'{$leg['selection']}' is not a valid selection for this leg.",
                ]);
            }
        }

        $earliest = $fixtures->min('date');
        if ($deadline->greaterThanOrEqualTo($earliest)) {
            throw ValidationException::withMessages([
                'entry_deadline_at' => 'Deadline must be before the earliest kickoff.',
            ]);
        }

        $latest = $fixtures->max('date');

        $contest = DB::transaction(function () use (
            $validated, $deadline, $earliest, $latest
        ) {
            $contest = Contest::create([
                'host_id'           => Auth::id(),
                'name'              => $validated['name'],
                'description'       => $validated['description'] ?? null,
                'visibility'        => 'private',
                'status'            => 'open',
                'entry_deadline_at' => $deadline,
                'starts_at'         => $earliest,
                'ends_at'           => $latest,
            ]);

            $legsByFixture = [];

            foreach ($validated['legs'] as $leg) {
                $contestLeg = ContestLeg::create([
                    'contest_id' => $contest->id,
                    'fixture_id' => $leg['fixture_id'],
                    'market_id'  => $leg['market_id'],
                    'status'     => 'pending',
                ]);

                $legsByFixture[] = [
                    'contest_leg_id' => $contestLeg->id,
                    'selection'      => $leg['selection'],
                ];
            }

            // Host auto-entry, accepted, with picks.
            $entry = ContestEntry::create([
                'contest_id' => $contest->id,
                'user_id'    => Auth::id(),
                'status'     => 'accepted',
                'joined_at'  => now(),
            ]);

            foreach ($legsByFixture as $leg) {
                $contestLeg = ContestLeg::find($leg['contest_leg_id']);

                $odd = Odd::where('fixture_id', $contestLeg->fixture_id)
                    ->where('market_id', $contestLeg->market_id)
                    ->where('value', $leg['selection'])
                    ->firstOrFail();

                ContestPick::create([
                    'contest_entry_id' => $entry->id,
                    'contest_leg_id'   => $contestLeg->id,
                    'selection'        => $leg['selection'],
                    'odds_at_pick'     => $odd->odd,
                    'status'           => 'pending',
                ]);
            }

            return $contest;
        });

        return redirect("/contests/{$contest->id}/manage");
    }
}