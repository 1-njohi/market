<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class SellerLookupController extends Controller
{
    public function show(Request $request)
    {
        $code = trim($request->query('code', ''));
        \Log::info($code);
    
        $seller = User::where('code', $code)
            ->with('sellerMetric')
            ->withCount(['betslips as active_tips_count' => fn($q) => $q->whereIn('status', ['pending', 'underway'])])
            ->first();

        if (!$seller) {
            return response()->json(['success' => false], 404);
        }

        // Compute their rank across all sellers (by ROI)
        $rank = User::whereHas('sellerMetric')
            ->whereRaw('(SELECT roi FROM seller_metrics WHERE user_id = users.id) > ?', [$seller->sellerMetric->roi ?? 0])
            ->count() + 1;

        return response()->json([
            'success' => true,
            'seller' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'code' => $seller->code,
                'avatar' => $seller->profile_picture_url
                    ?? "https://api.dicebear.com/10.x/thumbs/svg?seed=" . urlencode($seller->name),
                'roi' => round((float) ($seller->sellerMetric->roi ?? 0), 1),
                'win_rate' => round((float) ($seller->sellerMetric->win_rate ?? 0), 1),
                'streak' => 0, // optional: compute like LeaderboardService does
                'recent_form' => [],
                'active_tips' => (int) $seller->active_tips_count,
                'rank' => $rank,
            ],
        ]);
    }
}
