<?php

namespace App\Http\Middleware;

use App\Models\BetslipUserPurchase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasPurchased
{
    /**
     * The buyer dashboard unlocks after a user's first betslip purchase.
     * Before that there's nothing for them to see — live tracking, settlement
     * history, and buyer analytics are all empty until money has moved.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        $hasPurchased = BetslipUserPurchase::where('buyer_id', $user->id)->exists();

        if (! $hasPurchased) {
            return redirect('/dashboard')
                ->with('error', 'The buyer dashboard unlocks after your first purchase.');
        }

        return $next($request);
    }
}