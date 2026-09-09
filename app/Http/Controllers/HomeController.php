<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Betslip;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $betSlips = $this->getBetslips();

        $mappedFixtures = $this->getFixtures();
        return \Inertia\Inertia::render('Welcome', [
            'fixtures' => $mappedFixtures,
            'bet_slips' => $betSlips
        ]);
    }

    private function getFixtures()
    {
        // 1. Get the local market IDs for the markets we care about
        $marketNames = [
            'Match Winner',      // three_way
            'Double Chance',     // double_chance
            'Goals Over/Under',  // over_under
            'Both Teams Score',  // both_team_to_score
        ];

        $marketIds = \App\Models\Market::whereIn('name', $marketNames)->pluck('id')->toArray();

        // If some markets are missing, handle gracefully (maybe fallback to API IDs or skip)
        // For safety, we can map by name to ensure correct IDs.
        $marketMap = \App\Models\Market::whereIn('name', $marketNames)->get()->keyBy('name');

        // Use the actual IDs for the query
        $marketIds = $marketMap->pluck('id')->toArray();

        // 2. Eager load odds with these markets
        $fixtures = Fixture::with([
            'odds' => function ($query) use ($marketIds) {
                $query->whereIn('market_id', $marketIds);
            },
            'homeTeam',
            'awayTeam',
            'league',
            'league.Country', // if you have this relationship
        ])
            ->whereHas('odds', function ($query) use ($marketIds) {
                $query->whereIn('market_id', $marketIds);
            })
            ->whereBetween('date', ['2022-06-06 17:00:00', '2022-08-06 17:00:00'])
            ->orderBy('id', 'ASC')
            ->get();

        // 3. Build a mapping from market name to its local ID for use in keys
        //    We'll use the same mapping to generate the keys for oddsKeyed.
        //    For example: "{$marketMap['Match Winner']->id}-Home" etc.
        $marketIdMap = [
            'three_way' => $marketMap['Match Winner']->id ?? null,
            'double_chance' => $marketMap['Double Chance']->id ?? null,
            'over_under' => $marketMap['Goals Over/Under']->id ?? null,
            'both_team_to_score' => $marketMap['Both Teams Score']->id ?? null,
        ];

        // 4. Group fixtures by league_id
        $groupedFixtures = $fixtures->groupBy('league_id');

        // 5. Transform each group
        return $groupedFixtures->map(function ($leagueFixtures, $leagueId) use ($marketIdMap) {
            $league = $leagueFixtures->first()->league;

            $mappedFixtures = $leagueFixtures->map(function ($fixture) use ($marketIdMap) {
                // Key the odds by market_id and value
                $oddsKeyed = $fixture->odds->keyBy(function ($odd) {
                    return "{$odd->market_id}-{$odd->value}";
                });

                // Calculate additional markets count
                $uniqueMarketsCount = $fixture->odds->pluck('market_id')->unique()->count();
                $additionalMarketsCount = max(0, $uniqueMarketsCount - 4);

                // Helper to get odd data
                $getOdd = function ($marketId, $value) use ($oddsKeyed) {
                    $key = "{$marketId}-{$value}";
                    $odd = $oddsKeyed->get($key);
                    return $odd ? ['id' => $odd->id, 'value' => $odd->odd] : null;
                };

                return [
                    'id' => $fixture->id,
                    'id_on_api' => $fixture->id_on_api,
                    'date' => $fixture->date ? \Carbon\Carbon::parse($fixture->date)->format('d/m/y - H:i') : null,
                    'home_team' => $fixture->homeTeam->name ?? 'Unknown',
                    'away_team' => $fixture->awayTeam->name ?? 'Unknown',
                    'boosted' => false,
                    'additional_markets_count' => $additionalMarketsCount,
                    'odds' => [
                        'three_way' => [
                            'home' => $getOdd($marketIdMap['three_way'], 'Home'),
                            'draw' => $getOdd($marketIdMap['three_way'], 'Draw'),
                            'away' => $getOdd($marketIdMap['three_way'], 'Away'),
                        ],
                        'double_chance' => [
                            'one_x' => $getOdd($marketIdMap['double_chance'], 'Home/Draw'),
                            'x_two' => $getOdd($marketIdMap['double_chance'], 'Draw/Away'),
                            'one_two' => $getOdd($marketIdMap['double_chance'], 'Home/Away'),
                        ],
                        'over_under' => [
                            'over' => $getOdd($marketIdMap['over_under'], 'Over 2.5'),
                            'under' => $getOdd($marketIdMap['over_under'], 'Under 2.5'),
                        ],
                        'both_team_to_score' => [
                            'yes' => $getOdd($marketIdMap['both_team_to_score'], 'Yes'),
                            'no' => $getOdd($marketIdMap['both_team_to_score'], 'No'),
                        ],
                    ],
                ];
            });

            return [
                'name' => $league->name,
                'id' => $league->id,
                'country' => $league->Country->name ?? 'Unknown',
                'logo' => $league->logo,
                'league_id' => $leagueId,
                'fixtures' => $mappedFixtures->values()
            ];
        })->values();
    }

    // private function getFixtures()
    // {
    //     // 1. Eager load only the odds we care about to prevent the N+1 problem
    //     $marketIds = [1, 4, 7, 10];

    //     $fixtures = Fixture::with([
    //         'odds' => function ($query) use ($marketIds) {
    //             $query->whereIn('market_id', $marketIds);
    //         },
    //         'homeTeam',
    //         'awayTeam',
    //         'league'
    //     ])
    //         ->whereHas('odds', function ($query) use ($marketIds) {
    //             $query->whereIn('market_id', $marketIds);
    //         })
    //         ->whereBetween('date', ['2022-06-06 17:00:00', '2022-08-06 17:00:00'])
    //         ->orderBy('id', 'ASC')
    //         ->get();

    //     // 2. Group fixtures by league_id
    //     $groupedFixtures = $fixtures->groupBy('league_id');

    //     // 3. Transform each group into the desired structure
    //     return $groupedFixtures->map(function ($leagueFixtures, $leagueId) {
    //         // Get the league from the first fixture
    //         $league = $leagueFixtures->first()->league;

    //         // Transform fixtures within this league
    //         $mappedFixtures = $leagueFixtures->map(function ($fixture) {
    //             // Key the odds by a unique string combination
    //             $oddsKeyed = $fixture->odds->keyBy(function ($odd) {
    //                 return "{$odd->market_id}-{$odd->value}";
    //             });

    //             // Calculate additional markets count
    //             $uniqueMarketsCount = $fixture->odds->pluck('market_id')->unique()->count();
    //             $additionalMarketsCount = max(0, $uniqueMarketsCount - 4);

    //             return [
    //                 'id' => $fixture->id,
    //                 'id_on_api' => $fixture->id_on_api,
    //                 'date' => $fixture->date ? \Carbon\Carbon::parse($fixture->date)->format('d/m/y - H:i') : null,
    //                 'home_team' => $fixture->homeTeam->name ?? 'Unknown',
    //                 'away_team' => $fixture->awayTeam->name ?? 'Unknown',
    //                 'boosted' => false,
    //                 'additional_markets_count' => $additionalMarketsCount,
    //                 'odds' => [
    //                     'three_way' => [
    //                         'home' => $oddsKeyed->get('1-Home') ? [
    //                             'id' => $oddsKeyed->get('1-Home')->id,
    //                             'value' => $oddsKeyed->get('1-Home')->odd
    //                         ] : null,
    //                         'draw' => $oddsKeyed->get('1-Draw') ? [
    //                             'id' => $oddsKeyed->get('1-Draw')->id,
    //                             'value' => $oddsKeyed->get('1-Draw')->odd
    //                         ] : null,
    //                         'away' => $oddsKeyed->get('1-Away') ? [
    //                             'id' => $oddsKeyed->get('1-Away')->id,
    //                             'value' => $oddsKeyed->get('1-Away')->odd
    //                         ] : null,
    //                     ],
    //                     'double_chance' => [
    //                         'one_x' => $oddsKeyed->get('10-Home/Draw') ? [
    //                             'id' => $oddsKeyed->get('10-Home/Draw')->id,
    //                             'value' => $oddsKeyed->get('10-Home/Draw')->odd
    //                         ] : null,
    //                         'x_two' => $oddsKeyed->get('10-Draw/Away') ? [
    //                             'id' => $oddsKeyed->get('10-Draw/Away')->id,
    //                             'value' => $oddsKeyed->get('10-Draw/Away')->odd
    //                         ] : null,
    //                         'one_two' => $oddsKeyed->get('10-Home/Away') ? [
    //                             'id' => $oddsKeyed->get('10-Home/Away')->id,
    //                             'value' => $oddsKeyed->get('10-Home/Away')->odd
    //                         ] : null,
    //                     ],
    //                     'over_under' => [
    //                         'over' => $oddsKeyed->get('4-Over 2.5') ? [
    //                             'id' => $oddsKeyed->get('4-Over 2.5')->id,
    //                             'value' => $oddsKeyed->get('4-Over 2.5')->odd
    //                         ] : null,
    //                         'under' => $oddsKeyed->get('4-Under 2.5') ? [
    //                             'id' => $oddsKeyed->get('4-Under 2.5')->id,
    //                             'value' => $oddsKeyed->get('4-Under 2.5')->odd
    //                         ] : null,
    //                     ],
    //                     'both_team_to_score' => [
    //                         'yes' => $oddsKeyed->get('7-Yes') ? [
    //                             'id' => $oddsKeyed->get('7-Yes')->id,
    //                             'value' => $oddsKeyed->get('7-Yes')->odd
    //                         ] : null,
    //                         'no' => $oddsKeyed->get('7-No') ? [
    //                             'id' => $oddsKeyed->get('7-No')->id,
    //                             'value' => $oddsKeyed->get('7-No')->odd
    //                         ] : null,
    //                     ],
    //                 ],
    //             ];
    //         });

    //         return
    //             [
    //                 'name' => $league->name,
    //                 'id' => $league->id,
    //                 'country' => $league->Country->name,
    //                 'logo' => $league->logo,
    //                 'league_id' => $leagueId,
    //                 'fixtures' => $mappedFixtures->values()
    //             ];
    //     })->values(); // Reset array keys
    // }

    private function getBetslips()
    {
        // Get betslips with limited odds and a separate count
        $betslips = Betslip::with([
            'seller',
            'odds' => function ($query) {
                $query->limit(3); // Still limit for display
            },
            'odds.fixture',
            'odds.fixture.homeTeam',
            'odds.fixture.awayTeam',
            'odds.market'
        ])
            ->withCount('odds') // Add count of all odds
            ->where('status', 'pending')
            ->take(4)
            ->get();

        // Transform the collection
        return $betslips->map(function ($betslip) {
            $seller = $betslip->seller;

            $fake_caption = "Lorem ipsum dolor sit, amet consectetur adipisicing elit. Eius illo nihil, beatae, eveniet iste ducimus porro voluptatem esse quasi, error ex deserunt distinctio? Facere perferendis ullam consectetur exercitationem velit ipsum maiores autem consequuntur similique dolores iste rerum recusandae harum modi, laboriosam eius at qui quam numquam omnis architecto porro expedita!";
            return [
                'id' => $betslip->id,
                'code' => $betslip->code,
                'price' => (float) $betslip->price,
                'caption' => $betslip->caption ?? $fake_caption,
                'seller' => [
                    'name' => $seller->name ?? 'Unknown',
                    'code' => $seller->code,
                    'roi' => $this->calculateROI($seller),
                    'win_rate' => $this->calculateWinRate($seller),
                    'recent_form' => $this->getRecentForm($seller),
                ],
                'total_markets' => $betslip->odds_count, // Use the withCount result
                'legs' => $betslip->odds->take(1)->map(function ($odd) {
                    return [
                        'id' => $odd->id,
                        'league' => $odd->fixture->league->name ?? 'Unknown League',
                        'kickoff_at' => $odd->fixture->timestamp ?? now()->toISOString(),
                        'home_team' => $odd->fixture->homeTeam->name ?? 'Unknown',
                        'away_team' => $odd->fixture->awayTeam->name ?? 'Unknown',
                        'market_name' => $odd->market->name ?? 'Unknown Market',
                        'selection' => $odd->pivot->selection_value ?? $odd->value,
                        'odds' => (float) ($odd->pivot->odd_value_at_time ?? $odd->odd),
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * Get recent form for a user based on their last 5 settled betslips
     * Returns array of 'W', 'L', or 'P' (pending)
     */
    private function getRecentForm($user, $limit = 6)
    {
        // Get the last 5 betslips that are NOT pending (settled)
        $settledBetslips = $user->betslips()
            ->where('status', '!=', 'pending')
            ->where('status', '!=', 'underway')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        // Map to 'W' or 'L' based on is_winner
        $results = $settledBetslips->map(function ($betslip) {
            return $betslip->is_winner ? 'W' : 'L';
        })->toArray();

        // If we have fewer than $limit results, pad the beginning with 'P'
        while (count($results) < $limit) {
            array_unshift($results, 'P');
        }

        return $results;
    }

    private function calculateROI($user)
    {
        $totalStaked = $user->betslips()->sum('price');
        $totalWon = $user->betslips()->where('is_winner', true)->sum('price');
        return $totalStaked > 0 ? round(($totalWon / $totalStaked) * 100, 1) : 0;
    }

    private function calculateWinRate($user)
    {
        $totalBetslips = $user->betslips()->where('status', '!=', 'pending')->count();
        $wonBetslips = $user->betslips()->where('is_winner', true)->count();
        return $totalBetslips > 0 ? round(($wonBetslips / $totalBetslips) * 100) : 0;
    }
}

