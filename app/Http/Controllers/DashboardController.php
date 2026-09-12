<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Cache TTL for the role decision (seconds).
     */
    private const ROLE_CACHE_TTL = 3600; // 1 hour

    /**
     * Redirect the authenticated user to the most relevant dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Optional escape hatch: /dashboard?as=seller or ?as=buyer
        $override = $request->query('as');
        if (in_array($override, ['buyer', 'seller'], true)) {
            return redirect()->to("/{$override}/dashboard");
        }

        $role = Cache::remember(
            $this->cacheKey($user),
            self::ROLE_CACHE_TTL,
            fn () => $this->determineRole($user),
        );

        return redirect()->to("/{$role}/dashboard");
    }

    /**
     * Decide which dashboard suits this user best.
     *
     *   - Only sells        → seller
     *   - Only buys         → buyer
     *   - Both, active listings → seller
     *   - Both, otherwise   → whichever is more recent
     *   - Neither           → buyer (default landing for new users)
     */
    private function determineRole(User $user): string
    {
        $hasSold = $user->betslips()->exists();
        $hasBought = $user->purchases()->exists();

        // Nobody does anything yet — send them to the buyer view
        if (!$hasSold && !$hasBought) {
            return 'buyer';
        }

        if ($hasSold && !$hasBought) {
            return 'seller';
        }

        if ($hasBought && !$hasSold) {
            return 'buyer';
        }

        // Both — favour seller if they have live inventory
        $hasActiveListings = $user->betslips()
            ->whereIn('status', ['pending', 'underway'])
            ->exists();

        if ($hasActiveListings) {
            return 'seller';
        }

        // Otherwise, whichever side they touched most recently
        $lastSaleAt = $user->betslips()->max('created_at');
        $lastPurchaseAt = $user->purchases()->max('created_at');

        return ($lastSaleAt && $lastSaleAt >= $lastPurchaseAt) ? 'seller' : 'buyer';
    }

    /**
     * Centralised cache key so invalidation stays in sync.
     */
    public static function cacheKey(User $user): string
    {
        return "dashboard_role_{$user->id}";
    }
}
