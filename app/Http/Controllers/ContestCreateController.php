<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestLeg;
use App\Models\Fixture;
use App\Models\League;
use App\Models\ContestEntry;
use App\Models\ContestPick;
use App\Models\Market;
use App\Models\Odd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContestCreateController extends Controller
{
    private const MIN_LEGS       = 5;
    private const MAX_LEGS       = 50;
    private const LOOKAHEAD_DAYS = 14;
    private const FIXTURE_LIMIT  = 200;

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

        $marketNames = Market::pluck('name', 'id');
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

    /**
     * Step 1 → 2. Validate the legs payload, stash it in the session as a
     * flat array, bounce to the confirm page. No persistence.
     */
    public function draft(Request $request): RedirectResponse
    {
        $legs = $this->validateLegs($request->input('legs', []));

        session(['contest_draft' => $legs]);

        return Redirect::route('contests.confirm');
    }

    /**
     * Step 2 UI. Reads the draft from session, hydrates the legs for
     * display, and renders the review page.
     */
    public function confirm(): Response|RedirectResponse
    {
        $draft = session('contest_draft');

        if (!$draft) {
            return Redirect::route('contests.create')
                ->with('warn', 'Your contest draft expired. Pick your legs again.');
        }

        return Inertia::render('Contests/Confirm', [
            'legs'             => $this->hydrateLegs($draft),
            'min_legs'         => self::MIN_LEGS,
            'max_legs'         => self::MAX_LEGS,
            'earliest_kickoff' => $this->earliestKickoff($draft),
        ]);
    }

    /**
     * Step 2 submit. Reads legs from the session, re-validates them
     * (fixtures can vanish between draft and publish), validates the
     * config payload, then persists contest + legs + host entry + picks
     * in a single transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        $draftLegs = session('contest_draft');

        if (!$draftLegs) {
            return Redirect::route('contests.create')
                ->with('warn', 'Your contest draft expired. Pick your legs again.');
        }

        // Re-validate the draft legs. Guards against fixtures that have
        // been removed, or odds that have disappeared, while the draft
        // sat in the session.
        $legs = $this->validateLegs($draftLegs);

        // Config-only validation. Legs are not part of this request body.
        $config = $request->validate([
            'name'              => ['required', 'string', 'max:80'],
            'description'       => ['nullable', 'string', 'max:500'],
            'entry_deadline_at' => ['required', 'date', 'after:now'],
        ]);

        $deadline = \Carbon\Carbon::parse($config['entry_deadline_at']);

        $fixtures = Fixture::whereIn('id', array_column($legs, 'fixture_id'))
            ->get()
            ->keyBy('id');

        $earliest = $fixtures->min('date');

        if ($deadline->greaterThanOrEqualTo($earliest)) {
            throw ValidationException::withMessages([
                'entry_deadline_at' => 'Deadline must be before the earliest kickoff.',
            ]);
        }

        $latest = $fixtures->max('date');

        $contest = DB::transaction(function () use ($config, $legs, $deadline, $earliest, $latest) {
            $contest = Contest::create([
                'host_id'           => Auth::id(),
                'name'              => $config['name'],
                'description'       => $config['description'] ?? null,
                'visibility'        => 'private',
                'status'            => 'open',
                'entry_deadline_at' => $deadline,
                'starts_at'         => $earliest,
                'ends_at'           => $latest,
            ]);

            $entry = ContestEntry::create([
                'contest_id' => $contest->id,
                'user_id'    => Auth::id(),
                'status'     => 'accepted',
                'joined_at'  => now(),
            ]);

            foreach ($legs as $leg) {
                $contestLeg = ContestLeg::create([
                    'contest_id' => $contest->id,
                    'fixture_id' => $leg['fixture_id'],
                    'market_id'  => $leg['market_id'],
                    'status'     => 'pending',
                ]);

                $odd = Odd::where('fixture_id', $leg['fixture_id'])
                    ->where('market_id', $leg['market_id'])
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

        session()->forget('contest_draft');

        return Redirect::route('contests.created', ['contest' => $contest->id])
        ->with('success', "Contest “{$contest->name}” is live.");
    }

    /**
     * Terminal state after publish. Shows the invite link and share options.
     * Host-only — anyone else gets a 403.
     */
    public function created(Contest $contest): Response
    {
        abort_unless((int) $contest->host_id === (int) Auth::id(), 403);

        return Inertia::render('Contests/Created', [
            'contest' => [
                'id'                => $contest->id,
                'uuid'              => $contest->uuid,
                'name'              => $contest->name,
                'description'       => $contest->description,
                'status'            => $contest->status,
                'legs_count'        => $contest->legs()->count(),
                'entry_deadline_at' => $contest->entry_deadline_at->toIso8601String(),
            ],
        ]);
    }
    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Validate a raw legs array (from request body or session), or throw
     * a ValidationException. Returns the validated flat legs array.
     *
     * @param  mixed  $rawLegs
     * @return array<int, array{fixture_id:int, market_id:int, selection:string}>
     */
    private function validateLegs($rawLegs): array
    {
        $validated = validator(
            ['legs' => $rawLegs],
            [
                'legs'              => ['required', 'array', 'min:' . self::MIN_LEGS, 'max:' . self::MAX_LEGS],
                'legs.*.fixture_id' => ['required', 'integer'],
                'legs.*.market_id'  => ['required', 'integer'],
                'legs.*.selection'  => ['required', 'string', 'max:50'],
            ]
        )->validate();

        $legs = $validated['legs'];

        // No duplicate (fixture, market) pairs.
        $pairs = array_map(
            fn ($l) => $l['fixture_id'] . '-' . $l['market_id'],
            $legs,
        );

        if (count($pairs) !== count(array_unique($pairs))) {
            throw ValidationException::withMessages([
                'legs' => 'You cannot include the same fixture and market twice.',
            ]);
        }

        $fixtureIds = array_column($legs, 'fixture_id');
        $fixtures   = Fixture::whereIn('id', $fixtureIds)->get()->keyBy('id');

        if ($fixtures->count() !== count(array_unique($fixtureIds))) {
            throw ValidationException::withMessages([
                'legs' => 'One or more fixtures no longer exist.',
            ]);
        }

        foreach ($legs as $i => $leg) {
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

        return $legs;
    }

    /**
     * Enrich raw draft legs with team / league / market names and the
     * odds at pick time, for display on the confirm page.
     *
     * @param  array<int, array{fixture_id:int, market_id:int, selection:string}>  $legs
     * @return array<int, array<string, mixed>>
     */
    private function hydrateLegs(array $legs): array
    {
        $fixtures = Fixture::with(['homeTeam', 'awayTeam', 'league'])
            ->whereIn('id', array_column($legs, 'fixture_id'))
            ->get()
            ->keyBy('id');

        $marketNames = Market::pluck('name', 'id');

        return collect($legs)->map(function ($leg) use ($fixtures, $marketNames) {
            $fixture = $fixtures[$leg['fixture_id']] ?? null;

            $odd = $fixture
                ? Odd::where('fixture_id', $fixture->id)
                    ->where('market_id', $leg['market_id'])
                    ->where('value', $leg['selection'])
                    ->first()
                : null;

            return [
                'fixture_id'   => $leg['fixture_id'],
                'market_id'    => $leg['market_id'],
                'selection'    => $leg['selection'],
                'home_team'    => $fixture?->homeTeam?->name ?? 'Unknown',
                'away_team'    => $fixture?->awayTeam?->name ?? 'Unknown',
                'league'       => $fixture?->league?->name,
                'kickoff'      => $fixture?->date,
                'market_label' => $marketNames[$leg['market_id']] ?? 'Unknown Market',
                'odds'         => $odd ? (float) $odd->odd : null,
            ];
        })->values()->all();
    }

    /**
     * The earliest kickoff across the draft's fixtures, ISO-8601, so the
     * confirm page can set a `max` on the deadline input.
     *
     * @param  array<int, array{fixture_id:int, market_id:int, selection:string}>  $legs
     */
    private function earliestKickoff(array $legs): ?string
    {
        $earliest = Fixture::whereIn('id', array_column($legs, 'fixture_id'))->min('date');

        return $earliest
            ? \Carbon\Carbon::parse($earliest)->toIso8601String()
            : null;
    }
}