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

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get complete dashboard data
     */
    public function getDashboardData(User $user): array
    {
        $wa = $this->getWalletSummary($user);
        Log::info($wa);
        return [
            'user' => $this->getUserInfo($user),
            'performance' => $this->getPerformanceMetrics($user),
            'betslips' => $this->getBetslipManagement($user),
            'wallet' => $wa,
            'activity' => $this->getRecentActivity($user, 15),
            'financial' => $this->getFinancialSummary($user),
            'insights' => $this->getInsights($user),
            'follower_stats' => $this->getFollowerStats($user),
            'charts' => $this->getChartData($user),
            'quick_stats' => $this->getQuickStats($user),
            'notifications' => $this->getNotifications($user),
        ];
    }

    /**
     * Get real-time data
     */
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
        $betslips = $user->betslips()->whereIn('status', ['settled', 'completed'])->get();
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
            'total_sold' => $user->betslips()->where('status', 'sold')->count(),
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
            ->where('status', 'pending')
            ->where('remaining', '>', 0)
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
     * Get recent activity
     */
    public function getRecentActivity(User $user, int $limit = 20): array
    {
        $activities = collect();

        // Purchases
        $purchases = BetslipUserPurchase::where('seller_id', $user->id)
            ->with(['buyer', 'betslip'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($purchase) {
                return [
                    'type' => 'purchase',
                    'message' => "{$purchase->buyer->name} purchased #{$purchase->betslip->code}",
                    'amount' => $purchase->purchase_price,
                    'betslip_code' => $purchase->betslip->code,
                    'buyer_name' => $purchase->buyer->name,
                    'created_at' => $purchase->created_at->toISOString(),
                    'time_ago' => $purchase->created_at->diffForHumans(),
                ];
            });

        // Settlements
        $settlements = $user->betslips()
            ->whereIn('status', ['settled', 'completed'])
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($betslip) {
                $icon = $betslip->is_winner ? '🎉' : '😔';
                $status = $betslip->is_winner ? 'WINNER' : 'LOST';
                return [
                    'type' => 'settlement',
                    'message' => "#{$betslip->code} settled - {$status} {$icon}",
                    'result' => $betslip->is_winner ? 'won' : 'lost',
                    'betslip_code' => $betslip->code,
                    'created_at' => $betslip->updated_at->toISOString(),
                    'time_ago' => $betslip->updated_at->diffForHumans(),
                ];
            });

        // New followers (if you have this model)
        // $followers = $user->followers()->orderBy('followed_at', 'desc')->take($limit)->get()->map...

        $activities = $purchases->concat($settlements)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->toArray();

        return $activities;
    }

    /**
     * Get financial summary
     */
    public function getFinancialSummary(User $user): array
    {
        $totalRevenue = $this->getTotalRevenue($user);
        $totalFees = $totalRevenue * 0.10; // 10% platform fee
        $netEarnings = $totalRevenue - $totalFees;

        $pendingPayouts = BetslipUserPurchase::where('seller_id', $user->id)
            ->where('status', 'pending')
            ->sum('purchase_price');

        $availableBalance = $netEarnings - $pendingPayouts;

        $transactions = BetslipUserPurchase::where('seller_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($purchase) {
                return [
                    'date' => $purchase->created_at->format('Y-m-d'),
                    'betslip_code' => $purchase->betslip->code,
                    'buyer_name' => $purchase->buyer->name,
                    'amount' => round($purchase->purchase_price, 2),
                    'status' => $purchase->status,
                ];
            });

        return [
            'total_revenue' => round($totalRevenue, 2),
            'platform_fees' => round($totalFees, 2),
            'net_earnings' => round($netEarnings, 2),
            'pending_payouts' => round($pendingPayouts, 2),
            'available_balance' => max(0, round($availableBalance, 2)),
            'transactions' => $transactions,
            'revenue_trend' => $this->getRevenueTrend($user),
        ];
    }

    /**
     * Get AI insights
     */
    public function getInsights(User $user): array
    {
        $betslips = $user->betslips()->whereIn('status', ['settled', 'completed'])->get();
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
        $betslips = $user->betslips()->whereIn('status', ['settled', 'completed'])->get();

        return [
            'win_rate' => $this->calculateWinRate($betslips),
            'roi' => $this->calculateROI($betslips),
            'active_betslips' => $user->betslips()->where('status', 'pending')->where('remaining', '>', 0)->count(),
            'total_sold' => $user->betslips()->where('status', 'sold')->count(),
            'total_revenue' => round($this->getTotalRevenue($user), 0),
            'followers' => $user->followers()->count(),
            'profile_views' => $user->profile_views ?? 0,
        ];
    }

    /**
     * Get notifications
     */
    public function getNotifications(User $user): array
    {
        // This would typically come from a notifications table
        return [
            'unread_count' => 3,
            'items' => [
                [
                    'type' => 'purchase',
                    'message' => 'JohnD purchased #ABC-1234',
                    'time' => Carbon::now()->subMinutes(2)->diffForHumans(),
                    'read' => false,
                ],
                [
                    'type' => 'settlement',
                    'message' => '#XYZ-7890 settled - WINNER! 🎉',
                    'time' => Carbon::now()->subHour()->diffForHumans(),
                    'read' => false,
                ],
                [
                    'type' => 'follower',
                    'message' => '@BettingPro started following you',
                    'time' => Carbon::now()->subHours(3)->diffForHumans(),
                    'read' => true,
                ],
            ],
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
            ->where('status', 'completed')
            ->sum('purchase_price');
    }

    private function getAveragePrice(User $user): float
    {
        return BetslipUserPurchase::where('seller_id', $user->id)
            ->where('status', 'completed')
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
        $betslips = $user->betslips()->whereIn('status', ['settled', 'completed'])->get();

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
                ->where('status', 'completed')
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
        $betslips = $user->betslips()->whereIn('status', ['settled', 'completed'])->get();

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
                ->whereDate('created_at', $date->toDateString())
                ->sum('purchase_price');
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'profit' => round($profit, 2),
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

    private function getUserInfo(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar ?? "https://ui-avatars.com/api/?name={$user->name}",
            'email' => $user->email,
            'member_since' => $user->created_at->format('F Y'),
            'is_verified' => $user->is_verified ?? false,
        ];
    }

    public function getWalletSummary(User $user): array
    {
        $wallet = $this->walletService->getWallet($user);

        return [
            'balance' => (float) $wallet->balance,
            'pending_balance' => (float) $wallet->pending_balance,
            'total_deposited' => (float) $wallet->total_deposited,
            'total_withdrawn' => (float) $wallet->total_withdrawn,
            'currency' => $wallet->currency ?? 'KES',
            'recent_transactions' => $user->transactions()
                ->orderBy('created_at', 'desc')
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
                }),
        ];
    }

}