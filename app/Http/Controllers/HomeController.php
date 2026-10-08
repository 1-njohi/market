<?php

namespace App\Http\Controllers;

use App\Models\Betslip;
use App\Services\BetslipCardPresenter;
use App\Services\FixtureService;
use App\Services\LeaderboardService;
use Inertia\Inertia;

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
            'fixtures'  => $this->fixtureService->leaguesWithUpcomingFixtures(),
            'bet_slips' => $this->getBetslips(),
            'leaders'   => $this->leaderboardService->getTopSellers(10),
        ]);
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
            fn ($betslip) => $presenter->present($betslip, $user)
        );
    }
}