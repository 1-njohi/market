<?php

namespace App\Http\Controllers;

use App\Models\Betslip;
use App\Services\BetslipCardPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WatchlistController extends Controller
{
    public function __construct(
        protected BetslipCardPresenter $presenter,
    ) {
    }
    public function index(Request $request)
    {
        $user = Auth::user();

        // Watch order: newest-watched first. We'll preserve this order in PHP.
        $watchedIds = DB::table('betslip_watches')
            ->join('betslips', 'betslips.id', '=', 'betslip_watches.betslip_id')
            ->where('betslip_watches.user_id', $user->id)
            ->whereIn('betslips.status', ['pending', 'underway'])
            ->orderByDesc('betslip_watches.watched_at')
            ->pluck('betslips.id');

        $betslips = Betslip::with(BetslipCardPresenter::eagerLoad())
            ->withCount('odds')
            ->whereIn('id', $watchedIds)
            ->get()
            // Sort by position in $watchedIds, which is already in watch order.
            ->sortBy(fn($b) => $watchedIds->search($b->id))
            ->values()
            ->map(fn($b) => $this->presenter->present($b, $user))
            ->all();

        if ($request->wantsJson()) {
            return response()->json(['data' => $betslips]);
        }

        return Inertia::render('Watchlist', [
            'bet_slips' => ['data' => $betslips],
        ]);
    }
}