<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Services\ContestPickService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContestPickController extends Controller
{
    public function __construct(
        protected ContestPickService $picks,
    ) {
    }

    public function show(string $uuid): Response
    {
        $contest = Contest::with([
            'host:id,name,code',
            'legs.fixture.homeTeam',
            'legs.fixture.awayTeam',
            'legs.fixture.league',
            'legs.market',
        ])->where('uuid', $uuid)->firstOrFail();

        $user = Auth::user();

        // --- Eligibility check ---
        $relevantSubLeagues = collect();
        $isEligible = true;

        if ($contest->is_matchday) {
            $relevantSubLeagues = \App\Models\SubLeague::query()
                ->where('season', $contest->season)
                ->whereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->get()
                ->filter(fn($sub) => $sub->coversLeague((string) $contest->league_code))
                ->values();

            // Include owner reputation points
            $relevantSubLeagues->load('owner:id,prediction_points');
            foreach ($relevantSubLeagues as $sub) {
                $sub->owner_prediction_points = $sub->owner?->prediction_points ?? 0;
                $sub->owner_global_rank = $sub->owner && $sub->owner->prediction_points > 0
                    ? \App\Models\User::where('prediction_points', '>', $sub->owner->prediction_points)->count() + 1
                    : null;
            }

            $isEligible = $relevantSubLeagues->isNotEmpty();
        }

        // --- Entry resolution (graceful fallback) ---
        $entry = ContestEntry::where('contest_id', $contest->id)
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->first();

        if (!$entry && !($contest->is_matchday && !$isEligible)) {
            abort(403);
        }

        $existing = $entry
            ? ContestPick::where('contest_entry_id', $entry->id)->get()->keyBy('contest_leg_id')
            : collect();

        return Inertia::render('Contests/Picks', [
            'contest' => [
                'uuid' => $contest->uuid,
                'name' => $contest->name,
                'status' => $contest->status,
                'season' => $contest->season,
                'is_matchday' => $contest->is_matchday,
                'league_code' => $contest->league_code,
                'matchday_number' => $contest->matchday_number,
                'locks_at' => $contest->locks_at?->toISOString(),
                'entry_deadline_at' => $contest->entry_deadline_at->toISOString(),
                'is_locked' => $contest->is_matchday
                    ? ($contest->locks_at !== null && now()->gte($contest->locks_at)) || !$contest->isOpen()
                    : !$contest->isOpen(),
                'is_eligible' => $isEligible,
                'legs' => $contest->legs->map(function (ContestLeg $leg) {
                    return [
                        'id' => $leg->id,
                        'fixture' => [
                            'id' => $leg->fixture?->id,
                            'home_team' => $leg->fixture?->homeTeam?->name ?? 'Unknown',
                            'away_team' => $leg->fixture?->awayTeam?->name ?? 'Unknown',
                            'kickoff' => $leg->fixture?->date ? \Carbon\Carbon::parse($leg->fixture->date)->toISOString() : null,
                            'status' => $leg->fixture?->status_short ?? 'NS', // <--- Added
                        ],
                        'market' => $leg->market?->name ?? 'Unknown',
                        'options' => $this->picks->optionsFor($leg),
                        'status' => $leg->status,
                        'result' => $leg->result_selections ? implode(', ', $leg->result_selections) : null,
                    ];
                })->values()->all(),
            ],
            'picks' => $existing->map(fn(ContestPick $p) => [
                'leg_id' => $p->contest_leg_id,
                'selection' => $p->selection,
            ])->values()->all(),
            'sub_leagues' => $relevantSubLeagues->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'owner_prediction_points' => $s->owner_prediction_points,
                'owner_global_rank' => $s->owner_global_rank,
            ]),
            'league_labels' => config('marketplace.matchday.league_labels', []),
        ]);
    }

    public function store(Request $request, string $uuid): RedirectResponse
    {
        $contest = Contest::where('uuid', $uuid)->firstOrFail();
        $entry = $this->resolveEntry($contest, Auth::user());

        if (
            $contest->is_matchday
            && $contest->locks_at !== null
            && now()->gte($contest->locks_at)
        ) {
            throw ValidationException::withMessages([
                'picks' => "Picks for this matchday closed at "
                    . $contest->locks_at->toDateTimeString() . ".",
            ]);
        }

        if (!$contest->isOpen()) {
            throw ValidationException::withMessages([
                'picks' => 'This contest is no longer accepting picks.',
            ]);
        }

        $validated = $request->validate([
            'picks' => ['required', 'array'],
            'picks.*.leg_id' => ['required', 'integer'],
            'picks.*.selection' => ['required', 'string', 'max:50'],
            'picks.*.confidence' => ['required', 'integer', 'min:1', 'max:5'], // Ensure this is here
        ]);

        try {
            $this->picks->submit($contest, $entry, $validated['picks']);
        } catch (\App\Exceptions\MatchdayLockedException $e) {
            throw ValidationException::withMessages([
                'picks' => "Picks for this matchday closed at "
                    . $e->closedAt->toDateTimeString() . ".",
            ]);
        }

        return redirect("/contests/{$contest->uuid}/picks");
    }

    private function resolveEntry(Contest $contest, $user): ContestEntry
    {
        if (!$user) {
            abort(403);
        }

        $entry = ContestEntry::where('contest_id', $contest->id)
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->first();

        if (!$entry) {
            abort(403);
        }

        return $entry;
    }
}