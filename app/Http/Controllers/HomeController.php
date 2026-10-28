<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Betslip;
use App\Services\BetslipCardPresenter;
use App\Services\FixtureService;
use App\Services\AnalystArchiveService;
use App\Http\Resources\AnalystPickResource;
use App\Services\LeaderboardService;
use Inertia\Inertia;
use App\Models\Contest;
use App\Models\ContestEntry;

class HomeController extends Controller
{
    public function __construct(
        protected LeaderboardService $leaderboardService,
        protected FixtureService $fixtureService,
    ) {
    }

    public function index()
    {

        $analystArchive = app(AnalystArchiveService::class);

        $currentPicks = Betslip::publishedAnalystSlips()
            ->whereIn('status', ['pending', 'underway'])
            ->where('analyst_published_at', '>=', now()->subDays(7))
            ->with([
                'analyst',
                'odds.market',
                'odds.fixture.homeTeam',
                'odds.fixture.awayTeam',
                'odds.fixture.league',
            ])
            ->orderByDesc('analyst_published_at')
            ->limit(3)
            ->get();

        $analyst = User::where('is_analyst', true)
            ->whereNotNull('analyst_slug')
            ->orderBy('id')
            ->first();

        return Inertia::render('Welcome', [
            'fixtures' => $this->fixtureService->leaguesWithUpcomingFixtures(),
            'bet_slips' => $this->getBetslips(),
            'leaders' => $this->leaderboardService->getTopSellers(10),
            'public_contests' => $this->getPublicContests(),
            'open_matchdays' => $this->getOpenMatchdays(),
            'open_jackpots' => $this->getOpenJackpots(),
            'analyst_picks' => [
                'current' => AnalystPickResource::collection($currentPicks)->resolve(),
                'last_settled' => $analystArchive->lastSettledBatch(),
                'analyst' => $analyst ? [
                    'name' => $analyst->byline(),
                    'slug' => $analyst->analyst_slug,
                    'avatar_url' => $analyst->profile_picture_url,
                ] : null,
            ],

        ]);
    }
    /**
     * Public contests for the Welcome page discovery row.
     *
     * "Public" is a compound fact: visibility='public' AND auto_entry=true
     * AND still open AND deadline in the future. The controller that
     * writes visibility enforces the first two together, but we assert
     * both here so a future change to that rule can't accidentally
     * surface an approval-gated contest as instantly joinable.
     *
     * Capped at 6. Ordered by deadline so the most urgent comes first.
     * `is_joined` is computed with a single follow-up query rather than
     * a per-contest exists() call.
     */
    private function getPublicContests(): array
    {
        $contests = Contest::query()
            ->with([
                'host:id,name,code,prediction_points',
                'legs' => function ($q) {
                    // Eager load the fixtures, teams, and markets, no limit here.
                    $q->with([
                        'fixture.homeTeam',
                        'fixture.awayTeam',
                        'market',
                    ])->orderBy('id');
                },
            ])
            ->withCount([
                'legs',
                'entries as accepted_entries_count' => fn($q) => $q->where('status', 'accepted'),
                'entries as total_entries_count',
            ])
            ->addSelect([
                'total_picks_count' => \App\Models\ContestPick::query()
                    ->selectRaw('count(*)')
                    ->whereIn('contest_entry_id', function ($query) {
                        $query->select('id')
                            ->from('contest_entries')
                            ->whereColumn('contest_id', 'contests.id');
                    })
            ])
            ->where('visibility', 'public')
            ->where('auto_entry', true)
            ->where('is_matchday', false)
            ->where('status', 'open')
            ->where('entry_deadline_at', '>', now())
            ->orderBy('entry_deadline_at')
            ->limit(6)
            ->get();

        if ($contests->isEmpty()) {
            return [];
        }

        $viewer = auth()->user();
        $joinedIds = $viewer
            ? ContestEntry::where('user_id', $viewer->id)
                ->whereIn('contest_id', $contests->pluck('id'))
                ->where('status', 'accepted')
                ->pluck('contest_id')
                ->all()
            : [];

        return $contests->map(function (Contest $c) use ($joinedIds) {
            $hostRank = $c->host->prediction_points > 0
                ? User::where('prediction_points', '>', $c->host->prediction_points)->count() + 1
                : null;

            // Take only the first 3 legs for the preview, but from the fully
            // loaded collection.
            $fixturePreview = $c->legs->take(3)->map(function ($leg) {
                return [
                    'id' => $leg->fixture_id,
                    'home_team' => $leg->fixture?->homeTeam?->name ?? 'TBD',
                    'away_team' => $leg->fixture?->awayTeam?->name ?? 'TBD',
                    'kickoff' => $leg->fixture?->date?->toIso8601String(),
                    'market_name' => $leg->market?->name ?? 'Unknown Market', // <--- Added market name
                ];
            })->values()->all();

            return [
                'uuid' => $c->uuid,
                'name' => $c->name,
                'description' => $c->description,
                'host_name' => $c->host->name,
                'host_code' => $c->host->code,
                'host_reputation' => (int) $c->host->prediction_points,
                'host_rank' => $hostRank,
                'legs_count' => (int) $c->legs_count,
                'fixture_preview' => $fixturePreview,
                'entries_count' => (int) $c->accepted_entries_count,
                'pending_count' => (int) ($c->total_entries_count - $c->accepted_entries_count),
                'total_picks' => (int) $c->total_picks_count,
                'auto_entry' => (bool) $c->auto_entry,
                'entry_deadline_at' => $c->entry_deadline_at->toIso8601String(),
                'ends_at' => $c->ends_at->toIso8601String(),
                'is_joined' => in_array($c->id, $joinedIds, true),
                'prize_pool' => 0,
            ];
        })->values()->all();
    }

    /**
     * Featured betslips for the Welcome hero row.
     */
    private function getBetslips()
    {
        $user = auth()->user();
        $presenter = app(BetslipCardPresenter::class);

        $betslips = Betslip::with(BetslipCardPresenter::eagerLoad())
            ->withCount('odds')
            ->where('status', 'pending')
            ->take(4)
            ->get();

        return $betslips->map(
            fn($betslip) => $presenter->present($betslip, $user)
        );
    }

    /**
     * Open matchday contests for the Welcome page.
     *
     * A matchday is "open" when is_matchday=true, status='open', and the
     * lock time hasn't passed. Capped at 3 — matchdays are a secondary
     * section on the Welcome page and the most recent week is enough.
     *
     * Ordered by locks_at ascending so the most urgent matchday is first.
     * `has_picks` is computed with a single follow-up query against the
     * viewer's own picks so the CTA can say "Continue picks" vs "Make picks".
     */
    private function getOpenMatchdays(): array
    {
        $matchdays = Contest::query()
            ->with([
                'legs' => function ($q) {
                    $q->with(['fixture.homeTeam', 'fixture.awayTeam', 'market'])
                        ->orderBy('id');
                },
            ])
            ->withCount([
                'legs',
                'entries as accepted_entries_count' => fn($q) => $q->where('status', 'accepted'),
            ])
            ->where('is_matchday', true)
            ->where('status', 'open')
            ->where('locks_at', '>', now())
            ->orderBy('locks_at')
            ->limit(3)
            ->get();

        if ($matchdays->isEmpty()) {
            return [];
        }

        $viewer = auth()->user();

        // One query for which contests the viewer is entered in.
        $joinedIds = $viewer
            ? ContestEntry::where('user_id', $viewer->id)
                ->whereIn('contest_id', $matchdays->pluck('id'))
                ->where('status', 'accepted')
                ->pluck('contest_id')
                ->all()
            : [];

        // One query for which contests the viewer has already submitted picks in.
        $contestIdsWithPicks = $viewer
            ? \App\Models\ContestPick::query()
                ->whereIn('contest_entry_id', function ($q) use ($viewer, $matchdays) {
                    $q->select('id')
                        ->from('contest_entries')
                        ->where('user_id', $viewer->id)
                        ->whereIn('contest_id', $matchdays->pluck('id'));
                })
                ->pluck('contest_entry_id')
                ->all()
            : [];

        // Map entry_id -> contest_id so has_picks is answerable per contest.
        $entryToContest = \App\Models\ContestEntry::whereIn('id', $contestIdsWithPicks)
            ->pluck('contest_id', 'id')
            ->all();

        $contestsWithPicks = array_unique(array_values($entryToContest));

        return $matchdays->map(function (Contest $c) use ($joinedIds, $contestsWithPicks) {
            return [
                'uuid' => $c->uuid,
                'name' => $c->name,
                'league_code' => $c->league_code,
                'season' => $c->season,
                'matchday_number' => $c->matchday_number,
                'legs_count' => (int) $c->legs_count,
                'entries_count' => (int) $c->accepted_entries_count,
                'locks_at' => $c->locks_at?->toIso8601String(),
                'is_joined' => in_array($c->id, $joinedIds, true),
                'has_picks' => in_array($c->id, $contestsWithPicks, true),
                'fixture_preview' => $c->legs->take(3)->map(fn($leg) => [
                    'home_team' => $leg->fixture?->homeTeam?->name ?? 'TBD',
                    'away_team' => $leg->fixture?->awayTeam?->name ?? 'TBD',
                    'kickoff' => $leg->fixture?->date?->toIso8601String(),
                    'market_name' => $leg->market?->name ?? 'Unknown Market',
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * Open jackpots for the Welcome page.
     *
     * Jackpots are contests with type='jackpot'. They're "open" while
     * status='open' and the entry deadline is in the future. Capped at 3
     * so the section stays compact.
     *
     * `prize_pool` is read from contests.prize_pool if the column exists;
     * if not, it falls back to 0. Guard with property_exists so a schema
     * change doesn't silently fail.
     */
    private function getOpenJackpots(): array
    {
        $jackpots = Contest::query()
            ->with([
                'legs' => function ($q) {
                    $q->with(['fixture.homeTeam', 'fixture.awayTeam', 'market'])
                        ->orderBy('id');
                },
            ])
            ->withCount([
                'legs',
                'entries as accepted_entries_count' => fn($q) => $q->where('status', 'accepted'),
                'entries as total_entries_count',
            ])
            ->where('type', 'jackpot')
            ->where('status', 'open')
            ->where('entry_deadline_at', '>', now())
            ->orderBy('entry_deadline_at')
            ->limit(3)
            ->get();

        if ($jackpots->isEmpty()) {
            return [];
        }

        $viewer = auth()->user();
        $joinedIds = $viewer
            ? ContestEntry::where('user_id', $viewer->id)
                ->whereIn('contest_id', $jackpots->pluck('id'))
                ->where('status', 'accepted')
                ->pluck('contest_id')
                ->all()
            : [];

        return $jackpots->map(function (Contest $c) use ($joinedIds) {
            return [
                'uuid' => $c->uuid,
                'name' => $c->name,
                'description' => $c->description,
                'legs_count' => (int) $c->legs_count,
                'entries_count' => (int) $c->accepted_entries_count,
                'pending_count' => (int) ($c->total_entries_count - $c->accepted_entries_count),
                'entry_deadline_at' => $c->entry_deadline_at->toIso8601String(),
                'ends_at' => $c->ends_at?->toIso8601String(),
                'is_joined' => in_array($c->id, $joinedIds, true),
                'prize_pool' => property_exists($c, 'prize_pool') ? (int) $c->prize_pool : 0,
                'fixture_preview' => $c->legs->take(3)->map(fn($leg) => [
                    'home_team' => $leg->fixture?->homeTeam?->name ?? 'TBD',
                    'away_team' => $leg->fixture?->awayTeam?->name ?? 'TBD',
                    'kickoff' => $leg->fixture?->date?->toIso8601String(),
                    'market_name' => $leg->market?->name ?? 'Unknown Market',
                ])->values()->all(),
            ];
        })->values()->all();
    }
}