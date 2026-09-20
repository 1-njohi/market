<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class BuyerDashboardService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function getDashboardData(User $user): array
    {
        return [
            'user' => $this->getUserInfo($user),
            'performance' => $this->getPerformanceMetrics($user),
            'purchases' => $this->getPurchaseManagement($user),
            'settled_outcomes' => $this->getSettledOutcomes($user),
            'activity' => $this->getRecentActivity($user, 15),
            'financial' => $this->getFinancialSummary($user),
            'wallet' => $this->getWalletSummary($user),
            'insights' => $this->getInsights($user),
            'charts' => $this->getChartData($user),
            'quick_stats' => $this->getQuickStats($user),
            'notifications' => $this->getNotifications($user),
            'following_stats' => $this->getFollowingStats($user),
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
            fn($p) => in_array($p->status, ['won', 'refunded'], true)
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
                default => 'P',   // pending
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
     * Settled purchases for the buyer, newest first.
     *
     * Returns one row per pivot — the buyer's canonical view of "what
     * happened to my money". Both won and refunded/voided outcomes are
     * included so the buyer can see the full story.
     */
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
                    'outcome' => $purchase->status,   // won | refunded | voided
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
    /**
     * Chronological feed of everything that happened to this buyer's
     * purchases: the purchase itself and the terminal outcome.
     *
     * Refunds are NOT emitted as a separate event — the settlement event
     * carries that information in its message, and the money movement is
     * already visible in the transactions table.
     */
    public function getRecentActivity(User $user, int $limit = 20): array
    {
        // ── Purchases: betslips I bought ────────────────────────────────
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

        // ── Settlements: outcomes of my purchases ───────────────────────
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

                // status === 'refunded'
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
            'net_spent' => round($totalSpent, 2),   // = total_spent now
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
                    if ($transaction->transactionable instanceof \App\Models\BetslipUserPurchase) {
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
                        'outcome' => $outcome,   // null | won | refunded | voided | pending
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

        $totalSpent = $purchases->filter(fn($p) => $p->status !== 'refunded')->sum('purchase_price');
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

    public function getQuickStats(User $user): array
    {
        $purchases = $user->purchases;
        $wallet = $this->walletService->getWallet($user);

        return [
            'total_purchases' => $purchases->count(),
            'win_rate' => $this->calculateWinRate($purchases),
            'total_spent' => round((float) $purchases->where('status', 'won')->sum('purchase_price'), 2),
            'total_committed' => round(
                (float) $purchases->where('status', 'won')->sum('purchase_price')
                + (float) $purchases->where('status', 'pending')->sum('purchase_price'),
                2
            ),
            'total_refunded' => round((float) $purchases->whereIn('status', ['refunded', 'voided'])->sum('purchase_price'), 2),
            'balance' => (float) $wallet->balance,
            'pending_purchases' => $purchases->where('status', 'pending')->count(),
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
     * Sellers the buyer follows.
     */
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

    // ---------- Helper Methods ---------- //

    private function calculateWinRate($purchases): float
    {
        $settled = $purchases->filter(
            fn($p) => in_array($p->status, ['won', 'refunded'], true)
        );
        $won = $purchases->where('status', 'won');

        return $settled->count() > 0
            ? round(($won->count() / $settled->count()) * 100, 1)
            : 0.0;
    }

    private function calculateWinRateForPeriod($purchases, $since): float
    {
        $periodPurchases = $purchases->filter(fn($p) => $p->created_at >= $since);
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
        $statuses = $user->purchases->groupBy('status')->map(fn($group) => $group->count());

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