<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestLeg;
use App\Models\Fixture;
use App\Models\League;
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
                    'id'         => $f->id,
                    'home_team'  => $f->homeTeam?->name ?? 'Unknown',
                    'away_team'  => $f->awayTeam?->name ?? 'Unknown',
                    'kickoff'    => $f->date,
                    'markets' => $f->Odds
                        ->groupBy('market_id')
                        ->map(fn ($group, $marketId) => [
                            'id'    => (int) $marketId,
                            'label' => 'Market ' . $marketId,
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
            'name'                 => ['required', 'string', 'max:80'],
            'description'          => ['nullable', 'string', 'max:500'],
            'entry_deadline_at'    => ['required', 'date', 'after:now'],
            'legs' => ['required', 'array', 'min:' . self::MIN_LEGS, 'max:' . self::MAX_LEGS],
            'legs.*.fixture_id'    => ['required', 'integer'],
            'legs.*.market_id'     => ['required', 'integer'],
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

        // Load the fixtures we're actually using.
        $fixtureIds = array_column($validated['legs'], 'fixture_id');
        $fixtures = Fixture::whereIn('id', $fixtureIds)
            ->get()
            ->keyBy('id');

        if ($fixtures->count() !== count(array_unique($fixtureIds))) {
            throw ValidationException::withMessages([
                'legs' => 'One or more fixtures no longer exist.',
            ]);
        }

        // Validate every market belongs to the fixture.
        foreach ($validated['legs'] as $leg) {
            $fixture = $fixtures[$leg['fixture_id']];
            $hasOdd = $fixture->odds()
                ->where('market_id', $leg['market_id'])
                ->exists();

            if (!$hasOdd) {
                throw ValidationException::withMessages([
                    'legs' => "Market {$leg['market_id']} has no odds for fixture {$leg['fixture_id']}.",
                ]);
            }
        }

        // Deadline must precede the earliest kickoff.
        $earliest = $fixtures->min('date');
        if ($deadline->greaterThanOrEqualTo($earliest)) {
            throw ValidationException::withMessages([
                'entry_deadline_at' => 'Deadline must be before the earliest kickoff.',
            ]);
        }

        // Starts at the earliest kickoff, ends at the latest.
        $latest = $fixtures->max('date');

        $contest = DB::transaction(function () use (
            $validated, $deadline, $fixtures, $earliest, $latest
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

            foreach ($validated['legs'] as $leg) {
                ContestLeg::create([
                    'contest_id' => $contest->id,
                    'fixture_id' => $leg['fixture_id'],
                    'market_id'  => $leg['market_id'],
                    'status'     => 'pending',
                ]);
            }

            return $contest;
        });

        return redirect("/contests/{$contest->id}/manage");
    }
}