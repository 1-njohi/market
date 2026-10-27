<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSellerAccess
{
    /**
     * Sellers who have never qualified shouldn't see the seller console —
     * there's nothing for them to do there. Everyone else (`sellable`,
     * `warned`, `delisted`) keeps access: their history and standings
     * live behind this gate, and hiding it would erase their record.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/login');
        }

        if ($user->sellable_status === 'ineligible') {
            return redirect()
                ->back()
                ->with('error', 'The seller dashboard unlocks once you qualify as a seller.');
        }

        return $next($request);
    }
}