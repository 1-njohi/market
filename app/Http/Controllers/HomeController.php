<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Betslip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\LeaderboardService;
use App\Services\BetslipCardPresenter;

class HomeController extends Controller
{
    public function __construct(
        protected LeaderboardService $leaderboardService,
    ) {
    }

    public function index()
    {
        $betSlips = $this->getBetslips();
        $mappedFixtures = $this->getFixtures();

        return Inertia::render('Welcome', [
            'fixtures' => $mappedFixtures,
            'bet_slips' => $betSlips,
            'leaders' => $this->leaderboardService->getTopSellers(10),
        ]);
    }
    private function getFixtures()
    {
        // 1. Resolve the four primary market IDs by name (case-insensitive,
        //    name-driven so seeders or renames don't break the mapping).
        $primaryMarkets = [
            'three_way'           => 'Match Winner',
            'double_chance'       => 'Double Chance',
            'over_under'          => 'Goals Over/Under',
            'both_team_to_score'  => 'Both Teams Score',
        ];

        $marketMap = \App\Models\Market::whereIn('name', array_values($primaryMarkets))
            ->get()
            ->keyBy('name');

        // Bail early if the markets aren't seeded — surface a clear signal
        // instead of shipping an empty payload that silently looks OK.
        if ($marketMap->count() < 4) {
            \Log::warning('getFixtures: primary markets missing', [
                'found' => $marketMap->keys()->all(),
            ]);
            return collect();
        }

        $marketIdMap = [];
        foreach ($primaryMarkets as $key => $name) {
            $marketIdMap[$key] = $marketMap[$name]->id;
        }

        $marketIds = array_values($marketIdMap);

        // 2. Live time window. Today through two weeks from now, capped so
        //    the homepage never renders a thousand hidden rows.
        $from = now();
        $to   = now()->addDays(14);

        $fixtures = Fixture::with([
            'odds' => function ($query) use ($marketIds) {
                // Only pull the primary markets plus enough extra rows to
                // support the "additional markets" counter.
                $query->whereIn('market_id', $marketIds)
                    ->orWhere(function ($q) use ($marketIds) {
                        $q->whereNotIn('market_id', $marketIds);
                    });
            },
            'homeTeam',
            'awayTeam',
            'league',
            'league.Country',
        ])
            ->whereHas('odds', function ($query) use ($marketIds) {
                $query->whereIn('market_id', $marketIds);
            })
            ->whereBetween('date', [$from, $to])
            ->where('status_short', 'NS')
            ->orderBy('date')
            ->limit(200)
            ->get();

        if ($fixtures->isEmpty()) {
            return collect();
        }

        // 3. Group fixtures by league. Preserve the sort order within groups.
        $groupedFixtures = $fixtures->groupBy('league_id');

        // 4. Transform each group.
        return $groupedFixtures->map(function ($leagueFixtures, $leagueId) use ($marketIdMap) {
            $league = $leagueFixtures->first()->league;

            $mappedFixtures = $leagueFixtures->map(function ($fixture) use ($marketIdMap) {
                $oddsKeyed = $fixture->odds->keyBy(function ($odd) {
                    return "{$odd->market_id}-{$odd->value}";
                });

                // Count markets we didn't surface in the four fixed panels.
                $uniqueMarketsCount = $fixture->odds->pluck('market_id')->unique()->count();
                $additionalMarketsCount = max(0, $uniqueMarketsCount - 4);

                $getOdd = function ($marketId, $value) use ($oddsKeyed) {
                    $odd = $oddsKeyed->get("{$marketId}-{$value}");
                    return $odd ? ['id' => $odd->id, 'value' => (float) $odd->odd] : null;
                };

                return [
                    'id'         => $fixture->id,
                    'id_on_api'  => $fixture->id_on_api,
                    'date'       => $fixture->date
                        ? \Carbon\Carbon::parse($fixture->date)->format('d/m/y - H:i')
                        : null,
                    'kickoff'    => $fixture->date?->toIso8601String(),
                    'home_team'  => $fixture->homeTeam?->name ?? 'Unknown',
                    'away_team'  => $fixture->awayTeam?->name ?? 'Unknown',
                    'boosted'    => false,
                    'additional_markets_count' => $additionalMarketsCount,
                    'odds' => [
                        'three_way' => [
                            'home' => $getOdd($marketIdMap['three_way'], 'Home'),
                            'draw' => $getOdd($marketIdMap['three_way'], 'Draw'),
                            'away' => $getOdd($marketIdMap['three_way'], 'Away'),
                        ],
                        'double_chance' => [
                            'one_x'   => $getOdd($marketIdMap['double_chance'], 'Home/Draw'),
                            'x_two'   => $getOdd($marketIdMap['double_chance'], 'Draw/Away'),
                            'one_two' => $getOdd($marketIdMap['double_chance'], 'Home/Away'),
                        ],
                        'over_under' => [
                            'over'  => $getOdd($marketIdMap['over_under'], 'Over 2.5'),
                            'under' => $getOdd($marketIdMap['over_under'], 'Under 2.5'),
                        ],
                        'both_team_to_score' => [
                            'yes' => $getOdd($marketIdMap['both_team_to_score'], 'Yes'),
                            'no'  => $getOdd($marketIdMap['both_team_to_score'], 'No'),
                        ],
                    ],
                ];
            });

            return [
                'name'      => $league->name,
                'id'        => $league->id,
                'country'   => $league->Country?->name ?? 'Unknown',
                'logo'      => $league->logo,
                'league_id' => $leagueId,
                'fixtures'  => $mappedFixtures->values(),
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
        $user = auth()->user();
        $presenter = app(BetslipCardPresenter::class);

        $betslips = Betslip::with(BetslipCardPresenter::eagerLoad())
            ->withCount('odds')
            ->where('status', 'pending')
            ->take(4)
            ->get();

        return $betslips->map(
            fn($betslip) => $presenter->present($betslip, $user)
        );
    }
}

