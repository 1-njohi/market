<?php

namespace App\Services;

use App\Models\Betslip;
use App\Models\User;

/**
 * Transforms a Betslip into the payload shape consumed by
 * BetSlipSummaryCard.vue. Shared between the marketplace listing, the
 * user's watchlist, and (eventually) the following feed.
 *
 * Callers must eager-load: seller, odds (with limit), odds.fixture,
 * odds.fixture.homeTeam, odds.fixture.awayTeam, odds.market, and must
 * include withCount('odds').
 */
class BetslipCardPresenter
{
    public function __construct(
        protected WatchlistService $watchlist,
    ) {
    }

    /**
     * @param  Betslip  $betslip  Fully eager-loaded betslip.
     * @param  User|null  $viewer  The current viewer, or null for guests.
     */
    public function present(Betslip $betslip, ?User $viewer): array
    {
        $seller = $betslip->seller;

        return [
            'id' => $betslip->id,
            'code' => $betslip->code,
            'price' => (float) $betslip->price,
            'caption' => $betslip->caption,

            'is_watching' => $viewer
                ? $betslip->watchers()->where('user_id', $viewer->id)->exists()
                : false,
            'can_watch' => $viewer
                ? $this->watchlist->canWatch($viewer, $betslip)
                : false,

            'seller' => [
                'name' => $seller->name ?? 'Unknown',
                'code' => $seller->code,
                'avatar' => $seller->profile_picture_url,
                'roi' => $this->calculateROI($seller),
                'win_rate' => $this->calculateWinRate($seller),
                'recent_form' => $this->getRecentForm($seller),
            ],

            'total_markets' => $betslip->odds_count,

            'legs' => $betslip->odds->take(3)->map(function ($odd) {
                return [
                    'id' => $odd->id,
                    'league' => $odd->fixture->league->name ?? 'Unknown League',
                    'kickoff_at' => $odd->fixture->timestamp ?? now()->toISOString(),
                    'home_team' => $odd->fixture->homeTeam->name ?? 'Unknown',
                    'away_team' => $odd->fixture->awayTeam->name ?? 'Unknown',
                    'market_name' => $odd->market->name ?? 'Unknown Market',
                    'selection' => $odd->pivot->selection_value ?? $odd->value,
                    'odds' => (float) ($odd->pivot->odd_value_at_time ?? $odd->odd),
                ];
            })->toArray(),
        ];
    }

    private function getRecentForm(User $user, int $limit = 6): array
    {
        $settled = $user->betslips()
            ->whereNotIn('status', ['pending', 'underway'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        $results = $settled
            ->map(fn($b) => $b->is_winner ? 'W' : 'L')
            ->toArray();

        while (count($results) < $limit) {
            array_unshift($results, 'P');
        }

        return $results;
    }

    private function calculateROI(User $user): float
    {
        $staked = $user->betslips()->sum('price');
        $won = $user->betslips()->where('is_winner', true)->sum('price');

        return $staked > 0 ? round(($won / $staked) * 100, 1) : 0;
    }

    private function calculateWinRate(User $user): int
    {
        $settled = $user->betslips()->whereNotIn('status', ['pending', 'underway'])->count();
        $won = $user->betslips()->where('is_winner', true)->count();

        return $settled > 0 ? (int) round(($won / $settled) * 100) : 0;
    }

    /**
     * Reusable eager-load clause so marketplace and watchlist stay in
     * sync about which relations the presenter needs.
     */
    public static function eagerLoad(): array
    {
        return [
            'seller',
            'odds' => fn($q) => $q->limit(3),
            'odds.fixture',
            'odds.fixture.homeTeam',
            'odds.fixture.awayTeam',
            'odds.market',
        ];
    }
}