<?php

namespace App\Services;

use App\Models\User;
use App\Models\BetslipUserPurchase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BuyerDashboardService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get complete dashboard data for a buyer
     */
    public function getDashboardData(User $user): array
    {
        return [
            'user' => $this->getUserInfo($user),
            'performance' => $this->getPerformanceMetrics($user),
            'purchases' => $this->getPurchaseManagement($user),
            'activity' => $this->getRecentActivity($user, 15),
            'financial' => $this->getFinancialSummary($user),
            'wallet' => $this->getWalletSummary($user),
            'insights' => $this->getInsights($user),
            'charts' => $this->getChartData($user),
            'quick_stats' => $this->getQuickStats($user),
            'notifications' => $this->getNotifications($user),
        ];
    }

    /**
     * Get performance metrics for a buyer
     */
    public function getPerformanceMetrics(User $user): array
    {
        $purchases = $user->purchases()->with('betslip')->get();
        $totalPurchases = $purchases->count();
        $settledPurchases = $purchases->filter(function ($p) {
            return in_array($p->status, ['won', 'refunded', 'completed']);
        });
        $wonPurchases = $purchases->filter(function ($p) {
            return $p->status === 'won';
        });
        $refundedPurchases = $purchases->filter(function ($p) {
            return $p->status === 'refunded';
        });

        $winRate = $settledPurchases->count() > 0
            ? round(($wonPurchases->count() / $settledPurchases->count()) * 100, 1)
            : 0;

        // Total spent (on completed purchases, not refunded)
        $totalSpent = $purchases->filter(function ($p) {
            return $p->status !== 'refunded';
        })->sum('purchase_price');

        // Total refunded
        $totalRefunded = $refundedPurchases->sum('purchase_price');

        // Net spent = total spent - total refunded
        $netSpent = $totalSpent - $totalRefunded;

        // Average price per purchase
        $avgPrice = $totalPurchases > 0 ? round($totalSpent / $totalPurchases, 2) : 0;

        // Recent form (last 10 purchases statuses)
        $recentForm = $purchases
            ->take(10)
            ->map(function ($purchase) {
                return [
                    'status' => $purchase->status === 'won' ? 'W' : ($purchase->status === 'refunded' ? 'L' : 'P'),
                    'date' => $purchase->created_at->format('Y-m-d'),
                ];
            })
            ->values()
            ->toArray();

        // Win rate breakdown by period
        $now = Carbon::now();
        $winRateBreakdown = [
            'last_7_days' => $this->calculateWinRateForPeriod($purchases, $now->copy()->subDays(7)),
            'last_30_days' => $this->calculateWinRateForPeriod($purchases, $now->copy()->subDays(30)),
            'last_90_days' => $this->calculateWinRateForPeriod($purchases, $now->copy()->subDays(90)),
            'all_time' => $winRate,
        ];

        // Top sellers (by purchases and win rate)
        $topSellers = $purchases
            ->groupBy('seller_id')
            ->map(function ($group) {
                $seller = $group->first()->seller;
                $total = $group->count();
                $won = $group->where('status', 'won')->count();
                return [
                    'seller_id' => $seller->id,
                    'seller_name' => $seller->name,
                    'total_purchases' => $total,
                    'won_purchases' => $won,
                    'win_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('win_rate')
            ->take(5)
            ->values()
            ->toArray();

        return [
            'win_rate' => $winRate,
            'total_purchases' => $totalPurchases,
            'total_spent' => round($totalSpent, 2),
            'total_refunded' => round($totalRefunded, 2),
            'net_spent' => round($netSpent, 2),
            'avg_price' => $avgPrice,
            'recent_form' => $recentForm,
            'win_rate_breakdown' => $winRateBreakdown,
            'top_sellers' => $topSellers,
        ];
    }

    /**
     * Get purchase management (active/pending purchases)
     */
    public function getPurchaseManagement(User $user): array
    {
        $purchases = $user->purchases()
            ->with(['betslip', 'betslip.seller'])
            ->orderBy('created_at', 'desc')
            ->get();

        $pending = $purchases->where('status', 'pending');
        $active = $purchases->whereIn('status', ['pending', 'completed']);
        $settled = $purchases->whereIn('status', ['won', 'refunded']);

        return [
            'total' => $purchases->count(),
            'pending' => $pending->count(),
            'active' => $active->count(),
            'settled' => $settled->count(),
            'recent' => $purchases->take(10)->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'betslip_code' => $purchase->betslip->code,
                    'seller_name' => $purchase->seller->name,
                    'price' => round($purchase->purchase_price, 2),
                    'total_odds' => round($purchase->total_odds, 2),
                    'status' => $purchase->status,
                    'purchased_at' => $purchase->created_at->toISOString(),
                ];
            }),
        ];
    }

    /**
     * Get recent activity (purchases, refunds, etc.)
     */
    public function getRecentActivity(User $user, int $limit = 20): array
    {
        $activities = collect();

        // Purchases
        $purchases = $user->purchases()
            ->with(['betslip', 'seller'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($purchase) {
                return [
                    'type' => 'purchase',
                    'message' => "Purchased #{$purchase->betslip->code} from {$purchase->seller->name}",
                    'amount' => $purchase->purchase_price,
                    'status' => $purchase->status,
                    'created_at' => $purchase->created_at->toISOString(),
                    'time_ago' => $purchase->created_at->diffForHumans(),
                ];
            });

        // Refunds (if any)
        $refunds = $user->transactions()
            ->where('type', 'refund')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'type' => 'refund',
                    'message' => "Refund of {$transaction->amount} for {$transaction->description}",
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at->toISOString(),
                    'time_ago' => $transaction->created_at->diffForHumans(),
                ];
            });

        $activities = $purchases->concat($refunds)
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
        $wallet = $this->walletService->getWallet($user);
        $purchases = $user->purchases;

        $totalSpent = $purchases->filter(function ($p) {
            return $p->status !== 'refunded';
        })->sum('purchase_price');

        $totalRefunded = $purchases->filter(function ($p) {
            return $p->status === 'refunded';
        })->sum('purchase_price');

        $netSpent = $totalSpent - $totalRefunded;

        return [
            'balance' => $wallet->balance,
            'pending_balance' => $wallet->pending_balance,
            'total_spent' => round($totalSpent, 2),
            'total_refunded' => round($totalRefunded, 2),
            'net_spent' => round($netSpent, 2),
            'total_deposited' => $wallet->total_deposited,
            'total_withdrawn' => $wallet->total_withdrawn,
        ];
    }

    /**
     * Get wallet summary
     */
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

    /**
     * Get insights for the buyer
     */
    public function getInsights(User $user): array
    {
        $insights = [];
        $purchases = $user->purchases;

        // Best performing seller
        $bestSeller = $purchases
            ->groupBy('seller_id')
            ->map(function ($group) {
                $total = $group->count();
                $won = $group->where('status', 'won')->count();
                return [
                    'seller_name' => $group->first()->seller->name,
                    'win_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
                    'total' => $total,
                ];
            })
            ->sortByDesc('win_rate')
            ->first();

        if ($bestSeller) {
            $insights[] = "Your best performing seller is {$bestSeller['seller_name']} ({$bestSeller['win_rate']}% win rate).";
        }

        // Most purchased seller
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

        // Spending habits
        $totalSpent = $purchases->filter(function ($p) {
            return $p->status !== 'refunded';
        })->sum('purchase_price');

        $avgPrice = $purchases->count() > 0 ? round($totalSpent / $purchases->count(), 2) : 0;

        if ($avgPrice > 0) {
            $insights[] = "Your average purchase price is KSH {$avgPrice}.";
        }

        // Refund rate
        $refunded = $purchases->where('status', 'refunded')->count();
        $totalPurchases = $purchases->count();
        if ($totalPurchases > 0 && $refunded > 0) {
            $refundRate = round(($refunded / $totalPurchases) * 100, 1);
            $insights[] = "Your refund rate is {$refundRate}%.";
        }

        return $insights;
    }

    /**
     * Get chart data (trends)
     */
    public function getChartData(User $user): array
    {
        return [
            'purchase_trend' => $this->getPurchaseTrend($user),
            'spending_trend' => $this->getSpendingTrend($user),
            'status_distribution' => $this->getStatusDistribution($user),
            'seller_performance' => $this->getSellerPerformance($user),
        ];
    }

    /**
     * Get quick stats
     */
    public function getQuickStats(User $user): array
    {
        $purchases = $user->purchases;
        $wallet = $this->walletService->getWallet($user);

        return [
            'total_purchases' => $purchases->count(),
            'win_rate' => $this->calculateWinRate($purchases),
            'total_spent' => round($purchases->filter(function ($p) {
                return $p->status !== 'refunded';
            })->sum('purchase_price'), 2),
            'total_refunded' => round($purchases->where('status', 'refunded')->sum('purchase_price'), 2),
            'balance' => $wallet->balance,
            'pending_purchases' => $purchases->where('status', 'pending')->count(),
        ];
    }

    /**
     * Get notifications
     */
    public function getNotifications(User $user): array
    {
        // Placeholder – you can implement real notifications later
        return [
            'unread_count' => 2,
            'items' => [
                [
                    'type' => 'purchase',
                    'message' => 'Your purchase of #ABC-1234 is pending settlement.',
                    'time' => Carbon::now()->subHours(2)->diffForHumans(),
                    'read' => false,
                ],
                [
                    'type' => 'refund',
                    'message' => 'You received a refund for #XYZ-7890.',
                    'time' => Carbon::now()->subDay()->diffForHumans(),
                    'read' => true,
                ],
            ],
        ];
    }

    // ---------- Helper Methods ---------- //

    private function calculateWinRate($purchases): float
    {
        $settled = $purchases->filter(function ($p) {
            return in_array($p->status, ['won', 'refunded', 'completed']);
        });
        $won = $purchases->where('status', 'won');
        return $settled->count() > 0 ? round(($won->count() / $settled->count()) * 100, 1) : 0;
    }

    private function calculateWinRateForPeriod($purchases, $since): float
    {
        $periodPurchases = $purchases->filter(function ($p) use ($since) {
            return $p->created_at >= $since;
        });
        return $this->calculateWinRate($periodPurchases);
    }

    private function getUserInfo(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar ?? "https://ui-avatars.com/api/?name={$user->name}",
            'email' => $user->email,
            'member_since' => $user->created_at->format('F Y'),
        ];
    }

    // Chart data generators

    private function getPurchaseTrend(User $user): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = $user->purchases()
                ->whereDate('created_at', $date->toDateString())
                ->count();
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'purchases' => $count,
            ];
        }
        return $data;
    }

    private function getSpendingTrend(User $user): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $spent = $user->purchases()
                ->whereDate('created_at', $date->toDateString())
                ->sum('purchase_price');
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'spent' => round($spent, 2),
            ];
        }
        return $data;
    }

    private function getStatusDistribution(User $user): array
    {
        $statuses = $user->purchases->groupBy('status')->map(function ($group) {
            return $group->count();
        });

        return [
            ['status' => 'Pending', 'count' => $statuses->get('pending', 0)],
            ['status' => 'Won', 'count' => $statuses->get('won', 0)],
            ['status' => 'Refunded', 'count' => $statuses->get('refunded', 0)],
            ['status' => 'Completed', 'count' => $statuses->get('completed', 0)],
        ];
    }

    private function getSellerPerformance(User $user): array
    {
        $purchases = $user->purchases;
        $sellers = $purchases->groupBy('seller_id')->map(function ($group) {
            $seller = $group->first()->seller;
            $total = $group->count();
            $won = $group->where('status', 'won')->count();
            return [
                'seller_name' => $seller->name,
                'total' => $total,
                'won' => $won,
                'win_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
            ];
        })->sortByDesc('win_rate')->take(5)->values()->toArray();

        return $sellers;
    }
}