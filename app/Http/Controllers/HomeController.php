<?php

namespace App\Http\Controllers;

use App\Models\Betslip;
use App\Services\BetslipCardPresenter;
use App\Services\FixtureService;
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
        return Inertia::render('Welcome', [
            'fixtures' => $this->fixtureService->leaguesWithUpcomingFixtures(),
            'bet_slips' => $this->getBetslips(),
            'leaders' => $this->leaderboardService->getTopSellers(10),
            'public_contests' => $this->getPublicContests(),
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
            ->with('host:id,name,code')
            ->withCount([
                'legs',
                'entries as accepted_entries_count' => fn($q) => $q->where('status', 'accepted'),
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

        return $contests->map(fn(Contest $c) => [
            'uuid' => $c->uuid,
            'name' => $c->name,
            'description' => $c->description,
            'host_name' => $c->host->name,
            'host_code' => $c->host->code,
            'legs_count' => (int) $c->legs_count,
            'entries_count' => (int) $c->accepted_entries_count,
            'entry_deadline_at' => $c->entry_deadline_at->toIso8601String(),
            'ends_at' => $c->ends_at->toIso8601String(),
            'is_joined' => in_array($c->id, $joinedIds, true),
        ])->values()->all();
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
}