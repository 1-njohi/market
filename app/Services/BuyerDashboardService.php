<?php

namespace App\Services;

use App\Models\BetslipUserPurchase;
use App\Models\ContestEntry;
use App\Models\User;
use Carbon\Carbon;

class BuyerDashboardService
{
    public function __construct(
        protected WalletService $walletService,
        protected WatchlistService $watchlist,
        protected ReferralService $referralService,
        protected ContestService $contestService,
    ) {
    }

    public function getDashboardData(
        User $user,
        ?string $period = null,
        string $comparison = 'previous',
    ): array {
        return [
            'user' => $this->getUserInfo($user),
            'performance' => $this->getPerformanceMetrics($user),
            'purchases' => $this->getPurchaseManagement($user),
            'settled_outcomes' => $this->getSettledOutcomes($user),
            'watch_record' => $this->watchlist->getWatchRecord($user),
            'referral_card' => $this->referralService->cardFor($user),
            'activity' => $this->getRecentActivity($user, 15),
            'financial' => $this->getFinancialSummary($user),
            'wallet' => $this->getWalletSummary($user),
            'insights' => $this->getInsights($user),
            'charts' => $this->getChartData($user),
            'notifications' => $this->getNotifications($user),
            'following_stats' => $this->getFollowingStats($user),
            'contests' => $this->contestService->enteredForUser($user),
            'onboarding' => $this->getOnboardingState($user),
            'periods' => $this->getPeriods($user, $comparison),
            'heatmap' => $this->getHeatmap($user),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function getPerformanceMetrics(User $user): array
    {
        $purchases = $user->purchases()
            ->with('betslip')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalPurchases = $purchases->count();

        $settledPurchases = $purchases->filter(
            fn ($p) => in_array($p->status, ['won', 'refunded'], true)
        );

        $wonPurchases = $purchases->where('status', 'won');
        $refundedPurchases = $purchases->whereIn('status', ['refunded', 'voided']);
        $winRate = $settledPurchases->count() > 0
            ? round(($wonPurchases->count() / $settledPurchases->count()) * 100, 1)
            : 0;
        $totalSpent = (float) $wonPurchases->sum('purchase_price');
        $totalRefunded = (float) $refundedPurchases->sum('purchase_price');
        $netSpent = $totalSpent;

        $refundedCount = $refundedPurchases->count();
        $refundRate = $totalPurchases > 0
            ? round(($refundedCount / $totalPurchases) * 100, 1)
            : 0;

        $committedPurchases = $purchases->whereIn('status', ['won', 'pending']);
        $totalCommitted = (float) $committedPurchases->sum('purchase_price');
        $avgPrice = $committedPurchases->count() > 0
            ? round($totalCommitted / $committedPurchases->count(), 2)
            : 0;

        $recentForm = $purchases->take(10)->map(function ($purchase) {
            $mark = match ($purchase->status) {
                'won' => 'W',
                'refunded' => 'L',
                'voided' => 'V',
                default => 'P',
            };

            return [
                'status' => $mark,
                'date' => $purchase->created_at->format('Y-m-d'),
            ];
        })->values()->toArray();

        $now = Carbon::now();
        $winRateBreakdown = [
            'last_7_days' => $this->calculateWinRateForPeriod($purchases, $now->copy()->subDays(7)),
            'last_30_days' => $this->calculateWinRateForPeriod($purchases, $now->copy()->subDays(30)),
            'last_90_days' => $this->calculateWinRateForPeriod($purchases, $now->copy()->subDays(90)),
            'all_time' => $winRate,
        ];

        $topSellers = $purchases
            ->groupBy('seller_id')
            ->map(function ($group) {
                $seller = $group->first()->seller;
                $total = $group->count();
                $won = $group->where('status', 'won')->count();
                $settled = $group->whereIn('status', ['won', 'refunded'])->count();
                $lastPurchase = $group->max('created_at');

                return [
                    'seller_id' => $seller->id,
                    'seller_name' => $seller->name,
                    'total_purchases' => $total,
                    'won_purchases' => $won,
                    'win_rate' => $settled > 0
                        ? round(($won / $settled) * 100, 1)
                        : 0,
                    'last_purchase_at' => $lastPurchase,
                ];
            })
            ->sortByDesc('last_purchase_at')
            ->sortByDesc('win_rate')
            ->take(5)
            ->values()
            ->toArray();

        return [
            'win_rate' => $winRate,
            'total_purchases' => $totalPurchases,
            'total_spent' => round($totalSpent, 2),
            'total_refunded' => round($totalRefunded, 2),
            'refund_rate' => $refundRate,
            'net_spent' => round($netSpent, 2),
            'avg_price' => $avgPrice,
            'recent_form' => $recentForm,
            'win_rate_breakdown' => $winRateBreakdown,
            'top_sellers' => $topSellers,
            'series' => [
                'win_rate_7d' => $this->getWinRateSeries($user, 7),
                'spent_7d' => $this->getSpentSeries($user, 7),
            ],
        ];
    }

    public function getPurchaseManagement(User $user): array
    {
        $purchases = $user->purchases()
            ->with(['betslip', 'betslip.seller', 'seller'])
            ->orderBy('created_at', 'desc')
            ->get();

        $pending = $purchases->where('status', 'pending');
        $active = $purchases->where('status', 'pending');
        $settled = $purchases->whereIn('status', ['won', 'refunded', 'voided']);

        return [
            'total' => $purchases->count(),
            'pending' => $pending->count(),
            'active' => $active->count(),
            'settled' => $settled->count(),
            'recent' => $purchases->take(10)->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'betslip_code' => $purchase->betslip->code ?? 'N/A',
                    'seller_name' => $purchase->seller->name ?? 'Unknown',
                    'price' => round($purchase->purchase_price, 2),
                    'total_odds' => round($purchase->total_odds, 2),
                    'status' => $purchase->status,
                    'is_winner' => (bool) ($purchase->betslip->is_winner ?? false),
                    'legs' => $purchase->betslip->odds->count() ?? 0,
                    'purchased_at' => $purchase->created_at->toISOString(),
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Paginated purchase list for the /buyer/purchases page.
     *
     * Filter values:
     *   - 'active'   → pending
     *   - 'settled'  → won | refunded | voided
     *   - 'won'      → won
     *   - 'refunded' → refunded
     *   - 'voided'   → voided
     *   - null       → all
     */
    public function paginatedPurchases(User $user, ?string $status = null, int $perPage = 20)
    {
        $query = $user->purchases()
            ->with(['betslip', 'seller'])
            ->orderByDesc('created_at');

        match ($status) {
            'active'   => $query->where('status', 'pending'),
            'settled'  => $query->whereIn('status', ['won', 'refunded', 'voided']),
            'won'      => $query->where('status', 'won'),
            'refunded' => $query->where('status', 'refunded'),
            'voided'   => $query->where('status', 'voided'),
            default    => null,
        };

        $paginator = $query->paginate($perPage)->withQueryString();

        // Reshape each row to the frontend contract (same shape as `recent`).
        $paginator->through(function ($purchase) {
            return [
                'id'           => $purchase->id,
                'betslip_code' => $purchase->betslip->code ?? 'N/A',
                'seller_name'  => $purchase->seller->name ?? 'Unknown',
                'price'        => round((float) $purchase->purchase_price, 2),
                'total_odds'   => round((float) $purchase->total_odds, 2),
                'status'       => $purchase->status,
                'is_winner'    => (bool) ($purchase->betslip->is_winner ?? false),
                'legs'         => $purchase->betslip->odds->count() ?? 0,
                'purchased_at' => $purchase->created_at->toIso8601String(),
            ];
        });

        return $paginator;
    }

    public function getSettledOutcomes(User $user, int $limit = 20): array
    {
        return $user->purchases()
            ->with(['betslip', 'seller'])
            ->whereIn('status', ['won', 'refunded', 'voided'])
            ->orderByDesc('settled_at')
            ->take($limit)
            ->get()
            ->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'betslip_code' => $purchase->betslip->code ?? 'N/A',
                    'outcome' => $purchase->status,
                    'price' => (float) $purchase->purchase_price,
                    'seller_name' => $purchase->seller->name ?? 'Unknown',
                    'seller_code' => $purchase->seller->code ?? null,
                    'settled_at' => $purchase->settled_at?->toISOString(),
                    'settled_ago' => $purchase->settled_at?->diffForHumans(),
                ];
            })
            ->values()
            ->toArray();
    }

    public function getRecentActivity(User $user, int $limit = 20): array
    {
        $purchases = $user->purchases()
            ->with(['betslip', 'seller'])
            ->orderByDesc('created_at')
            ->take($limit)
            ->get()
            ->map(function ($purchase) {
                return [
                    'kind' => 'purchase',
                    'badge' => 'P',
                    'tone' => 'negative',
                    'message' => "Purchased #{$purchase->betslip->code} from {$purchase->seller->name}",
                    'amount' => (float) $purchase->purchase_price,
                    'created_at' => $purchase->created_at->toISOString(),
                    'time_ago' => $purchase->created_at->diffForHumans(),
                ];
            });

        $settlements = $user->purchases()
            ->with('betslip')
            ->whereIn('status', ['won', 'refunded', 'voided'])
            ->whereNotNull('settled_at')
            ->orderByDesc('settled_at')
            ->take($limit)
            ->get()
            ->map(function ($purchase) {
                $code = $purchase->betslip->code ?? 'N/A';
                $price = number_format((float) $purchase->purchase_price, 2);

                if ($purchase->status === 'won') {
                    return [
                        'kind' => 'settlement_won',
                        'badge' => 'W',
                        'tone' => 'positive',
                        'message' => "#{$code} won — you kept the tip",
                        'amount' => (float) $purchase->purchase_price,
                        'created_at' => $purchase->settled_at->toISOString(),
                        'time_ago' => $purchase->settled_at->diffForHumans(),
                    ];
                }

                if ($purchase->status === 'voided') {
                    return [
                        'kind' => 'settlement_voided',
                        'badge' => 'V',
                        'tone' => 'neutral',
                        'message' => "#{$code} voided — KES {$price} refunded",
                        'amount' => (float) $purchase->purchase_price,
                        'created_at' => $purchase->settled_at->toISOString(),
                        'time_ago' => $purchase->settled_at->diffForHumans(),
                    ];
                }

                return [
                    'kind' => 'settlement_lost',
                    'badge' => 'L',
                    'tone' => 'neutral',
                    'message' => "#{$code} lost — KES {$price} refunded",
                    'amount' => (float) $purchase->purchase_price,
                    'created_at' => $purchase->settled_at->toISOString(),
                    'time_ago' => $purchase->settled_at->diffForHumans(),
                ];
            });

        return $purchases
            ->concat($settlements)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->all();
    }

    public function getFinancialSummary(User $user): array
    {
        $wallet = $this->walletService->getWallet($user);
        $purchases = $user->purchases;

        $won = $purchases->where('status', 'won');
        $pending = $purchases->where('status', 'pending');
        $refunded = $purchases->whereIn('status', ['refunded', 'voided']);

        $totalSpent = (float) $won->sum('purchase_price');
        $totalCommitted = (float) $won->sum('purchase_price')
            + (float) $pending->sum('purchase_price');
        $escrowed = (float) $pending->sum('purchase_price');
        $totalRefunded = (float) $refunded->sum('purchase_price');

        return [
            'balance' => (float) $wallet->balance,
            'escrow_balance' => (float) $wallet->escrow_balance,
            'total_spent' => round($totalSpent, 2),
            'total_committed' => round($totalCommitted, 2),
            'escrowed' => round($escrowed, 2),
            'total_refunded' => round($totalRefunded, 2),
            'net_spent' => round($totalSpent, 2),
            'total_deposited' => (float) $wallet->total_deposited,
            'total_withdrawn' => (float) $wallet->total_withdrawn,
        ];
    }

    public function getWalletSummary(User $user): array
    {
        $wallet = $this->walletService->getWallet($user);

        return [
            'balance' => (float) $wallet->balance,
            'escrow_balance' => (float) $wallet->escrow_balance,
            'total_deposited' => (float) $wallet->total_deposited,
            'total_withdrawn' => (float) $wallet->total_withdrawn,
            'currency' => $wallet->currency ?? 'KES',
            'recent_transactions' => $user->transactions()
                ->with('transactionable')
                ->where('balance_type', 'available')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get()
                ->map(function ($transaction) {
                    $outcome = null;
                    if ($transaction->transactionable instanceof BetslipUserPurchase) {
                        $outcome = $transaction->transactionable->status;
                    }

                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => (float) $transaction->amount,
                        'balance_before' => (float) $transaction->balance_before,
                        'balance_after' => (float) $transaction->balance_after,
                        'description' => $transaction->description,
                        'status' => $transaction->status,
                        'outcome' => $outcome,
                        'created_at' => $transaction->created_at->toISOString(),
                    ];
                })->values()->toArray(),
        ];
    }

    public function getInsights(User $user): array
    {
        $insights = [];
        $purchases = $user->purchases;

        $bestSeller = $purchases
            ->groupBy('seller_id')
            ->map(function ($group) {
                $total = $group->count();
                $won = $group->where('status', 'won')->count();
                $settled = $group->whereIn('status', ['won', 'refunded'])->count();
                return [
                    'seller_name' => $group->first()->seller->name,
                    'win_rate' => $settled > 0 ? round(($won / $settled) * 100, 1) : 0,
                    'total' => $total,
                ];
            })
            ->sortByDesc('win_rate')
            ->first();

        if ($bestSeller) {
            $insights[] = "Your best performing seller is {$bestSeller['seller_name']} ({$bestSeller['win_rate']}% win rate).";
        }

        $mostPurchasedSeller = $purchases
            ->groupBy('seller_id')
            ->map(function ($group) {
                return [
                    'seller_name' => $group->first()->seller->name,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->first();

        if ($mostPurchasedSeller) {
            $insights[] = "You have purchased the most betslips from {$mostPurchasedSeller['seller_name']} ({$mostPurchasedSeller['count']} purchases).";
        }

        $totalSpent = $purchases->filter(fn ($p) => $p->status !== 'refunded')->sum('purchase_price');
        $avgPrice = $purchases->count() > 0 ? round($totalSpent / $purchases->count(), 2) : 0;

        if ($avgPrice > 0) {
            $insights[] = "Your average purchase price is KSH {$avgPrice}.";
        }

        $refunded = $purchases->where('status', 'refunded')->count();
        $totalPurchases = $purchases->count();
        if ($totalPurchases > 0 && $refunded > 0) {
            $refundRate = round(($refunded / $totalPurchases) * 100, 1);
            $insights[] = "Your refund rate is {$refundRate}%.";
        }

        return $insights;
    }

    public function getChartData(User $user): array
    {
        return [
            'purchase_trend' => $this->getPurchaseTrend($user),
            'spending_trend' => $this->getSpendingTrend($user),
            'status_distribution' => $this->getStatusDistribution($user),
            'seller_performance' => $this->getSellerPerformance($user),
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

    /**
     * Paginated list of sellers the buyer follows, newest first.
     */
    public function paginatedFollowing(User $user, int $perPage = 20)
    {
        $paginator = $user->following()
            ->orderByDesc('followers.followed_at')
            ->paginate($perPage);

        $paginator->through(function ($seller) {
            return [
                'id'          => $seller->id,
                'name'        => $seller->name,
                'code'        => $seller->code,
                'avatar'      => $seller->profile_picture_url
                    ?? "https://ui-avatars.com/api/?name=" . urlencode($seller->name),
                'followed_at' => $seller->pivot->followed_at
                    ? \Carbon\Carbon::parse($seller->pivot->followed_at)->diffForHumans()
                    : null,
            ];
        });

        return $paginator;
    }

    public function getFollowingStats(User $user): array
    {
        $following = $user->following()
            ->orderBy('followers.followed_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($seller) {
                return [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'code' => $seller->code,
                    'avatar' => $seller->profile_picture_url
                        ?? "https://ui-avatars.com/api/?name=" . urlencode($seller->name),
                    'followed_at' => $seller->pivot->followed_at
                        ? Carbon::parse($seller->pivot->followed_at)->diffForHumans()
                        : null,
                ];
            })
            ->values()
            ->toArray();

        return [
            'total' => $user->following()->count(),
            'recent' => $following,
        ];
    }

    // ---------------------------------------------------------------------
    // Onboarding
    // ---------------------------------------------------------------------

    private function getOnboardingState(User $user): array
    {
        $hasPurchased     = $user->purchases()->exists();
        $hasWatched       = $user->watchedBetslips()->exists();
        $hasFollowed      = $user->following()->exists();
        $hasJoinedContest = ContestEntry::where('user_id', $user->id)->exists();

        $isFirstSession = !$hasPurchased
            && !$hasWatched
            && !$hasFollowed
            && !$hasJoinedContest;

        $firstDepositAmount = $user->Deposits()
            ->orderBy('created_at')
            ->value('amount');

        return [
            'is_first_session' => $isFirstSession,
            'steps' => [
                'has_watched'        => (bool) $hasWatched,
                'has_purchased'      => (bool) $hasPurchased,
                'has_followed'       => (bool) $hasFollowed,
                'has_joined_contest' => (bool) $hasJoinedContest,
            ],
            'first_deposit_amount' => $firstDepositAmount !== null
                ? (float) $firstDepositAmount
                : null,
        ];
    }

    // ---------------------------------------------------------------------
    // Sparkline series
    // ---------------------------------------------------------------------

    private function getWinRateSeries(User $user, int $days = 7): array
    {
        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        $purchases = $user->purchases()
            ->where('created_at', '>=', $start)
            ->whereIn('status', ['won', 'refunded', 'voided'])
            ->get();

        $series = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $dayPurchases = $purchases->filter(fn ($p) => $p->created_at->isSameDay($day));

            if ($dayPurchases->isEmpty()) {
                continue;
            }

            $won = $dayPurchases->where('status', 'won')->count();
            $series[] = round(($won / $dayPurchases->count()) * 100, 1);
        }

        return $series;
    }

    private function getSpentSeries(User $user, int $days = 7): array
    {
        $series = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();

            $spent = $user->purchases()
                ->whereDate('created_at', $date)
                ->where('status', 'won')
                ->sum('purchase_price');

            $series[] = round((float) $spent, 2);
        }

        return $series;
    }

    // ---------------------------------------------------------------------
    // Period aggregates
    // ---------------------------------------------------------------------

    private const WINDOWS = ['24h', '7d', '30d', '90d', 'ytd', 'all'];

    private function getPeriods(User $user, string $comparison): array
    {
        $periods = [];

        foreach (self::WINDOWS as $window) {
            $agg = $this->aggregateForWindow($user, $window);
            $prev = $this->aggregateForPrevious($user, $window, $comparison);

            $agg['comparison'] = [
                'win_rate_delta'        => round($agg['win_rate'] - $prev['win_rate'], 1),
                'total_spent_delta'     => round($agg['total_spent'] - $prev['total_spent'], 2),
                'total_purchases_delta' => $agg['total_purchases'] - $prev['total_purchases'],
                'refund_rate_delta'     => round($agg['refund_rate'] - $prev['refund_rate'], 1),
                'avg_price_delta'       => round($agg['avg_price'] - $prev['avg_price'], 2),
            ];

            $periods[$window] = $agg;
        }

        return $periods;
    }

    private function aggregateForWindow(User $user, string $window): array
    {
        $since = $this->windowStart($window);

        return $this->aggregatePurchases($user, $since, null);
    }

    private function aggregateForPrevious(User $user, string $window, string $comparison): array
    {
        if ($window === 'all') {
            return $this->emptyAggregate();
        }

        [$prevSince, $prevUntil] = $comparison === 'yoy'
            ? $this->yoyRange($window)
            : $this->previousRange($window);

        if (!$prevSince) {
            return $this->emptyAggregate();
        }

        return $this->aggregatePurchases($user, $prevSince, $prevUntil);
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
            'all' => null,
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

    private function aggregatePurchases(User $user, ?Carbon $since, ?Carbon $until): array
    {
        $query = $user->purchases();

        if ($since) {
            $query->where('created_at', '>=', $since);
        }
        if ($until) {
            $query->where('created_at', '<', $until);
        }

        $purchases = $query->get();

        $total = $purchases->count();
        $won = $purchases->where('status', 'won');
        $settled = $purchases->filter(
            fn ($p) => in_array($p->status, ['won', 'refunded'], true)
        );
        $refunded = $purchases->whereIn('status', ['refunded', 'voided']);
        $committed = $purchases->whereIn('status', ['won', 'pending']);

        $winRate = $settled->count() > 0
            ? round(($won->count() / $settled->count()) * 100, 1)
            : 0.0;

        $totalSpent = (float) $won->sum('purchase_price');

        $refundRate = $total > 0
            ? round(($refunded->count() / $total) * 100, 1)
            : 0.0;

        $avgPrice = $committed->count() > 0
            ? round((float) $committed->sum('purchase_price') / $committed->count(), 2)
            : 0.0;

        return [
            'win_rate' => $winRate,
            'total_purchases' => $total,
            'total_spent' => round($totalSpent, 2),
            'net_spent' => round($totalSpent, 2),
            'refund_rate' => $refundRate,
            'avg_price' => $avgPrice,
        ];
    }

    private function emptyAggregate(): array
    {
        return [
            'win_rate' => 0.0,
            'total_purchases' => 0,
            'total_spent' => 0.0,
            'net_spent' => 0.0,
            'refund_rate' => 0.0,
            'avg_price' => 0.0,
        ];
    }

    /**
     * Daily net tip outcomes for the last $days days.
     *
     * value   = (tips won that day) − (tips lost that day)
     * summary = { count, won, lost } for the day, or null if no activity.
     *
     * Days are emitted oldest-first so the frontend renders them left-to-right
     * without reversing. Days with no settled purchases contribute value 0
     * and summary null.
     */
    public function getHeatmap(User $user, int $days = 90): array
    {
        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        $purchases = $user->purchases()
            ->whereIn('status', ['won', 'refunded', 'voided'])
            ->whereNotNull('settled_at')
            ->where('settled_at', '>=', $start)
            ->get()
            ->groupBy(fn ($p) => $p->settled_at->format('Y-m-d'));

        $out = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayPurchases = $purchases->get($date, collect());

            $won = $dayPurchases->where('status', 'won')->count();
            $lost = $dayPurchases->whereIn('status', ['refunded', 'voided'])->count();
            $count = $dayPurchases->count();

            $out[] = [
                'date' => $date,
                'value' => $won - $lost,
                'summary' => $count > 0 ? [
                    'count' => $count,
                    'won' => $won,
                    'lost' => $lost,
                ] : null,
            ];
        }

        return ['days' => $out];
    }

    // ---------------------------------------------------------------------
    // Legacy helpers
    // ---------------------------------------------------------------------

    private function calculateWinRate($purchases): float
    {
        $settled = $purchases->filter(
            fn ($p) => in_array($p->status, ['won', 'refunded'], true)
        );
        $won = $purchases->where('status', 'won');

        return $settled->count() > 0
            ? round(($won->count() / $settled->count()) * 100, 1)
            : 0.0;
    }

    private function calculateWinRateForPeriod($purchases, $since): float
    {
        $periodPurchases = $purchases->filter(fn ($p) => $p->created_at >= $since);
        return $this->calculateWinRate($periodPurchases);
    }

    private function getUserInfo(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->profile_picture_url
                ?? "https://ui-avatars.com/api/?name=" . urlencode($user->name),
            'email' => $user->email,
            'code' => $user->code,
            'is_verified' => (bool) ($user->email_verified_at ?? false),
            'member_since' => $user->created_at->format('F Y'),
        ];
    }

    private function getPurchaseTrend(User $user): array
    {
        $data = [];
        $counts = $user->purchases()
            ->where('created_at', '>=', Carbon::now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $key = $date->format('Y-m-d');
            $data[] = ['date' => $key, 'purchases' => (int) ($counts[$key] ?? 0)];
        }

        return $data;
    }

    private function getSpendingTrend(User $user): array
    {
        $data = [];
        $spentByDay = $user->purchases()
            ->where('created_at', '>=', Carbon::now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as date, SUM(purchase_price) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $key = $date->format('Y-m-d');
            $data[] = ['date' => $key, 'spent' => round((float) ($spentByDay[$key] ?? 0), 2)];
        }

        return $data;
    }

    private function getStatusDistribution(User $user): array
    {
        $statuses = $user->purchases->groupBy('status')->map(fn ($group) => $group->count());

        return [
            ['status' => 'Pending', 'count' => $statuses->get('pending', 0)],
            ['status' => 'Won', 'count' => $statuses->get('won', 0)],
            ['status' => 'Refunded', 'count' => $statuses->get('refunded', 0)],
            ['status' => 'Voided', 'count' => $statuses->get('voided', 0)],
        ];
    }

    private function getSellerPerformance(User $user): array
    {
        $purchases = $user->purchases;

        return $purchases->groupBy('seller_id')->map(function ($group) {
            $seller = $group->first()->seller;
            $total = $group->count();
            $won = $group->where('status', 'won')->count();
            $settled = $group->whereIn('status', ['won', 'refunded'])->count();

            return [
                'seller_name' => $seller->name,
                'total' => $total,
                'won' => $won,
                'win_rate' => $settled > 0
                    ? round(($won / $settled) * 100, 1)
                    : 0,
            ];
        })->sortByDesc('win_rate')->take(5)->values()->toArray();
    }
}