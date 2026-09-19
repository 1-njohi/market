<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\SellerMetric;
use App\Services\SellerDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SellerDashboardController extends Controller
{
    protected SellerDashboardService $dashboardService;

    public function __construct(SellerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the seller dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get dashboard data with caching
        // $dashboardData = Cache::remember(
        //     "seller_dashboard_{$user->id}",
        //     300, // 5 minutes
        //     function () use ($user) {
        //         return $this->dashboardService->getDashboardData($user);
        //     }
        // );

        $dashboardData = $this->dashboardService->getDashboardData($user);
        return Inertia::render('SellerDashboard')->with([
            'seller_data' => $dashboardData,
        ]);
    }

    /**
     * Get real-time dashboard data (AJAX)
     */
    public function getRealtimeData(Request $request)
    {
        $user = Auth::user();
        $data = $this->dashboardService->getRealtimeData($user);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get performance chart data
     */
    public function getPerformanceData(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', '30d');

        $data = $this->dashboardService->getPerformanceData($user, $period);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get market performance breakdown
     */
    public function getMarketPerformance(Request $request)
    {
        $user = Auth::user();
        $data = $this->dashboardService->getMarketPerformance($user);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 20);

        $data = $this->dashboardService->getRecentActivity($user, $limit);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get financial summary
     */
    public function getFinancialSummary(Request $request)
    {
        $user = Auth::user();
        $data = $this->dashboardService->getFinancialSummary($user);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get AI insights
     */
    public function getInsights(Request $request)
    {
        $user = Auth::user();
        $data = $this->dashboardService->getInsights($user);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get follower statistics
     */
    public function getFollowerStats(Request $request)
    {
        $user = Auth::user();
        $data = $this->dashboardService->getFollowerStats($user);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}