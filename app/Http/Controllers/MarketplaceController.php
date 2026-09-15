<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Betslip;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $betSlips = $this->getBetslips();
        if ($request->wantsJson()) {
            return response()->json($betSlips);
        }
        return Inertia::render('Marketplace', [
            'bet_slips' => $betSlips
        ]);
    }


    private function getBetslips()
    {
        // 1. Fetch paginated betslips with relations
        $betslips = Betslip::with([
            'seller',
            'odds' => function ($query) {
                $query->limit(3); // limit to 3 odds per betslip for display
            },
            'odds.fixture',
            'odds.fixture.homeTeam',
            'odds.fixture.awayTeam',
            'odds.market'
        ])
            ->withCount('odds') // total odds count (used for total_markets)
            ->where('status', 'pending')
            ->paginate(4);

        // 2. Transform the items inside the paginator's collection
        $transformed = $betslips->getCollection()->map(function ($betslip) {
            $seller = $betslip->seller;

            return [
                'id' => $betslip->id,
                'code' => $betslip->code,
                'price' => (float) $betslip->price,
                'caption' => $betslip->caption,
                'seller' => [
                    'name' => $seller->name ?? 'Unknown',
                    'code' => $seller->code,
                    'avatar' => $seller->profile_picture_url,
                    'roi' => $this->calculateROI($seller),
                    'win_rate' => $this->calculateWinRate($seller),
                    'recent_form' => $this->getRecentForm($seller),
                ],
                'total_markets' => $betslip->odds_count, // from withCount
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
        });

        // 3. Replace the original collection with the transformed one
        $betslips->setCollection($transformed);

        // 4. Return the paginator (still a LengthAwarePaginator with links & meta)
        return $betslips;
    }

    /**
     * Get recent form for a user based on their last 5 settled betslips
     * Returns array of 'W', 'L', or 'P' (pending)
     */
    private function getRecentForm($user, $limit = 6)
    {
        // Get the last 5 betslips that are NOT pending (settled)
        $settledBetslips = $user->betslips()
            ->where('status', '!=', 'pending')
            ->where('status', '!=', 'underway')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        // Map to 'W' or 'L' based on is_winner
        $results = $settledBetslips->map(function ($betslip) {
            return $betslip->is_winner ? 'W' : 'L';
        })->toArray();

        // If we have fewer than $limit results, pad the beginning with 'P'
        while (count($results) < $limit) {
            array_unshift($results, 'P');
        }

        return $results;
    }

    private function calculateROI($user)
    {
        $totalStaked = $user->betslips()->sum('price');
        $totalWon = $user->betslips()->where('is_winner', true)->sum('price');
        return $totalStaked > 0 ? round(($totalWon / $totalStaked) * 100, 1) : 0;
    }

    private function calculateWinRate($user)
    {
        $totalBetslips = $user->betslips()->where('status', '!=', 'pending')->count();
        $wonBetslips = $user->betslips()->where('is_winner', true)->count();
        return $totalBetslips > 0 ? round(($wonBetslips / $totalBetslips) * 100) : 0;
    }
}
