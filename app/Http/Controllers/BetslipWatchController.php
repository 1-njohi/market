<?php

namespace App\Http\Controllers;

use App\Exceptions\WatchlistException;
use App\Models\Betslip;
use App\Services\WatchlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class BetslipWatchController extends Controller
{
    public function __construct(
        protected WatchlistService $watchlist,
    ) {
    }

    /**
     * POST /betslip/{code}/watch
     */
    public function store(string $code): RedirectResponse
    {
        $betslip = Betslip::where('code', $code)->first();

        if (!$betslip) {
            return back()->with('error', 'Betslip not found.');
        }

        try {
            $this->watchlist->watch(Auth::user(), $betslip);
        } catch (WatchlistException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            "Added to your watchlist. We'll notify you when #{$betslip->code} settles."
        );
    }

    /**
     * DELETE /betslip/{code}/watch
     */
    public function destroy(string $code): RedirectResponse
    {
        $betslip = Betslip::where('code', $code)->first();

        if (!$betslip) {
            return back()->with('error', 'Betslip not found.');
        }

        $this->watchlist->unwatch(Auth::user(), $betslip);

        return back()->with(
            'success',
            "Removed #{$betslip->code} from your watchlist."
        );
    }
}