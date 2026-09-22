<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Betslip;
use App\Services\WatchlistService;

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
        $user = auth()->user();
        $presenter = app(\App\Services\BetslipCardPresenter::class);

        $betslips = Betslip::with(\App\Services\BetslipCardPresenter::eagerLoad())
            ->withCount('odds')
            ->where('status', 'pending')
            ->paginate(4);

        $betslips->setCollection(
            $betslips->getCollection()->map(
                fn($b) => $presenter->present($b, $user)
            )
        );

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
}
