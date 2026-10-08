<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\Market;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FixtureService
{
    /**
     * Upcoming fixtures grouped by league, shaped for FixtureSummaryCard
     * and FixtureList on the frontend.
     *
     * This method is the single source of truth for the fixture-list shape.
     * Both HomeController (Welcome page) and FixturesController (/fixtures)
     * delegate to it — no drift, no duplicated query logic.
     *
     * Returns a Collection of league groups (id / name / country / logo /
     * league_id / fixtures[]).
     */
    public function leaguesWithUpcomingFixtures(int $daysAhead = 14, int $limit = 200): Collection
    {
        // 1. Resolve the four primary market IDs by name (case-insensitive,
        //    name-driven so seeders or renames don't break the mapping).
        $primaryMarkets = [
            'three_way'          => 'Match Winner',
            'double_chance'      => 'Double Chance',
            'over_under'         => 'Goals Over/Under',
            'both_team_to_score' => 'Both Teams Score',
        ];

        $marketMap = Market::whereIn('name', array_values($primaryMarkets))
            ->get()
            ->keyBy('name');

        if ($marketMap->count() < 4) {
            Log::warning('FixtureService: primary markets missing', [
                'found' => $marketMap->keys()->all(),
            ]);
            return collect();
        }

        $marketIdMap = [];
        foreach ($primaryMarkets as $key => $name) {
            $marketIdMap[$key] = $marketMap[$name]->id;
        }

        $marketIds = array_values($marketIdMap);

        // 2. Live time window — today through `daysAhead` days from now.
        $from = now();
        $to   = now()->addDays($daysAhead);

        $fixtures = Fixture::with([
            'odds' => function ($query) use ($marketIds) {
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
            ->limit($limit)
            ->get();

        if ($fixtures->isEmpty()) {
            return collect();
        }

        // 3. Group fixtures by league. Preserve sort order within groups.
        $groupedFixtures = $fixtures->groupBy('league_id');

        // 4. Transform each group.
        return $groupedFixtures->map(function ($leagueFixtures, $leagueId) use ($marketIdMap) {
            $league = $leagueFixtures->first()->league;

            $mappedFixtures = $leagueFixtures->map(function ($fixture) use ($marketIdMap) {
                $oddsKeyed = $fixture->odds->keyBy(function ($odd) {
                    return "{$odd->market_id}-{$odd->value}";
                });

                $uniqueMarketsCount = $fixture->odds->pluck('market_id')->unique()->count();
                $additionalMarketsCount = max(0, $uniqueMarketsCount - 4);

                $getOdd = function ($marketId, $value) use ($oddsKeyed) {
                    $odd = $oddsKeyed->get("{$marketId}-{$value}");
                    return $odd ? ['id' => $odd->id, 'value' => (float) $odd->odd] : null;
                };

                return [
                    'id'        => $fixture->id,
                    'id_on_api' => $fixture->id_on_api,
                    'date'      => $fixture->date
                        ? Carbon::parse($fixture->date)->format('d/m/y - H:i')
                        : null,
                    'kickoff'   => $fixture->date?->toIso8601String(),
                    'home_team' => $fixture->homeTeam?->name ?? 'Unknown',
                    'away_team' => $fixture->awayTeam?->name ?? 'Unknown',
                    'boosted'   => false,
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
}