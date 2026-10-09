<?php

namespace App\Http\Controllers;

use App\Services\BuyerDashboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;

class BuyerDashboardController extends Controller
{
    protected BuyerDashboardService $dashboardService;

    public function __construct(BuyerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $user = Auth::user();

        // Use caching if you want
        // $dashboardData = Cache::remember("buyer_dashboard_{$user->id}", 300, function () use ($user) {
        //     return $this->dashboardService->getDashboardData($user);
        // });

        $dashboardData = $this->dashboardService->getDashboardData($user);

        return Inertia::render('BuyerDashboard')->with([
            'buyer_data' => $dashboardData,
        ]);
        ;
    }
    
    public function followingIndex()
    {
        return Inertia::render('Buyer/Following', [
            'following' => $this->dashboardService->paginatedFollowing(Auth::user()),
        ]);
    }

    // Optional: AJAX endpoints for real-time updates
    public function getPerformanceData()
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getPerformanceMetrics($user),
        ]);
    }

    public function getRecentActivity()
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getRecentActivity($user, 20),
        ]);
    }

    public function getFinancialSummary()
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getFinancialSummary($user),
        ]);
    }

    public function getWalletSummary()
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getWalletSummary($user),
        ]);
    }

    public function getInsights()
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getInsights($user),
        ]);
    }

    public function purchasesIndex(Request $request)
    {
        $allowed = ['active', 'settled', 'won', 'refunded', 'voided'];
        $status  = $request->query('status');
        if (!in_array($status, $allowed, true)) {
            $status = null;
        }

        return Inertia::render('Buyer/Purchases', [
            'purchases' => $this->dashboardService->paginatedPurchases(
                Auth::user(),
                $status,
            ),
            'filters' => ['status' => $status],
        ]);
    }
}