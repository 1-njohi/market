<?php

namespace App\Services;

use App\Models\User;
use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\SellerMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\WalletService;

class SellerDashboardService
{
    protected WalletService $walletService;
    protected PlatformFeeService $platformFeeService;

    public function __construct(WalletService $walletService, PlatformFeeService $platformFeeService)
    {
        $this->walletService = $walletService;
        $this->platformFeeService = $platformFeeService;
    }

    /**
     * Get complete dashboard data
     */
    public function getDashboardData(User $user): array
    {
        return [
            'user' => $this->getUserInfo($user),
            'performance' => $this->getPerformanceMetrics($user),
            'betslips' => $this->getBetslipManagement($user),
            'wallet' => $this->getWalletSummary($user),
            'activity' => $this->getRecentActivity($user, 15),
            'settlements' => $this->getSettlements($user),
            'financial' => $this->getFinancialSummary($user),
            'insights' => $this->getInsights($user),
            'follower_stats' => $this->getFollowerStats($user),
            'charts' => $this->getChartData($user),
            'quick_stats' => $this->getQuickStats($user),
            'notifications' => $this->getNotifications($user),
            'fee_tier' => $this->getFeeTier($user),
        ];
    }
    /**
     * Get real-time data
     */

    // Somewhere in SellerDashboardService
    public function getFeeTier(User $seller): array
    {
        $totalSales = $this->platformFeeService->getTotalSalesCount($seller);
        $currentPct = $this->platformFeeService->getFeePercentageFor($seller);
        $tiers = config('services.betslip_pirates.fee_tiers');

        // Find current tier index
        $currentIdx = null;
        foreach ($tiers as $i => $tier) {
            if ($tier['max'] === null || $totalSales <= $tier['max']) {
                $currentIdx = $i;
                break;
            }
        }

        $nextTier = $tiers[$currentIdx + 1] ?? null;
        $currentTier = $tiers[$currentIdx];

        return [
            'total_sales' => $totalSales,
            'current_percentage' => $currentPct,
            'current_tier' => $currentIdx + 1,
            'next_tier_percentage' => $nextTier['percentage'] ?? null,
            'sales_until_next_tier' => $nextTier
                ? max(0, $currentTier['max'] - $totalSales + 1)
                : null,
        ];
    }
    public function getRealtimeData(User $user): array
    {
        return [
            'active_betslips' => $this->getActiveBetslipsCount($user),
            'today_sales' => $this->getTodaySales($user),
            'pending_settlements' => $this->getPendingSettlementsCount($user),
            'unread_notifications' => $this->getUnreadNotificationsCount($user),
            'recent_purchases' => $this->getRecentPurchases($user, 5),
        ];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(User $user): array
    {
        $betslips = $user->betslips()->where('status', 'settled')->get();
        $totalBetslips = $betslips->count();
        $wonBetslips = $betslips->where('is_winner', true);

        $winRate = $totalBetslips > 0
            ? round(($wonBetslips->count() / $totalBetslips) * 100, 1)
            : 0;

        // Calculate ROI
        $totalWonAmount = $wonBetslips->sum(function ($betslip) {
            return $betslip->total_odds * $betslip->price;
        });
        $totalLostAmount = $betslips->where('is_winner', false)->sum('price');
        $totalStaked = $totalWonAmount + $totalLostAmount;
        $roi = $totalStaked > 0
            ? round(($totalWonAmount / $totalStaked) * 100, 1)
            : 0;

        // Recent form (last 10)
        $recentForm = $betslips
            ->take(10)
            ->map(function ($betslip) {
                return [
                    'status' => $betslip->is_winner ? 'W' : 'L',
                    'date' => $betslip->created_at->format('Y-m-d')
                ];
            })
            ->values()
            ->toArray();

        // Win rate breakdown
        $now = Carbon::now();
        $winRateBreakdown = [
            'last_7_days' => $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(7)),
            'last_30_days' => $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(30)),
            'last_90_days' => $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(90)),
            'all_time' => $winRate,
        ];

        return [
            'win_rate' => $winRate,
            'win_rate_change' => $this->calculateWinRateChange($user),
            'roi' => $roi,
            'roi_change' => $this->calculateROIChange($user),
            'total_betslips' => $user->betslips()->count(),
            'total_sold' => BetslipUserPurchase::where('seller_id', $user->id)
                ->whereIn('status', ['won', 'refunded', 'voided'])
                ->count(),
            'total_revenue' => $this->getTotalRevenue($user),
            'total_revenue_change' => $this->calculateRevenueChange($user),
            'avg_price' => $this->getAveragePrice($user),
            'avg_odds' => $this->getAverageOdds($user),
            'avg_legs' => $this->getAverageLegs($user),
            'recent_form' => $recentForm,
            'win_rate_breakdown' => $winRateBreakdown,
        ];
    }

    /**
     * Get betslip management data
     */
    public function getBetslipManagement(User $user): array
    {
        $activeBetslips = $user->betslips()
            // // ->where('status', 'pending')
            // ->where('remaining', '>', 0)
            ->withCount('odds as legs')
            ->withCount('purchases as purchases_count')
            ->orderBy('created_at', 'desc')
            ->get();

        $expiringSoon = $activeBetslips->filter(function ($betslip) {
            return $betslip->created_at->diffInDays(Carbon::now()) >= 7;
        });

        $soldOut = $user->betslips()
            ->where('status', 'sold')
            ->orWhere('remaining', 0)
            ->count();

        return [
            'active' => $activeBetslips->map(function ($betslip) {
                return [
                    'id' => $betslip->id,
                    'code' => $betslip->code,
                    'legs' => $betslip->legs,
                    'is_winner' => $betslip->is_winner,
                    'total_odds' => round($betslip->total_odds, 2),
                    'price' => round($betslip->price, 2),
                    'remaining' => $betslip->remaining,
                    'status' => $betslip->status,
                    'created_at' => $betslip->created_at->toISOString(),
                    'days_active' => $betslip->created_at->diffInDays(Carbon::now()),
                    'is_expiring_soon' => $betslip->created_at->diffInDays(Carbon::now()) >= 7,
                    'purchases' => $betslip->purchases_count
                ];
            }),
            'total_active' => $activeBetslips->count(),
            'expiring_soon' => $expiringSoon->count(),
            'sold_out' => $soldOut,
            'pending_settlement' => $user->betslips()
                ->where('status', 'pending')
                ->where('remaining', 0)
                ->count(),
        ];
    }

    /**
     * Chronological feed of everything that happened to this seller's
     * betslips: sales (buyers purchasing) and settlements (terminal states).
     *
     * Every event shares the same shape so the frontend renders them
     * uniformly. See WalletService docs for the ledger side of these.
     */
    public function getRecentActivity(User $user, int $limit = 20): array
    {
        // ── Sales: buyers who purchased one of my betslips ──────────────
        $sales = BetslipUserPurchase::where('seller_id', $user->id)
            ->with(['buyer', 'betslip'])
            ->orderByDesc('created_at')
            ->take($limit)
            ->get()
            ->map(function ($purchase) {
                return [
                    'kind' => 'sale',
                    'badge' => 'S',
                    'tone' => 'neutral',
                    'message' => "{$purchase->buyer->name} bought #{$purchase->betslip->code}",
                    'amount' => (float) $purchase->purchase_price,
                    'created_at' => $purchase->created_at->toISOString(),
                    'time_ago' => $purchase->created_at->diffForHumans(),
                ];
            });

        // ── Settlements: my betslips reaching a terminal state ──────────
        $settlements = $user->betslips()
            ->whereIn('status', ['settled', 'voided'])
            ->orderByDesc('updated_at')
            ->take($limit)
            ->get()
            ->map(function ($betslip) {
                if ($betslip->status === 'voided') {
                    return [
                        'kind' => 'settlement_voided',
                        'badge' => 'V',
                        'tone' => 'neutral',
                        'message' => "#{$betslip->code} voided — buyers refunded",
                        'amount' => null,
                        'created_at' => $betslip->updated_at->toISOString(),
                        'time_ago' => $betslip->updated_at->diffForHumans(),
                    ];
                }

                if ($betslip->is_winner) {
                    return [
                        'kind' => 'settlement_won',
                        'badge' => 'W',
                        'tone' => 'positive',
                        'message' => "#{$betslip->code} settled WON — payout released",
                        'amount' => null,
                        'created_at' => $betslip->updated_at->toISOString(),
                        'time_ago' => $betslip->updated_at->diffForHumans(),
                    ];
                }

                return [
                    'kind' => 'settlement_lost',
                    'badge' => 'L',
                    'tone' => 'neutral',
                    'message' => "#{$betslip->code} settled LOST — buyers refunded",
                    'amount' => null,
                    'created_at' => $betslip->updated_at->toISOString(),
                    'time_ago' => $betslip->updated_at->diffForHumans(),
                ];
            });

        return $sales
            ->concat($settlements)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Settled purchases for the seller, newest first.
     *
     * Reads the actual payout and fee amounts off the ledger (transactions
     * tagged with this pivot) rather than recomputing from the current fee
     * tier. Historical settlements therefore reflect what was actually paid
     * out, even if the seller's tier changes later.
     */
    public function getSettlements(User $user, int $limit = 20): array
    {
        return $user->purchasesAsSeller()
            ->with(['betslip', 'buyer', 'transactions'])
            ->whereIn('status', ['won', 'refunded', 'voided'])
            ->orderByDesc('settled_at')
            ->take($limit)
            ->get()
            ->map(function ($purchase) {
                $available = $purchase->transactions
                    ->where('user_id', $purchase->seller_id)
                    ->where('balance_type', 'available');

                $gross = (float) $available->where('type', 'payout')->sum('amount');
                $fee = abs((float) $available->where('type', 'fee')->sum('amount'));
                $net = $gross - $fee;

                return [
                    'id' => $purchase->id,
                    'betslip_code' => $purchase->betslip->code ?? 'N/A',
                    'outcome' => $purchase->status,
                    'buyer_name' => $purchase->buyer->name ?? 'Unknown',
                    'buyer_code' => $purchase->buyer->code ?? null,
                    'gross' => round($gross, 2),
                    'fee' => round($fee, 2),
                    'net' => round($net, 2),
                    'settled_at' => $purchase->settled_at?->toISOString(),
                    'settled_ago' => $purchase->settled_at?->diffForHumans(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get financial summary
     */
    public function getFinancialSummary(User $user): array
    {
        $wallet = $this->walletService->getWallet($user);

        // Lifetime earnings, straight from the ledger. Each won purchase
        // writes a payout row (gross) and, if applicable, a fee row.
        $lifetimeGross = (float) \App\Models\Transaction::where('user_id', $user->id)
            ->where('balance_type', 'available')
            ->where('type', \App\Models\Transaction::TYPE_PAYOUT)
            ->sum('amount');

        $lifetimeFees = abs((float) \App\Models\Transaction::where('user_id', $user->id)
            ->where('balance_type', 'available')
            ->where('type', \App\Models\Transaction::TYPE_FEE)
            ->sum('amount'));

        $netEarnings = $lifetimeGross - $lifetimeFees;

        // Projected earnings from still-pending sales — not wallet money.
        $grossAtStake = (float) BetslipUserPurchase::where('seller_id', $user->id)
            ->where('status', 'pending')
            ->sum('purchase_price');

        $feePct = $this->platformFeeService->getFeePercentageFor($user);
        $netAtStake = round($grossAtStake * (1 - $feePct), 2);

        return [
            'total_revenue' => round($lifetimeGross, 2),
            'platform_fees' => round($lifetimeFees, 2),
            'net_earnings' => round($netEarnings, 2),
            'available_balance' => (float) $wallet->balance,
            'gross_at_stake' => round($grossAtStake, 2),
            'net_at_stake' => $netAtStake,
            'revenue_trend' => $this->getRevenueTrend($user),
        ];
    }

    /**
     * Get AI insights
     */
    public function getInsights(User $user): array
    {
        $betslips = $user->betslips()->whereIn('status', ['settled', 'voided'])->get();
        $winRate = $this->calculateWinRate($betslips);

        $insights = [];

        // Best day
        $bestDay = $this->calculateBestDay($betslips);
        if ($bestDay) {
            $insights[] = "Your best day is {$bestDay['day']} ({$bestDay['win_rate']}% win rate)";
        }

        // Best league
        $bestLeague = $this->calculateBestLeague($user);
        if ($bestLeague) {
            $insights[] = "Premier League bets perform best ({$bestLeague['win_rate']}%)";
        }

        // Best leg count
        $bestLegs = $this->calculateBestLegCount($user);
        if ($bestLegs) {
            $insights[] = "{$bestLegs['legs']}-leg bets have the highest win rate ({$bestLegs['win_rate']}%)";
        }

        // Best odds range
        $bestOdds = $this->calculateBestOddsRange($user);
        if ($bestOdds) {
            $insights[] = "Low odds ({$bestOdds['range']}) have {$bestOdds['win_rate']}% win rate";
        }

        // Streak
        $streak = $this->calculateCurrentStreak($betslips);
        if ($streak && $streak['type'] === 'win') {
            $insights[] = "You're on a {$streak['count']}-win streak! 🔥";
        }

        // ROI change
        $roiChange = $this->calculateROIChange($user);
        if ($roiChange > 0) {
            $insights[] = "Your ROI increased by {$roiChange}% this month";
        }

        // Market suggestion
        $suggestedMarket = $this->suggestMarket($user);
        if ($suggestedMarket) {
            $insights[] = "Consider more {$suggestedMarket['name']} bets ({$suggestedMarket['win_rate']}% win rate)";
        }

        return $insights;
    }

    /**
     * Get follower statistics
     */
    public function getFollowerStats(User $user): array
    {
        $followers = $user->followers;
        $totalFollowers = $followers->count();

        $recentFollowers = $followers
            ->take(5)
            ->map(function ($follower) {
                return [
                    'name' => $follower->name,
                    'avatar' => $follower->avatar ?? "https://ui-avatars.com/api/?name={$follower->name}",
                    'followed_at' => Carbon::parse($follower->pivot->followed_at)->diffForHumans(),
                    'code' => $follower->code
                ];
            });

        return [
            'total' => $totalFollowers,
            'new_this_week' => $this->getNewFollowersThisWeek($user),
            'active_followers' => $this->getActiveFollowers($user),
            'engaged_followers' => $this->getEngagedFollowers($user),
            'recent' => $recentFollowers,
            'engagement_rate' => $this->calculateEngagementRate($user),
        ];
    }

    /**
     * Get chart data
     */
    public function getChartData(User $user): array
    {
        return [
            'win_rate_trend' => $this->getWinRateTrend($user),
            'profit_loss' => $this->getProfitLossData($user),
            'market_distribution' => $this->getMarketDistribution($user),
            'league_performance' => $this->getLeaguePerformance($user),
            'time_performance' => $this->getTimePerformance($user),
        ];
    }

    /**
     * Get quick stats
     */
    public function getQuickStats(User $user): array
    {
        $betslips = $user->betslips()->whereIn('status', ['settled', 'voided'])->get();

        return [
            'win_rate' => $this->calculateWinRate($betslips),
            'roi' => $this->calculateROI($betslips),
            'active_betslips' => $user->betslips()->where('status', 'pending')->where('remaining', '>', 0)->count(),
            'total_sold' => BetslipUserPurchase::where('seller_id', $user->id)
                ->whereIn('status', ['won', 'refunded', 'voided'])
                ->count(),
            'total_revenue' => round($this->getTotalRevenue($user), 0),
            'followers' => $user->followers()->count(),
            'profile_views' => $user->profile_views ?? 0,
        ];
    }

    /**
     * Get notifications
     */
    private function getUserInfo(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->profile_picture_url
                ?? "https://ui-avatars.com/api/?name=" . urlencode($user->name),
            'email' => $user->email,
            'member_since' => $user->created_at->format('F Y'),
            'is_verified' => !is_null($user->email_verified_at),
        ];
    }

    /**
     * Real notifications, matching the buyer dashboard format.
     */
    public function getNotifications(User $user): array
    {
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $notifications->map(function ($notification) {
                $data = $notification->data ?? [];

                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? 'general',
                    'title' => $data['title'] ?? null,
                    'message' => $data['body'] ?? '',
                    'betslip_id' => $data['betslip_id'] ?? null,
                    'betslip_code' => $data['betslip_code'] ?? null,
                    'time' => $notification->created_at->diffForHumans(),
                    'created_at' => $notification->created_at->toISOString(),
                    'read' => !is_null($notification->read_at),
                ];
            })->values()->toArray(),
        ];
    }
    // ------------------ Helper Methods ------------------ //

    private function calculateWinRate($betslips): float
    {
        $total = $betslips->count();
        $won = $betslips->where('is_winner', true)->count();
        return $total > 0 ? round(($won / $total) * 100, 1) : 0;
    }

    private function calculateROI($betslips): float
    {
        $wonAmount = $betslips->where('is_winner', true)->sum(function ($betslip) {
            return $betslip->total_odds * $betslip->price;
        });
        $lostAmount = $betslips->where('is_winner', false)->sum('price');
        $total = $wonAmount + $lostAmount;
        return $total > 0 ? round(($wonAmount / $total) * 100, 1) : 0;
    }

    private function calculateWinRateForPeriod($betslips, $since): float
    {
        $periodBetslips = $betslips->filter(function ($betslip) use ($since) {
            return $betslip->created_at >= $since;
        });
        return $this->calculateWinRate($periodBetslips);
    }

    private function getTotalRevenue(User $user): float
    {
        return BetslipUserPurchase::where('seller_id', $user->id)
            ->where('status', 'won')
            ->sum('purchase_price');
    }

    private function getAveragePrice(User $user): float
    {
        return BetslipUserPurchase::where('seller_id', $user->id)
            ->where('status', 'won')
            ->avg('purchase_price') ?? 0;
    }

    private function getAverageOdds(User $user): float
    {
        return $user->betslips()->avg('total_odds') ?? 0;
    }

    private function getAverageLegs(User $user): float
    {
        return $user->betslips()->withCount('odds')->get()->avg('odds_count') ?? 0;
    }

    private function calculateWinRateChange(User $user): float
    {
        // Compare last 30 days vs previous 30 days
        $now = Carbon::now();
        $betslips = $user->betslips()->whereIn('status', ['settled', 'voided'])->get();

        $current = $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(30));
        $previous = $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(60)->subDays(30));

        return round($current - $previous, 1);
    }

    private function calculateROIChange(User $user): float
    {
        // Similar to win rate change
        return round(rand(-3, 5), 1); // Placeholder
    }

    private function calculateRevenueChange(User $user): float
    {
        // Similar to win rate change
        return round(rand(-1000, 2000), 0); // Placeholder
    }

    private function getRevenueTrend(User $user): array
    {
        // Generate last 7 days revenue data
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = BetslipUserPurchase::where('seller_id', $user->id)
                ->where('status', 'won')
                ->whereDate('created_at', $date->toDateString())
                ->sum('purchase_price');
            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'revenue' => round($revenue, 2),
            ];
        }
        return $trend;
    }

    private function calculateBestDay($betslips): ?array
    {
        $dayStats = [];
        foreach ($betslips as $betslip) {
            $day = $betslip->created_at->format('l');
            if (!isset($dayStats[$day])) {
                $dayStats[$day] = ['total' => 0, 'won' => 0];
            }
            $dayStats[$day]['total']++;
            if ($betslip->is_winner) {
                $dayStats[$day]['won']++;
            }
        }

        $best = null;
        $bestWinRate = 0;
        foreach ($dayStats as $day => $stats) {
            $winRate = $stats['total'] > 0 ? ($stats['won'] / $stats['total']) * 100 : 0;
            if ($winRate > $bestWinRate && $stats['total'] >= 5) {
                $bestWinRate = $winRate;
                $best = ['day' => $day, 'win_rate' => round($winRate, 1)];
            }
        }
        return $best;
    }

    private function calculateBestLeague(User $user): ?array
    {
        // This would require joining with fixtures and leagues
        return null; // Placeholder
    }

    private function calculateBestLegCount(User $user): ?array
    {
        // This would require analyzing betslip leg counts
        return null; // Placeholder
    }

    private function calculateBestOddsRange(User $user): ?array
    {
        // This would require analyzing odds ranges
        return null; // Placeholder
    }

    private function calculateCurrentStreak($betslips): ?array
    {
        $recent = $betslips->take(10);
        $streak = 0;
        $type = null;

        foreach ($recent as $betslip) {
            if ($betslip->is_winner) {
                if ($type === 'win' || $type === null) {
                    $streak++;
                    $type = 'win';
                } else {
                    break;
                }
            } else {
                if ($type === 'loss' || $type === null) {
                    $streak++;
                    $type = 'loss';
                } else {
                    break;
                }
            }
        }

        return $streak > 0 ? ['count' => $streak, 'type' => $type] : null;
    }

    private function suggestMarket(User $user): ?array
    {
        // This would require analyzing market performance
        return null; // Placeholder
    }

    private function getNewFollowersThisWeek(User $user): int
    {
        return $user->followers()
            ->wherePivot('followed_at', '>=', Carbon::now()->subWeek())
            ->count();
    }

    private function getActiveFollowers(User $user): int
    {
        // Followers who have purchased in the last 30 days
        // return $user->followers()
        //     ->whereHas('purchasedBetslips', function ($query) {
        //         $query->where('created_at', '>=', Carbon::now()->subDays(30));
        //     })
        //     ->count();

        return $user->followers()
            ->whereHas('purchasedBetslips', function ($query) {
                $query->where('betslip_user_purchases.created_at', '>=', Carbon::now()->subDays(30));
            })
            ->count();
    }

    private function getEngagedFollowers(User $user): int
    {
        // Followers who have interacted (viewed, purchased, messaged)
        return round($user->followers()->count() * 0.36); // Placeholder
    }

    private function calculateEngagementRate(User $user): float
    {
        $total = $user->followers()->count();
        $engaged = $this->getEngagedFollowers($user);
        return $total > 0 ? round(($engaged / $total) * 100, 1) : 0;
    }

    private function getActiveBetslipsCount(User $user): int
    {
        return $user->betslips()
            ->where('status', 'pending')
            ->where('remaining', '>', 0)
            ->count();
    }

    private function getTodaySales(User $user): array
    {
        $today = Carbon::today();
        $sales = BetslipUserPurchase::where('seller_id', $user->id)
            ->whereDate('created_at', $today)
            ->get();

        return [
            'count' => $sales->count(),
            'amount' => round($sales->sum('purchase_price'), 2),
        ];
    }

    private function getPendingSettlementsCount(User $user): int
    {
        return $user->betslips()
            ->where('status', 'pending')
            ->where('remaining', 0)
            ->count();
    }

    private function getUnreadNotificationsCount(User $user): int
    {
        // Placeholder - would come from notifications table
        return 3;
    }

    private function getRecentPurchases(User $user, int $limit): array
    {
        return BetslipUserPurchase::where('seller_id', $user->id)
            ->with(['buyer', 'betslip'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($purchase) {
                return [
                    'buyer_name' => $purchase->buyer->name,
                    'betslip_code' => $purchase->betslip->code,
                    'amount' => round($purchase->purchase_price, 2),
                    'time' => $purchase->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    // Chart data generators
    private function getWinRateTrend(User $user): array
    {
        $data = [];
        $betslips = $user->betslips()->whereIn('status', ['settled', 'voided'])->get();

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayBetslips = $betslips->filter(function ($betslip) use ($date) {
                return $betslip->created_at->format('Y-m-d') === $date->format('Y-m-d');
            });

            $total = $dayBetslips->count();
            $won = $dayBetslips->where('is_winner', true)->count();

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'win_rate' => $total > 0 ? round(($won / $total) * 100, 1) : null,
            ];
        }

        return $data;
    }

    private function getProfitLossData(User $user): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $profit = BetslipUserPurchase::where('seller_id', $user->id)
                ->where('status', 'won')
                ->whereDate('created_at', $date->toDateString())
                ->sum('purchase_price');
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'profit' => round((float) $profit, 2),
            ];
        }
        return $data;
    }

    private function getMarketDistribution(User $user): array
    {
        // Placeholder - would analyze market distribution
        return [
            ['market' => 'Match Winner', 'percentage' => 45],
            ['market' => 'Over/Under', 'percentage' => 28],
            ['market' => 'BTTS', 'percentage' => 15],
            ['market' => 'Double Chance', 'percentage' => 8],
            ['market' => 'Other', 'percentage' => 4],
        ];
    }

    private function getLeaguePerformance(User $user): array
    {
        // Placeholder - would analyze league performance
        return [
            ['league' => 'Premier League', 'win_rate' => 82],
            ['league' => 'Champions League', 'win_rate' => 76],
            ['league' => 'La Liga', 'win_rate' => 70],
            ['league' => 'Bundesliga', 'win_rate' => 68],
        ];
    }

    private function getTimePerformance(User $user): array
    {
        // Placeholder - would analyze time-based performance
        return [
            ['day' => 'Mon', 'win_rate' => 65],
            ['day' => 'Tue', 'win_rate' => 72],
            ['day' => 'Wed', 'win_rate' => 68],
            ['day' => 'Thu', 'win_rate' => 75],
            ['day' => 'Fri', 'win_rate' => 78],
            ['day' => 'Sat', 'win_rate' => 82],
            ['day' => 'Sun', 'win_rate' => 70],
        ];
    }
    public function getWalletSummary(User $user): array
    {
        $wallet = $this->walletService->getWallet($user);

        $grossAtStake = (float) \App\Models\BetslipUserPurchase::where('seller_id', $user->id)
            ->where('status', 'pending')
            ->sum('purchase_price');

        $feePct = $this->platformFeeService->getFeePercentageFor($user);
        $netIfAllWin = round($grossAtStake * (1 - $feePct), 2);

        return [
            'balance' => (float) $wallet->balance,
            'gross_at_stake' => round($grossAtStake, 2),
            'net_if_all_win' => $netIfAllWin,
            'total_deposited' => (float) $wallet->total_deposited,
            'total_withdrawn' => (float) $wallet->total_withdrawn,
            'currency' => $wallet->currency ?? 'KES',
            'recent_transactions' => $user->transactions()
                ->where('balance_type', 'available')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')   // within same timestamp, execution order:
                // payout row was written before fee row
                ->take(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => (float) $transaction->amount,
                        'balance_before' => (float) $transaction->balance_before,
                        'balance_after' => (float) $transaction->balance_after,
                        'description' => $transaction->description,
                        'status' => $transaction->status,
                        'created_at' => $transaction->created_at->toISOString(),
                    ];
                })->values()->toArray(),
        ];
    }
}