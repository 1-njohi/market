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
        $filters = $request->validate([
            // Buyer's budget cap. Null / absent means no filter.
            'max_break_even' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $betSlips = $this->getBetslips(
            isset($filters['max_break_even']) ? (float) $filters['max_break_even'] : null,
        );

        if ($request->wantsJson()) {
            return response()->json($betSlips);
        }

        return Inertia::render('Marketplace', [
            'bet_slips' => $betSlips,
            'filters' => [
                'max_break_even' => $filters['max_break_even'] ?? null,
            ],
        ]);
    }

    private function getBetslips(?float $maxBreakEven = null)
    {
        $user = auth()->user();
        $presenter = app(\App\Services\BetslipCardPresenter::class);

        $query = Betslip::with(\App\Services\BetslipCardPresenter::eagerLoad())
            ->withCount('odds')
            ->where('status', 'pending');

        if ($maxBreakEven !== null) {
            // Null break_even_stake slips are hidden under a filter. They
            // can only exist for pre-migration rows; the backfill closes
            // that window. Excluding them here means a filter never shows
            // a slip whose stake we can't compute.
            $query->whereNotNull('break_even_stake')
                ->where('break_even_stake', '<=', $maxBreakEven);
        }

        $betslips = $query->paginate(4);

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
