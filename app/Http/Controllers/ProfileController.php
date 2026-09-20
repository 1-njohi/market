<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->show($request->user_code);
        return Inertia::render('Profile', [
            'seller_data' => $data,
        ]);
    }

    public function show($code)
    {
        $user = User::where('code', $code)->firstOrFail();

        $betslips = $user->betslips()
            ->with(['odds', 'odds.fixture', 'odds.fixture.league', 'odds.market'])
            ->orderBy('created_at', 'desc')
            ->get();

        $purchases = BetslipUserPurchase::where('seller_id', $user->id)
            ->with(['buyer', 'betslip'])
            ->orderBy('created_at', 'desc')
            ->get();

        $performance = $this->calculatePerformance($betslips, $purchases);
        $expertise = $this->calculateExpertise($betslips);
        $transactions = $this->getTransactionHistory($user, $purchases);
        $availableBetslips = $this->getAvailableBetslips($user);
        $predictive = $this->calculatePredictive($betslips);

        return [
            'seller' => [
                'id' => $user->id,
                'name' => $user->name,
                'code' => $user->code,
                'avatar' => $user->profile_picture_url
                    ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
                'bio' => $user->bio ?? 'Sports analyst and betslip creator.',
                'member_since' => $user->created_at->format('Y-m-d'),
                'location' => $user->country_code ?? 'KE',
                'is_verified' => !is_null($user->email_verified_at),
                'badges' => $this->getUserBadges($user, $betslips),
                'followers' => $user->followers()->count(),
                'following' => $user->following()->count(),
                'profile_views' => $user->profile_views ?? 0,
                'rank' => $this->calculateRank($user),
                'reply_rate' => null,       // no messages table yet
                'avg_response_time' => null, // no messages table yet
            ],
            'performance' => $performance,
            'expertise' => $expertise,
            'transaction_history' => $transactions,
            'predictive' => $predictive,
            'available_betslips' => $availableBetslips,
            'meta' => [
                'is_following' => auth()->check() ? auth()->user()->isFollowing($user) : false,
                'can_message' => auth()->check() && auth()->id() !== $user->id,
                'can_purchase' => auth()->check() && auth()->id() !== $user->id,
                'is_owner' => auth()->check() && auth()->id() === $user->id,
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Performance
    // ─────────────────────────────────────────────────────────────

    private function calculatePerformance($betslips, $purchases)
    {
        $totalBetslips = $betslips->count();
        $settled = $betslips->whereIn('status', ['settled', 'voided']);
        $won = $settled->where('is_winner', true);
        $lost = $settled->where('is_winner', false);

        $totalRevenue = $purchases->whereIn('status', ['won', 'refunded'])
            ->sum('purchase_price');
        $totalSold = $purchases->whereIn('status', ['won', 'refunded'])->count();

        // ROI using the same logic as HomeController
        $wonAmount = $won->sum(fn($b) => $b->total_odds * $b->price);
        $lostAmount = $lost->sum('price');
        $staked = $wonAmount + $lostAmount;
        $roi = $staked > 0 ? round(($wonAmount / $staked) * 100, 1) : 0;

        $winRate = $settled->count() > 0
            ? round(($won->count() / $settled->count()) * 100, 1)
            : 0;

        $avgPrice = $purchases->avg('purchase_price') ?? 0;
        $avgOdds = $betslips->avg('total_odds') ?? 0;
        $avgLegs = $betslips->avg(fn($b) => $b->odds->count()) ?? 0;
        $avgStake = $betslips->avg('price') ?? 0;

        $now = Carbon::now();
        $winRateBreakdown = [
            'last_7_days' => $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(7)),
            'last_30_days' => $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(30)),
            'last_90_days' => $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(90)),
            'all_time' => $winRate,
        ];

        // Recent form — last 10 settled, newest first
        $recentForm = $settled
            ->take(10)
            ->map(fn($b) => [
                'status' => $b->is_winner ? 'W' : 'L',
                'date' => $b->created_at->format('Y-m-d'),
            ])
            ->values()
            ->toArray();

        $streak = $this->calculateCurrentStreak($settled);

        $winRateTrend = $this->calculateWinRateTrend($betslips);

        return [
            'win_rate' => $winRate,
            'roi' => $roi,
            'total_betslips' => $totalBetslips,
            'total_settled' => $settled->count(),
            'total_sold' => $totalSold,
            'sold_rate' => $totalBetslips > 0 ? round(($totalSold / $totalBetslips) * 100, 1) : 0,
            'avg_price' => round($avgPrice, 2),
            'avg_stake' => round($avgStake, 2),
            'avg_odds' => round($avgOdds, 2),
            'avg_legs' => round($avgLegs, 1),
            'total_revenue' => round($totalRevenue, 2),
            'total_won_amount' => round($wonAmount, 2),
            'total_lost_amount' => round($lostAmount, 2),
            'net_profit' => round($wonAmount - $lostAmount, 2),
            'current_streak' => $streak,
            'win_rate_breakdown' => $winRateBreakdown,
            'recent_form' => $recentForm,
            'win_rate_trend' => $winRateTrend,
        ];
    }

    private function calculateWinRateForPeriod($betslips, $since)
    {
        $period = $betslips
            ->where('created_at', '>=', $since)
            ->whereIn('status', ['settled']);

        $total = $period->count();
        $won = $period->where('is_winner', true)->count();

        return $total > 0 ? round(($won / $total) * 100, 1) : 0;
    }

    private function calculateCurrentStreak($settledBetslips): array
    {
        $streak = 0;
        $type = null;

        foreach ($settledBetslips as $betslip) {
            $isWin = (bool) $betslip->is_winner;
            if ($type === null) {
                $type = $isWin ? 'win' : 'loss';
                $streak = 1;
            } elseif (($type === 'win' && $isWin) || ($type === 'loss' && !$isWin)) {
                $streak++;
            } else {
                break;
            }
        }

        return ['count' => $streak, 'type' => $type];
    }

    /**
     * Daily aggregation over the last 90 days: value = net profit/loss that day.
     * Returns [{date, value, label, summary}]
     */
    private function calculateWinRateTrend($betslips)
    {
        $grouped = $betslips
            ->whereIn('status', ['settled'])
            ->groupBy(fn($b) => $b->created_at->format('Y-m-d'));

        $trendData = [];
        $start = Carbon::now()->subDays(89);
        $end = Carbon::now();

        for ($d = clone $start; $d <= $end; $d->addDay()) {
            $key = $d->format('Y-m-d');
            $dayBetslips = $grouped->get($key, collect());

            $won = $dayBetslips->where('is_winner', true);
            $lost = $dayBetslips->where('is_winner', false);

            $wonAmount = $won->sum(fn($b) => $b->total_odds * $b->price);
            $lostAmount = $lost->sum('price');
            $net = $wonAmount - $lostAmount;

            $trendData[] = [
                'date' => $key,
                'value' => round($net, 2),
                'label' => sprintf(
                    'Bets: %d (W:%d / L:%d) — Net KES %+.2f',
                    $dayBetslips->count(),
                    $won->count(),
                    $lost->count(),
                    $net
                ),
                'summary' => [
                    'total_bets' => $dayBetslips->count(),
                    'total_won' => $won->count(),
                    'total_lost' => $lost->count(),
                    'net_profit' => round($net, 2),
                    'total_staked' => round($dayBetslips->sum('price'), 2),
                ],
            ];
        }

        $values = array_column($trendData, 'value');

        return [
            'data' => $trendData,
            'summary' => [
                'total_days' => count($trendData),
                'winning_days' => count(array_filter($values, fn($v) => $v > 0)),
                'losing_days' => count(array_filter($values, fn($v) => $v < 0)),
                'neutral_days' => count(array_filter($values, fn($v) => $v == 0)),
                'best_day' => $trendData[array_search(max($values), $values)] ?? null,
                'worst_day' => $trendData[array_search(min($values), $values)] ?? null,
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Expertise
    // ─────────────────────────────────────────────────────────────

    private function calculateExpertise($betslips)
    {
        $settled = $betslips->whereIn('status', ['settled']);
        $won = $settled->where('is_winner', true);
        $lost = $settled->where('is_winner', false);

        // ─── Top Markets ───
        $marketStats = [];
        foreach ($settled as $betslip) {
            foreach ($betslip->odds as $odd) {
                $marketId = $odd->market_id;
                if (!isset($marketStats[$marketId])) {
                    $marketStats[$marketId] = [
                        'market_name' => $odd->market->name ?? 'Unknown',
                        'total' => 0,
                        'won' => 0,
                        'won_amount' => 0,
                        'lost_amount' => 0,
                        'betslips' => [],
                    ];
                }
                $marketStats[$marketId]['total']++;
                if ($betslip->is_winner) {
                    $marketStats[$marketId]['won']++;
                    $marketStats[$marketId]['won_amount'] += $betslip->total_odds * $betslip->price;
                } else {
                    $marketStats[$marketId]['lost_amount'] += $betslip->price;
                }
                $marketStats[$marketId]['betslips'][] = $betslip->id;
            }
        }

        $topMarkets = collect($marketStats)
            ->map(function ($s) {
                $staked = $s['won_amount'] + $s['lost_amount'];
                return [
                    'market_name' => $s['market_name'],
                    'win_rate' => $s['total'] > 0 ? round(($s['won'] / $s['total']) * 100, 1) : 0,
                    'betslips' => count(array_unique($s['betslips'])),
                    'roi' => $staked > 0 ? round(($s['won_amount'] / $staked) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('win_rate')
            ->take(4)
            ->values()
            ->toArray();

        // ─── Top Leagues ───
        $leagueStats = [];
        foreach ($settled as $betslip) {
            foreach ($betslip->odds as $odd) {
                $fixture = $odd->fixture;
                if (!$fixture || !$fixture->league)
                    continue;

                $leagueId = $fixture->league_id;
                if (!isset($leagueStats[$leagueId])) {
                    $leagueStats[$leagueId] = [
                        'league_name' => $fixture->league->name ?? 'Unknown',
                        'total' => 0,
                        'won' => 0,
                        'won_amount' => 0,
                        'lost_amount' => 0,
                        'betslips' => [],
                    ];
                }
                $leagueStats[$leagueId]['total']++;
                if ($betslip->is_winner) {
                    $leagueStats[$leagueId]['won']++;
                    $leagueStats[$leagueId]['won_amount'] += $betslip->total_odds * $betslip->price;
                } else {
                    $leagueStats[$leagueId]['lost_amount'] += $betslip->price;
                }
                $leagueStats[$leagueId]['betslips'][] = $betslip->id;
            }
        }

        $topLeagues = collect($leagueStats)
            ->map(function ($s) {
                $staked = $s['won_amount'] + $s['lost_amount'];
                return [
                    'league_name' => $s['league_name'],
                    'win_rate' => $s['total'] > 0 ? round(($s['won'] / $s['total']) * 100, 1) : 0,
                    'betslips' => count(array_unique($s['betslips'])),
                    'roi' => $staked > 0 ? round(($s['won_amount'] / $staked) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('win_rate')
            ->take(4)
            ->values()
            ->toArray();

        // ─── Odds Distribution ───
        $oddsDistribution = [
            'low' => ['range' => '1.1-2.0', 'count' => 0, 'won' => 0],
            'medium' => ['range' => '2.1-5.0', 'count' => 0, 'won' => 0],
            'high' => ['range' => '5.1-10.0', 'count' => 0, 'won' => 0],
            'very_high' => ['range' => '10.0+', 'count' => 0, 'won' => 0],
        ];

        foreach ($settled as $betslip) {
            $range = $this->getOddsRange($betslip->total_odds ?? 0);
            if (isset($oddsDistribution[$range])) {
                $oddsDistribution[$range]['count']++;
                if ($betslip->is_winner) {
                    $oddsDistribution[$range]['won']++;
                }
            }
        }

        $totalBetslips = array_sum(array_column($oddsDistribution, 'count'));

        $oddsDistribution = collect($oddsDistribution)->map(function ($s) use ($totalBetslips) {
            return [
                'range' => $s['range'],
                'percentage' => $totalBetslips > 0 ? round(($s['count'] / $totalBetslips) * 100, 1) : 0,
                'win_rate' => $s['count'] > 0 ? round(($s['won'] / $s['count']) * 100, 1) : 0,
                'betslips' => $s['count'],
            ];
        })->toArray();

        return [
            'top_markets' => $topMarkets,
            'top_leagues' => $topLeagues,
            'odds_distribution' => $oddsDistribution,
        ];
    }

    private function getOddsRange($odds)
    {
        if ($odds <= 2.0)
            return 'low';
        if ($odds <= 5.0)
            return 'medium';
        if ($odds <= 10.0)
            return 'high';
        return 'very_high';
    }

    // ─────────────────────────────────────────────────────────────
    // Transaction history, available betslips, predictive
    // ─────────────────────────────────────────────────────────────

    private function getTransactionHistory($user, $purchases)
    {
        $recentSales = $purchases->take(5)->map(function ($p) {
            return [
                'code' => $p->betslip->code ?? 'N/A',
                'odds' => round($p->betslip->total_odds ?? 0, 2),
                'price' => round($p->purchase_price, 2),
                'result' => $p->betslip->is_winner
                    ? 'won'
                    : ($p->betslip->status === 'pending' ? 'pending' : 'lost'),
                'date' => $p->created_at->toISOString(),
                'legs' => $p->betslip->odds->count(),
            ];
        })->values()->toArray();

        $now = Carbon::now();
        $thisWeek = $purchases->where('created_at', '>=', $now->copy()->startOfWeek());
        $thisMonth = $purchases->where('created_at', '>=', $now->copy()->startOfMonth());

        return [
            'recent_sales' => $recentSales,
            'quick_stats' => [
                'last_sale' => $purchases->first()
                    ? $purchases->first()->created_at->diffForHumans()
                    : 'No sales',
                'sales_this_week' => $thisWeek->count(),
                'sales_this_month' => $thisMonth->count(),
                'avg_time_to_sell' => round($this->calculateAvgTimeToSell($purchases)),
            ],
        ];
    }

    private function getAvailableBetslips($user)
    {
        return $user->betslips()
            ->where('status', 'pending')
            ->where('remaining', '>', 0)
            ->withCount('odds as legs')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'code' => $b->code,
                    'total_odds' => round($b->total_odds, 2),
                    'price' => round($b->price, 2),
                    'legs' => $b->legs,
                    'remaining' => $b->remaining,
                    'created_at' => $b->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    private function calculatePredictive($betslips)
    {
        $settled = $betslips->whereIn('status', ['settled']);
        $total = $settled->count();
        $recentWinRate = $this->calculateWinRateForPeriod($betslips, Carbon::now()->subDays(30));
        $confidence = min(95, 50 + ($total * 0.5));

        $riskLevel = 'Medium';
        if ($recentWinRate > 70)
            $riskLevel = 'Low';
        if ($recentWinRate < 55)
            $riskLevel = 'High';

        return [
            'projected_win_rate' => round($recentWinRate, 1),
            'confidence_score' => round($confidence, 1),
            'risk_level' => $riskLevel,
            'best_times' => $this->calculateBestTimes($settled),
        ];
    }

    private function calculateBestTimes($settled)
    {
        $dayStats = [];
        foreach ($settled as $b) {
            $day = $b->created_at->format('l');
            if (!isset($dayStats[$day]))
                $dayStats[$day] = ['total' => 0, 'won' => 0];
            $dayStats[$day]['total']++;
            if ($b->is_winner)
                $dayStats[$day]['won']++;
        }

        return collect($dayStats)
            ->map(fn($s, $day) => [
                'day' => $day,
                'win_rate' => $s['total'] > 0 ? round(($s['won'] / $s['total']) * 100, 1) : 0,
                'betslips' => $s['total'],
            ])
            ->sortByDesc('win_rate')
            ->take(3)
            ->values()
            ->toArray();
    }

    private function calculateAvgTimeToSell($purchases)
    {
        $times = $purchases->map(function ($p) {
            return $p->created_at->diffInMinutes($p->betslip->created_at);
        });
        return $times->avg() ?? 0;
    }

    private function calculateRank(User $user): ?int
    {
        $metric = $user->sellerMetric;
        if (!$metric)
            return null;

        return User::whereHas('sellerMetric', function ($q) use ($metric) {
            $q->where('roi', '>', $metric->roi);
        })->count() + 1;
    }

    private function getUserBadges($user, $betslips)
    {
        $badges = [];

        $totalSold = $user->betslips()->where('status', 'sold')->count();
        if ($totalSold >= 50)
            $badges[] = 'Gold Seller';
        elseif ($totalSold >= 10)
            $badges[] = 'Silver Seller';

        $settled = $betslips->whereIn('status', ['settled']);
        $won = $settled->where('is_winner', true);
        $winRate = $settled->count() > 0 ? ($won->count() / $settled->count()) * 100 : 0;

        if ($winRate >= 65)
            $badges[] = 'Verified Predictor';
        if ($winRate >= 75)
            $badges[] = 'Top 10% Seller';

        // Hot streak: 5+ consecutive wins
        $streak = 0;
        foreach ($settled as $b) {
            if ($b->is_winner) {
                if (++$streak >= 5) {
                    $badges[] = '🔥 Hot Streak';
                    break;
                }
            } else {
                $streak = 0;
            }
        }

        return array_values(array_unique($badges));
    }
}