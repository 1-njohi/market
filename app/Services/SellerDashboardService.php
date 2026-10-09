<?php

namespace App\Services;

use App\Models\User;
use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Contest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\WalletService;

class SellerDashboardService
{
    public function __construct(
        protected WalletService $walletService,
        protected PlatformFeeService $platformFeeService,
        protected ReferralService $referralService,
        protected ContestService $contestService,
    ) {
    }

    /**
     * Get complete dashboard data
     */
    public function getDashboardData(
        User $user,
        ?string $period = null,
        string $comparison = 'previous',
    ): array {
        return [
            'user' => $this->getUserInfo($user),
            'performance' => $this->getPerformanceMetrics($user),
            'betslips' => $this->getBetslipManagement($user),
            'wallet' => $this->getWalletSummary($user),
            'activity' => $this->getRecentActivity($user, 15),
            'referral_card' => $this->referralService->cardFor($user),
            'contests' => $this->contestService->hostedForUser($user),
            'settlements' => $this->getSettlements($user),
            'financial' => $this->getFinancialSummary($user),
            'insights' => $this->getInsights($user),
            'follower_stats' => $this->getFollowerStats($user),
            'charts' => $this->getChartData($user),
            'notifications' => $this->getNotifications($user),
            'fee_tier' => $this->getFeeTier($user),
            'onboarding' => $this->getOnboardingState($user),
            'periods' => $this->getPeriods($user, $comparison),
            'heatmap' => $this->getHeatmap($user),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function getFeeTier(User $seller): array
    {
        $totalSales = $this->platformFeeService->getTotalSalesCount($seller);
        $currentPct = $this->platformFeeService->getFeePercentageFor($seller);
        $tiers = config('services.betslip_pirates.fee_tiers');

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

        $totalWonAmount = $wonBetslips->sum(function ($betslip) {
            return $betslip->total_odds * $betslip->price;
        });
        $totalLostAmount = $betslips->where('is_winner', false)->sum('price');
        $totalStaked = $totalWonAmount + $totalLostAmount;
        $roi = $totalStaked > 0
            ? round(($totalWonAmount / $totalStaked) * 100, 1)
            : 0;

        $recentForm = $betslips
            ->take(10)
            ->map(function ($betslip) {
                return [
                    'status' => $betslip->is_winner ? 'W' : 'L',
                    'date' => $betslip->created_at->format('Y-m-d'),
                ];
            })
            ->values()
            ->toArray();

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
            'total_betslips' => $user->betslips()->count(),
            'total_sold' => BetslipUserPurchase::where('seller_id', $user->id)
                ->whereIn('status', ['won', 'refunded', 'voided'])
                ->count(),
            'total_revenue' => $this->getTotalRevenue($user),
            'avg_price' => $this->getAveragePrice($user),
            'avg_odds' => $this->getAverageOdds($user),
            'avg_legs' => $this->getAverageLegs($user),
            'recent_form' => $recentForm,
            'win_rate_breakdown' => $winRateBreakdown,
            'series' => [
                'win_rate_7d' => $this->getWinRateSeries($user, 7),
                'revenue_7d' => $this->getRevenueSeries($user, 7),
            ],
        ];
    }

    /**
     * Get betslip management data
     */
    public function getBetslipManagement(User $user): array
    {
        $activeBetslips = $user->betslips()
            ->withCount('odds as legs')
            ->withCount('purchases as purchases_count')
            ->withCount('watchers as watch_count')
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
                    'purchases' => $betslip->purchases_count,
                    'watch_count' => (int) $betslip->watch_count,
                ];
            }),
            'total_watchers' => (int) $activeBetslips->sum('watch_count'),
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
     */
    public function getRecentActivity(User $user, int $limit = 20): array
    {
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
     * Paginated settlements list for the /seller/settlements page.
     *
     * Filter: outcome = won | refunded | voided | null.
     * Uses the same per-row mapping as getSettlements() so the dashboard
     * and the list page render identical rows.
     */
    public function paginatedSettlements(User $user, ?string $outcome = null, int $perPage = 20)
    {
        $query = $user->purchasesAsSeller()
            ->with(['betslip', 'buyer', 'transactions'])
            ->whereIn('status', ['won', 'refunded', 'voided'])
            ->orderByDesc('settled_at');

        if (in_array($outcome, ['won', 'refunded', 'voided'], true)) {
            $query->where('status', $outcome);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->through(function ($purchase) {
            $available = $purchase->transactions
                ->where('user_id', $purchase->seller_id)
                ->where('balance_type', 'available');

            $gross = (float) $available->where('type', 'payout')->sum('amount');
            $fee   = abs((float) $available->where('type', 'fee')->sum('amount'));

            return [
                'id'           => $purchase->id,
                'betslip_code' => $purchase->betslip->code ?? 'N/A',
                'outcome'      => $purchase->status,
                'buyer_name'   => $purchase->buyer->name ?? 'Unknown',
                'buyer_code'   => $purchase->buyer->code ?? null,
                'gross'        => round($gross, 2),
                'fee'          => round($fee, 2),
                'net'          => round($gross - $fee, 2),
                'settled_at'   => $purchase->settled_at?->toIso8601String(),
                'settled_ago'  => $purchase->settled_at?->diffForHumans(),
            ];
        });

        return $paginator;
    }

    /**
     * Get financial summary
     */
    public function getFinancialSummary(User $user): array
    {
        $wallet = $this->walletService->getWallet($user);

        $lifetimeGross = (float) \App\Models\Transaction::where('user_id', $user->id)
            ->where('balance_type', 'available')
            ->where('type', \App\Models\Transaction::TYPE_PAYOUT)
            ->sum('amount');

        $lifetimeFees = abs((float) \App\Models\Transaction::where('user_id', $user->id)
            ->where('balance_type', 'available')
            ->where('type', \App\Models\Transaction::TYPE_FEE)
            ->sum('amount'));

        $netEarnings = $lifetimeGross - $lifetimeFees;

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
     * Insights derived from real data only.
     *
     * Stubbed metrics (best league, best leg count, best odds range, market
     * suggestion) were removed — they always returned null and inflated the
     * payload without informing the seller. Add them back only when the
     * underlying fixtures/leagues/markets joins are wired up.
     */
    public function getInsights(User $user): array
    {
        $betslips = $user->betslips()->whereIn('status', ['settled', 'voided'])->get();

        $insights = [];

        $bestDay = $this->calculateBestDay($betslips);
        if ($bestDay) {
            $insights[] = "Your best day is {$bestDay['day']} ({$bestDay['win_rate']}% win rate)";
        }

        $streak = $this->calculateCurrentStreak($betslips);
        if ($streak && $streak['type'] === 'win') {
            $insights[] = "You're on a {$streak['count']}-win streak! 🔥";
        }

        return $insights;
    }

    /**
     * Follower statistics.
     *
     * `buyer_conversion_rate` = followers who purchased from this seller
     * in the last 30 days, as a percentage of total followers. Every
     * component of the calculation is derived from real purchase rows.
     */
    public function getFollowerStats(User $user): array
    {
        $followers = $user->followers;
        $totalFollowers = $followers->count();
        $activeBuyers = $this->getActiveBuyerCount($user);

        $recentFollowers = $followers
            ->take(5)
            ->map(function ($follower) {
                return [
                    'name' => $follower->name,
                    'avatar' => $follower->avatar ?? "https://ui-avatars.com/api/?name={$follower->name}",
                    'followed_at' => Carbon::parse($follower->pivot->followed_at)->diffForHumans(),
                    'code' => $follower->code,
                ];
            });

        return [
            'total' => $totalFollowers,
            'new_this_week' => $this->getNewFollowersThisWeek($user),
            'active_buyers' => $activeBuyers,
            'buyer_conversion_rate' => $totalFollowers > 0
                ? round(($activeBuyers / $totalFollowers) * 100, 1)
                : 0.0,
            'recent' => $recentFollowers,
        ];
    }

    /**
     * Chart data for the Analytics tab.
     *
     * `win_rate_trend` and `profit_loss` are real 30-day daily series.
     * `league_performance` returns an empty array — see getLeaguePerformance().
     */
    public function getChartData(User $user): array
    {
        return [
            'win_rate_trend' => $this->getWinRateTrend($user),
            'profit_loss' => $this->getProfitLossData($user),
            'league_performance' => $this->getLeaguePerformance($user),
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
     * Per-league win rate.
     *
     * The underlying data (which league a betslip belongs to) is not exposed
     * via the current schema — it requires a join through betslip_odds →
     * fixtures → leagues that isn't wired up. Returns an empty array so the
     * Analytics panel shows an honest "No league data yet" state instead of
     * hardcoded numbers.
     *
     * @todo Wire up once league is queryable from a betslip.
     */
    private function getLeaguePerformance(User $user): array
    {
        return [];
    }

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

    // ──────────────────────────────────────────────────────────────────
    // Onboarding
    // ──────────────────────────────────────────────────────────────────

    private function getOnboardingState(User $user): array
    {
        $hasCreatedBetslip = $user->betslips()->exists();
        $hasFirstSale      = $user->purchasesAsSeller()->exists();
        $hasHostedContest  = Contest::where('host_id', $user->id)->exists();
        $hasWithdrawn      = $user->withdraws()->exists();

        $isFirstSession = !$hasCreatedBetslip
            && !$hasFirstSale
            && !$hasHostedContest
            && !$hasWithdrawn;

        $firstSaleAt = $user->purchasesAsSeller()
            ->orderBy('created_at')
            ->value('created_at');

        return [
            'is_first_session' => $isFirstSession,
            'steps' => [
                'has_created_betslip' => (bool) $hasCreatedBetslip,
                'has_first_sale'      => (bool) $hasFirstSale,
                'has_hosted_contest'  => (bool) $hasHostedContest,
                'has_withdrawn'       => (bool) $hasWithdrawn,
            ],
            'first_sale_at' => $firstSaleAt,
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    // Sparkline series
    // ──────────────────────────────────────────────────────────────────

    private function getWinRateSeries(User $user, int $days = 7): array
    {
        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        $betslips = $user->betslips()
            ->where('created_at', '>=', $start)
            ->whereIn('status', ['settled', 'voided'])
            ->get();

        $series = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $dayBetslips = $betslips->filter(fn ($b) => $b->created_at->isSameDay($day));

            if ($dayBetslips->isEmpty()) {
                continue;
            }

            $won = $dayBetslips->where('is_winner', true)->count();
            $series[] = round(($won / $dayBetslips->count()) * 100, 1);
        }

        return $series;
    }

    private function getRevenueSeries(User $user, int $days = 7): array
    {
        $trend = $this->getRevenueTrend($user);

        return array_map(fn ($row) => (float) $row['revenue'], $trend);
    }

    // ──────────────────────────────────────────────────────────────────
    // Heat map
    // ──────────────────────────────────────────────────────────────────

    public function getHeatmap(User $user, int $days = 90): array
    {
        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        $settlements = $user->purchasesAsSeller()
            ->with('transactions')
            ->whereIn('status', ['won', 'refunded', 'voided'])
            ->whereNotNull('settled_at')
            ->where('settled_at', '>=', $start)
            ->get()
            ->groupBy(fn ($p) => $p->settled_at->format('Y-m-d'));

        $out = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayRows = $settlements->get($date, collect());

            $netEarnings = 0.0;
            $won = 0;

            foreach ($dayRows as $purchase) {
                $available = $purchase->transactions
                    ->where('user_id', $purchase->seller_id)
                    ->where('balance_type', 'available');

                $gross = (float) $available->where('type', 'payout')->sum('amount');
                $fee = abs((float) $available->where('type', 'fee')->sum('amount'));
                $netEarnings += $gross - $fee;

                if ($purchase->status === 'won') {
                    $won++;
                }
            }

            $count = $dayRows->count();
            $lost = $count - $won;

            $out[] = [
                'date' => $date,
                'value' => round($netEarnings, 2),
                'summary' => $count > 0 ? [
                    'count' => $count,
                    'won' => $won,
                    'lost' => $lost,
                ] : null,
            ];
        }

        return ['days' => $out];
    }

    // ──────────────────────────────────────────────────────────────────
    // Period aggregates
    // ──────────────────────────────────────────────────────────────────

    private const WINDOWS = ['24h', '7d', '30d', '90d', 'ytd', 'all'];

    private function getPeriods(User $user, string $comparison): array
    {
        $periods = [];

        foreach (self::WINDOWS as $window) {
            $agg = $this->aggregateForWindow($user, $window);
            $prev = $this->aggregateForPrevious($user, $window, $comparison);

            $agg['comparison'] = [
                'win_rate_delta'     => round($agg['win_rate'] - $prev['win_rate'], 1),
                'revenue_delta'      => round($agg['total_revenue'] - $prev['total_revenue'], 2),
                'total_sold_delta'   => $agg['total_sold'] - $prev['total_sold'],
                'roi_delta'          => round($agg['roi'] - $prev['roi'], 1),
                'net_earnings_delta' => round($agg['net_earnings'] - $prev['net_earnings'], 2),
            ];

            $periods[$window] = $agg;
        }

        return $periods;
    }

    private function aggregateForWindow(User $user, string $window): array
    {
        $since = $this->windowStart($window);

        return $this->aggregateSeller($user, $since, null);
    }

    private function aggregateForPrevious(User $user, string $window, string $comparison): array
    {
        if ($window === 'all') {
            return $this->emptySellerAggregate();
        }

        [$prevSince, $prevUntil] = $comparison === 'yoy'
            ? $this->yoyRange($window)
            : $this->previousRange($window);

        if (!$prevSince) {
            return $this->emptySellerAggregate();
        }

        return $this->aggregateSeller($user, $prevSince, $prevUntil);
    }

    private function windowStart(string $window): ?Carbon
    {
        $now = Carbon::now();

        return match ($window) {
            '24h' => $now->copy()->subHours(24),
            '7d'  => $now->copy()->subDays(7),
            '30d' => $now->copy()->subDays(30),
            '90d' => $now->copy()->subDays(90),
            'ytd' => $now->copy()->startOfYear(),
            default => null,
        };
    }

    private function previousRange(string $window): array
    {
        $now = Carbon::now();

        return match ($window) {
            '24h' => [$now->copy()->subHours(48), $now->copy()->subHours(24)],
            '7d'  => [$now->copy()->subDays(14), $now->copy()->subDays(7)],
            '30d' => [$now->copy()->subDays(60), $now->copy()->subDays(30)],
            '90d' => [$now->copy()->subDays(180), $now->copy()->subDays(90)],
            'ytd' => [$now->copy()->subYear()->startOfYear(), $now->copy()->startOfYear()],
            default => [null, null],
        };
    }

    private function yoyRange(string $window): array
    {
        $now = Carbon::now();
        $lastYear = $now->copy()->subYear();

        return match ($window) {
            '24h' => [$lastYear->copy()->subHours(24), $lastYear],
            '7d'  => [$lastYear->copy()->subDays(7), $lastYear],
            '30d' => [$lastYear->copy()->subDays(30), $lastYear],
            '90d' => [$lastYear->copy()->subDays(90), $lastYear],
            'ytd' => [$lastYear->copy()->startOfYear(), $lastYear->copy()->endOfYear()],
            default => [null, null],
        };
    }

    private function aggregateSeller(User $user, ?Carbon $since, ?Carbon $until): array
    {
        $betslipQuery = $user->betslips();
        if ($since) {
            $betslipQuery->where('created_at', '>=', $since);
        }
        if ($until) {
            $betslipQuery->where('created_at', '<', $until);
        }
        $betslips = $betslipQuery->get();

        $totalBetslips = $betslips->count();
        $settled = $betslips->whereIn('status', ['settled', 'voided']);
        $won = $betslips->where('is_winner', true);

        $winRate = $settled->count() > 0
            ? round(($won->count() / $settled->count()) * 100, 1)
            : 0.0;

        $wonAmount = $won->sum(fn ($b) => $b->total_odds * $b->price);
        $lostAmount = $betslips->where('is_winner', false)->sum('price');
        $totalStaked = $wonAmount + $lostAmount;
        $roi = $totalStaked > 0
            ? round(($wonAmount / $totalStaked) * 100, 1)
            : 0.0;

        $purchaseQuery = BetslipUserPurchase::where('seller_id', $user->id);
        if ($since) {
            $purchaseQuery->where('created_at', '>=', $since);
        }
        if ($until) {
            $purchaseQuery->where('created_at', '<', $until);
        }
        $purchases = $purchaseQuery->get();

        $totalSold = $purchases->whereIn('status', ['won', 'refunded', 'voided'])->count();
        $totalRevenue = (float) $purchases->where('status', 'won')->sum('purchase_price');

        $feePct = $this->platformFeeService->getFeePercentageFor($user);
        $netEarnings = round($totalRevenue * (1 - $feePct), 2);

        return [
            'win_rate' => $winRate,
            'roi' => $roi,
            'total_betslips' => $totalBetslips,
            'total_sold' => $totalSold,
            'total_revenue' => round($totalRevenue, 2),
            'net_earnings' => $netEarnings,
        ];
    }

    private function emptySellerAggregate(): array
    {
        return [
            'win_rate' => 0.0,
            'roi' => 0.0,
            'total_betslips' => 0,
            'total_sold' => 0,
            'total_revenue' => 0.0,
            'net_earnings' => 0.0,
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
        $now = Carbon::now();
        $betslips = $user->betslips()->whereIn('status', ['settled', 'voided'])->get();

        $current = $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(30));
        $previous = $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(60)->subDays(30));

        return round($current - $previous, 1);
    }

    private function getRevenueTrend(User $user): array
    {
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

    private function getNewFollowersThisWeek(User $user): int
    {
        return $user->followers()
            ->wherePivot('followed_at', '>=', Carbon::now()->subWeek())
            ->count();
    }

    /**
     * Followers who purchased one of THIS seller's betslips in the last 30
     * days. Real number, real window, real seller scope.
     */
    private function getActiveBuyerCount(User $user): int
    {
        return $user->followers()
            ->whereHas('purchasedBetslips', function ($query) use ($user) {
                $query->where('betslip_user_purchases.seller_id', $user->id)
                    ->where('betslip_user_purchases.created_at', '>=', Carbon::now()->subDays(30));
            })
            ->count();
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
        return $user->unreadNotifications()->count();
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
                ->orderBy('id', 'desc')
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
                        'created_at' => $transaction->created_at->toIso8601String(),
                    ];
                })->values()->toArray(),
        ];
    }

    /**
     * Paginated betslip list for the /seller/betslips page.
     *
     * Filter values:
     *   - 'active'   → status pending, remaining > 0
     *   - 'sold_out' → remaining = 0
     *   - 'settled'  → status settled | voided
     *   - null       → all
     */
    public function paginatedBetslips(User $user, ?string $status = null, int $perPage = 20)
    {
        $query = $user->betslips()
            ->withCount('odds as legs')
            ->withCount('purchases as purchases_count')
            ->withCount('watchers as watch_count')
            ->orderByDesc('created_at');

        match ($status) {
            'active'   => $query->where('status', 'pending')->where('remaining', '>', 0),
            'sold_out' => $query->where('remaining', 0),
            'settled'  => $query->whereIn('status', ['settled', 'voided']),
            default    => null,
        };

        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->through(function ($betslip) {
            return [
                'id'              => $betslip->id,
                'code'            => $betslip->code,
                'legs'            => (int) $betslip->legs,
                'is_winner'       => (bool) $betslip->is_winner,
                'total_odds'      => round((float) $betslip->total_odds, 2),
                'price'           => round((float) $betslip->price, 2),
                'remaining'       => $betslip->remaining,
                'status'          => $betslip->status,
                'created_at'      => $betslip->created_at->toIso8601String(),
                'is_expiring_soon' => $betslip->created_at->diffInDays(now()) >= 7,
                'purchases'       => (int) $betslip->purchases_count,
                'watch_count'     => (int) $betslip->watch_count,
            ];
        });

        return $paginator;
    }

    /**
     * Paginated followers list for the /seller/followers page.
     */
    public function paginatedFollowers(User $user, int $perPage = 20)
    {
        $paginator = $user->followers()
            ->orderByDesc('followers.followed_at')
            ->paginate($perPage);

        $paginator->through(function ($follower) {
            return [
                'id'          => $follower->id,
                'name'        => $follower->name,
                'code'        => $follower->code,
                'avatar'      => $follower->profile_picture_url
                    ?? "https://ui-avatars.com/api/?name=" . urlencode($follower->name),
                'followed_at' => $follower->pivot->followed_at
                    ? Carbon::parse($follower->pivot->followed_at)->diffForHumans()
                    : null,
            ];
        });

        return $paginator;
    }
}