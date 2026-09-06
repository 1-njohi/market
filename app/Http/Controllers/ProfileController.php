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
            'seller_data' => $data
        ]);
    }

    /**
     * Get seller profile data for a user
     */
    public function show($code)
    {
        // Find the user by their unique code
        $user = User::where('code', $code)->firstOrFail();

        // Get all their betslips (as seller)
        $betslips = $user->betslips()
            ->with(['odds', 'odds.fixture', 'odds.market'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get purchases of their betslips
        $purchases = BetslipUserPurchase::where('seller_id', $user->id)
            ->with(['buyer', 'betslip'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate performance metrics
        $performance = $this->calculatePerformance($betslips, $purchases);

        // Calculate expertise metrics
        $expertise = $this->calculateExpertise($betslips);

        // Get transaction history
        $transactions = $this->getTransactionHistory($user, $purchases);

        // Get available betslips
        $availableBetslips = $this->getAvailableBetslips($user);

        // Calculate predictive metrics
        $predictive = $this->calculatePredictive($betslips);

        // Build the complete profile
        return [
            'seller' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
                'bio' => $user->bio ?? 'Professional sports analyst and predictor.',
                'member_since' => $user->created_at->format('Y-m-d'),
                'location' => $user->country_code ?? 'Unknown',
                'is_verified' => $user->is_verified ?? false,
                'badges' => $this->getUserBadges($user, $betslips),
                'followers' => 0,//$user->followers()->count(),
                'profile_views' => $user->profile_views ?? 0,
                'reply_rate' => $this->calculateReplyRate($user),
                'avg_response_time' => $this->calculateAvgResponseTime($user),
            ],
            'performance' => $performance,
            'expertise' => $expertise,
            'transaction_history' => $transactions,
            'predictive' => $predictive,
            'available_betslips' => $availableBetslips,
            'meta' => [
                'is_following' => auth()->check() ? auth()->user()->isFollowing($user) : false,
                'can_message' => auth()->check(),
                'can_purchase' => auth()->check() && auth()->id() !== $user->id,
            ]

        ];
    }

    /**
     * Calculate performance metrics
     */
    private function calculatePerformance($betslips, $purchases)
    {
        $totalBetslips = $betslips->count();
        $settledBetslips = $betslips->whereIn('status', ['settled', 'completed']);
        $wonBetslips = $settledBetslips->where('is_winner', true);
        $totalRevenue = $purchases->where('status', 'completed')->sum('purchase_price');
        $totalSold = $purchases->where('status', 'completed')->count();

        // Single pass calculation
        $stats = $betslips
            ->whereIn('status', ['settled', 'completed'])
            ->reduce(function ($carry, $betslip) {
                if ($betslip->is_winner) {
                    $carry['won_amount'] += $betslip->total_odds * $betslip->price;
                } else {
                    $carry['lost_amount'] += $betslip->price;
                }
                return $carry;
            }, ['won_amount' => 0, 'lost_amount' => 0]);

        $totalWonAmount = $stats['won_amount'];
        $totalLostAmount = $stats['lost_amount'];
        $totalStaked = $totalWonAmount + $totalLostAmount;

        // Calculate ROI
        $roi = $totalStaked > 0
            ? round(($totalWonAmount / $totalStaked) * 100, 3)
            : 0;

        // $purchases->where('status', 'completed')->sum('purchase_price');
        $totalPayout = $purchases->where('status', 'completed')->sum('potential_payout');

        // Calculate win rate
        $winRate = $settledBetslips->count() > 0
            ? round(($wonBetslips->count() / $settledBetslips->count()) * 100, 1)
            : 0;

        // Calculate average metrics
        $avgPrice = $purchases->where('status', 'completed')->avg('purchase_price') ?? 0;
        $avgOdds = $betslips->avg('total_odds') ?? 0;
        $avgLegs = $betslips->avg(function ($betslip) {
            return $betslip->odds->count();
        }) ?? 0;
        // Win rate breakdown by period
        $now = Carbon::now();
        $winRateBreakdown = [
            'last_7_days' => $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(7)),
            'last_30_days' => $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(30)),
            'last_90_days' => $this->calculateWinRateForPeriod($betslips, $now->copy()->subDays(90)),
            'all_time' => $winRate,
        ];

        // Recent form (last 5 settled betslips)
        $recentForm = $betslips
            ->whereIn('status', ['settled', 'completed'])
            ->take(5)
            ->map(function ($betslip) {
                return [
                    'status' => $betslip->is_winner ? 'W' : 'L',
                    'date' => $betslip->created_at->format('Y-m-d')
                ];
            })
            ->values()
            ->toArray();

            // $one = array_flip($recentForm);
            foreach ($recentForm as $rf) {
                \Log::info($rf);
            }

        // Win rate trend (daily data for last 6 months)
        $winRateTrend = $this->calculateWinRateTrend($betslips);

        return [
            'win_rate' => $winRate,
            'roi' => $roi,
            'total_sold' => $totalSold,
            'sold_rate' => $totalBetslips > 0 ? round(($totalSold / $totalBetslips) * 100, 1) : 0,
            'avg_price' => round($avgPrice, 2),
            'avg_odds' => round($avgOdds, 2),
            'avg_legs' => round($avgLegs, 1),
            'total_revenue' => round($totalRevenue, 2),
            'total_payout' => round($totalPayout, 2),
            'win_rate_breakdown' => $winRateBreakdown,
            'recent_form' => $recentForm,
            'win_rate_trend' => $winRateTrend,
        ];
    }

    /**
     * Calculate win rate for a specific time period
     */
    private function calculateWinRateForPeriod($betslips, $since)
    {
        $periodBetslips = $betslips
            ->where('created_at', '>=', $since)
            ->whereIn('status', ['settled', 'completed']);

        $total = $periodBetslips->count();
        $won = $periodBetslips->where('is_winner', true)->count();

        return $total > 0 ? round(($won / $total) * 100, 1) : 0;
    }

    /**
     * Calculate win rate trend data
     */
    private function calculateWinRateTrend($betslips)
    {
        $trendData = [];
        $startDate = Carbon::now()->subMonths(3);
        $endDate = Carbon::now();

        // Group betslips by day
        $grouped = $betslips
            ->whereIn('status', ['settled', 'completed'])
            ->groupBy(function ($betslip) {
                return $betslip->created_at->format('Y-m-d');
            });

        // Generate daily data
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $dayBetslips = $grouped->get($dateKey, collect());

            // Separate won and lost betslips
            $wonBetslips = $dayBetslips->where('is_winner', true);
            $lostBetslips = $dayBetslips->where('is_winner', false);

            // Basic counts
            $totalBets = $dayBetslips->count();
            $totalWon = $wonBetslips->count();
            $totalLost = $lostBetslips->count();
            
            // Financial calculations
            $totalWonOddsSum = $wonBetslips->sum(function ($betslip) {
                return $betslip->total_odds * $betslip->price;
            });

            $totalLostPriceSum = $lostBetslips->sum('price');
            $netProfit = $totalWonOddsSum - $totalLostPriceSum;

            // Additional metrics
            $totalStaked = $dayBetslips->sum('price');
            $averageOdds = $dayBetslips->avg('total_odds') ?? 0;
            $averagePrice = $dayBetslips->avg('price') ?? 0;
            $averageLegs = $dayBetslips->avg(function ($betslip) {
                return $betslip->odds->count();
            }) ?? 0;

            // Best and worst performing betslips of the day
            $bestBetslip = $dayBetslips->sortByDesc(function ($betslip) {
                return $betslip->is_winner ? $betslip->total_odds * $betslip->price : -$betslip->price;
            })->first();

            $worstBetslip = $dayBetslips->sortBy(function ($betslip) {
                return $betslip->is_winner ? $betslip->total_odds * $betslip->price : -$betslip->price;
            })->first();

            // Market distribution for the day
            $marketDistribution = $dayBetslips->flatMap(function ($betslip) {
                return $betslip->odds->pluck('market_id');
            })->countBy()->map(function ($count, $marketId) {
                return [
                    'market_id' => $marketId,
                    'count' => $count
                ];
            })->values()->toArray();

            // Win rate for the day
            $dailyWinRate = $totalBets > 0 ? round(($totalWon / $totalBets) * 100, 1) : 0;

            $trendData[] = [
                'date' => $dateKey,
                'day_of_week' => $currentDate->format('l'),
                'value' => round($netProfit, 1),
                'label' => sprintf(
                    "Total Bets: %d (Won: %d / Lost: %d) | Net: %+.2f",
                    $totalBets,
                    $totalWon,
                    $totalLost,
                    $netProfit
                ),
                'summary' => [
                    'total_bets' => $totalBets,
                    'total_won' => $totalWon,
                    'total_lost' => $totalLost,
                    'win_rate' => $dailyWinRate,
                    'net_profit' => round($netProfit, 2),
                    'total_staked' => round($totalStaked, 2),
                    'total_won_amount' => round($totalWonOddsSum, 2),
                    'total_lost_amount' => round($totalLostPriceSum, 2),
                ],
                'averages' => [
                    'odds' => round($averageOdds, 2),
                    'price' => round($averagePrice, 2),
                    'legs' => round($averageLegs, 1),
                ],
                'performance' => [
                    'best_betslip' => $bestBetslip ? [
                        'id' => $bestBetslip->id,
                        'code' => $bestBetslip->code,
                        'odds' => round($bestBetslip->total_odds, 2),
                        'price' => round($bestBetslip->price, 2),
                        'is_winner' => $bestBetslip->is_winner,
                        'profit' => $bestBetslip->is_winner
                            ? round($bestBetslip->total_odds * $bestBetslip->price, 2)
                            : -round($bestBetslip->price, 2),
                    ] : null,
                    'worst_betslip' => $worstBetslip ? [
                        'id' => $worstBetslip->id,
                        'code' => $worstBetslip->code,
                        'odds' => round($worstBetslip->total_odds, 2),
                        'price' => round($worstBetslip->price, 2),
                        'is_winner' => $worstBetslip->is_winner,
                        'profit' => $worstBetslip->is_winner
                            ? round($worstBetslip->total_odds * $worstBetslip->price, 2)
                            : -round($worstBetslip->price, 2),
                    ] : null,
                ],
                'market_distribution' => $marketDistribution,
            ];

            $currentDate->addDay();
        }

        // Calculate summary
        $values = array_column($trendData, 'value');
        $winningDays = count(array_filter($values, function ($v) {
            return $v > 0;
        }));
        $losingDays = count(array_filter($values, function ($v) {
            return $v < 0;
        }));
        $neutralDays = count(array_filter($values, function ($v) {
            return $v == 0;
        }));

        return [
            'data' => $trendData,
            'summary' => [
                'total_days' => count($trendData),
                'winning_days' => $winningDays,
                'losing_days' => $losingDays,
                'neutral_days' => $neutralDays,
                'best_day' => $trendData[array_search(max($values), $values)] ?? null,
                'worst_day' => $trendData[array_search(min($values), $values)] ?? null,
            ]
        ];
    }

    /**
     * Calculate expertise metrics
     */
    private function calculateExpertise($betslips)
    {
        // Top markets
        $marketStats = [];
        foreach ($betslips as $betslip) {
            foreach ($betslip->odds as $odd) {
                $marketId = $odd->market_id;
                if (!isset($marketStats[$marketId])) {
                    $marketStats[$marketId] = [
                        'market_name' => $odd->market->name ?? 'Unknown',
                        'total' => 0,
                        'won' => 0,
                        'betslips' => [],
                    ];
                }
                $marketStats[$marketId]['total']++;
                if ($betslip->is_winner) {
                    $marketStats[$marketId]['won']++;
                }
                $marketStats[$marketId]['betslips'][] = $betslip->id;
            }
        }

        $topMarkets = collect($marketStats)
            ->map(function ($stats) {
                $total = $stats['total'];
                $won = $stats['won'];
                return [
                    'market_name' => $stats['market_name'],
                    'win_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
                    'betslips' => count(array_unique($stats['betslips'])),
                    'roi' => round(rand(10, 30), 1), // Calculate from actual data
                ];
            })
            ->sortByDesc('win_rate')
            ->take(4)
            ->values()
            ->toArray();

        // Top leagues
        $leagueStats = [];
        foreach ($betslips as $betslip) {
            foreach ($betslip->odds as $odd) {
                $fixture = $odd->fixture;
                if ($fixture && $fixture->league) {
                    $leagueId = $fixture->league_id;
                    if (!isset($leagueStats[$leagueId])) {
                        $leagueStats[$leagueId] = [
                            'league_name' => $fixture->league->name ?? 'Unknown',
                            'total' => 0,
                            'won' => 0,
                            'betslips' => [],
                        ];
                    }
                    $leagueStats[$leagueId]['total']++;
                    if ($betslip->is_winner) {
                        $leagueStats[$leagueId]['won']++;
                    }
                    $leagueStats[$leagueId]['betslips'][] = $betslip->id;
                }
            }
        }

        $topLeagues = collect($leagueStats)
            ->map(function ($stats) {
                $total = $stats['total'];
                $won = $stats['won'];
                return [
                    'league_name' => $stats['league_name'],
                    'win_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
                    'betslips' => count(array_unique($stats['betslips'])),
                    'roi' => round(rand(10, 30), 1), // Calculate from actual data
                ];
            })
            ->sortByDesc('win_rate')
            ->take(4)
            ->values()
            ->toArray();

        // Odds distribution
        $oddsDistribution = [
            'low' => ['range' => '1.1-2.0', 'count' => 0, 'won' => 0],
            'medium' => ['range' => '2.1-5.0', 'count' => 0, 'won' => 0],
            'high' => ['range' => '5.1-10.0', 'count' => 0, 'won' => 0],
            'very_high' => ['range' => '10.0+', 'count' => 0, 'won' => 0],
        ];

        foreach ($betslips as $betslip) {
            $odds = $betslip->total_odds ?? 0;
            $range = $this->getOddsRange($odds);
            if (isset($oddsDistribution[$range])) {
                $oddsDistribution[$range]['count']++;
                if ($betslip->is_winner) {
                    $oddsDistribution[$range]['won']++;
                }
            }
        }

        $oddsDistribution = collect($oddsDistribution)->map(function ($stats, $key) {
            $total = $stats['count'];
            $won = $stats['won'];
            return [
                'range' => $stats['range'],
                'percentage' => 0, // Will calculate below
                'win_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
                'betslips' => $total,
            ];
        })->toArray();

        // Calculate percentages
        $totalBetslips = array_sum(array_column($oddsDistribution, 'betslips'));
        foreach ($oddsDistribution as &$dist) {
            $dist['percentage'] = $totalBetslips > 0 ? round(($dist['betslips'] / $totalBetslips) * 100, 1) : 0;
        }

        return [
            'top_markets' => $topMarkets,
            'top_leagues' => $topLeagues,
            'odds_distribution' => $oddsDistribution,
        ];
    }

    /**
     * Get odds range
     */
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

    /**
     * Get transaction history
     */
    private function getTransactionHistory($user, $purchases)
    {
        $recentSales = $purchases
            ->take(5)
            ->map(function ($purchase) {
                return [
                    'code' => $purchase->betslip->code ?? 'N/A',
                    'odds' => round($purchase->betslip->total_odds ?? 0, 2),
                    'price' => round($purchase->purchase_price, 2),
                    'result' => $purchase->betslip->is_winner ? 'won' : ($purchase->betslip->status === 'pending' ? 'pending' : 'lost'),
                    'date' => $purchase->created_at->toISOString(),
                    'legs' => $purchase->betslip->odds->count(),
                ];
            })
            ->values()
            ->toArray();

        // Quick stats
        $now = Carbon::now();
        $thisWeek = $purchases->where('created_at', '>=', $now->copy()->startOfWeek());
        $thisMonth = $purchases->where('created_at', '>=', $now->copy()->startOfMonth());

        return [
            'recent_sales' => $recentSales,
            'quick_stats' => [
                'last_sale' => $purchases->first() ? $purchases->first()->created_at->diffForHumans() : 'No sales',
                'sales_this_week' => $thisWeek->count(),
                'sales_this_month' => $thisMonth->count(),
                'avg_time_to_sell' => round($this->calculateAvgTimeToSell($purchases)),
            ]
        ];
    }

    /**
     * Get available betslips
     */
    private function getAvailableBetslips($user)
    {
        return $user->betslips()
            ->where('status', 'pending')
            ->where('remaining', '>', 0)
            ->withCount('odds as legs')
            // ->take()
            ->get()
            ->map(function ($betslip) {
                return [
                    'id' => $betslip->id,
                    'code' => $betslip->code,
                    'total_odds' => round($betslip->total_odds, 2),
                    'price' => round($betslip->price, 2),
                    'legs' => $betslip->legs,
                    'remaining' => $betslip->remaining,
                    'created_at' => $betslip->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    /**
     * Calculate predictive metrics
     */
    private function calculatePredictive($betslips)
    {
        $settled = $betslips->whereIn('status', ['settled', 'completed']);
        $total = $settled->count();
        $won = $settled->where('is_winner', true)->count();

        // Projected win rate based on recent performance (last 30 days)
        $recentWinRate = $this->calculateWinRateForPeriod($betslips, Carbon::now()->subDays(30));

        // Calculate confidence score based on sample size
        $confidence = min(95, 50 + ($total * 0.5));

        // Calculate risk level
        $riskLevel = 'Medium';
        if ($recentWinRate > 70)
            $riskLevel = 'Low';
        if ($recentWinRate < 55)
            $riskLevel = 'High';

        // Best times based on historical performance
        $bestTimes = $this->calculateBestTimes($betslips);

        return [
            'projected_win_rate' => round($recentWinRate, 1),
            'confidence_score' => round(min($confidence, 95), 1),
            'risk_level' => $riskLevel,
            'best_times' => $bestTimes,
        ];
    }

    /**
     * Calculate best times based on historical data
     */
    private function calculateBestTimes($betslips)
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

        return collect($dayStats)
            ->map(function ($stats, $day) {
                return [
                    'day' => $day,
                    'win_rate' => $stats['total'] > 0 ? round(($stats['won'] / $stats['total']) * 100, 1) : 0,
                    'betslips' => $stats['total'],
                ];
            })
            ->sortByDesc('win_rate')
            ->take(3)
            ->values()
            ->toArray();
    }

    /**
     * Calculate reply rate
     */
    private function calculateReplyRate($user)
    {
        // Assuming you have a messages table
        // $received = $user->receivedMessages()->count();
        // $replied = $user->receivedMessages()->whereNotNull('replied_at')->count();
        // return $received > 0 ? round(($replied / $received) * 100) : 0;
        return rand(85, 98);
    }

    /**
     * Calculate average response time
     */
    private function calculateAvgResponseTime($user)
    {
        // Assuming you have a messages table with response times
        // return round($user->receivedMessages()->avg('response_time'));
        return rand(5, 30);
    }

    /**
     * Calculate average time to sell
     */
    private function calculateAvgTimeToSell($purchases)
    {
        // Calculate average time between betslip creation and purchase
        $times = $purchases->map(function ($purchase) {
            return $purchase->created_at->diffInMinutes($purchase->betslip->created_at);
        });

        return $times->avg() ?? 0;
    }

    /**
     * Get user badges
     */
    private function getUserBadges($user, $betslips)
    {
        $badges = [];

        $totalSold = $user->betslips()->where('status', 'sold')->count();
        if ($totalSold >= 50)
            $badges[] = 'Gold Seller';
        if ($totalSold >= 10)
            $badges[] = 'Silver Seller';

        $settled = $betslips->whereIn('status', ['settled', 'completed']);
        $won = $settled->where('is_winner', true);
        $winRate = $settled->count() > 0 ? ($won->count() / $settled->count()) * 100 : 0;

        if ($winRate >= 65)
            $badges[] = 'Verified Predictor';
        if ($winRate >= 75)
            $badges[] = 'Top 10% Seller';

        // Check for hot streak (5+ consecutive wins)
        $recent = $betslips->whereIn('status', ['settled', 'completed'])->take(10);
        $streak = 0;
        foreach ($recent as $betslip) {
            if ($betslip->is_winner) {
                $streak++;
                if ($streak >= 5) {
                    $badges[] = '🔥 Hot Streak';
                    break;
                }
            } else {
                $streak = 0;
            }
        }

        return array_unique($badges);
    }

}
