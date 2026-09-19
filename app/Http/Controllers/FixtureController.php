<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Fixture;

class FixtureController extends Controller
{
    public function index(Request $request)
    {
        $fixture_id_on_api = $request->id;
        $mappedFixture = $this->getFixture($fixture_id_on_api);
        return Inertia::render('Fixture', [
            'fixture' => $mappedFixture
        ]);
    }

    private function getFixture($fixture_id_on_api)
    {
        $excludedMarkets = [99];

        $fixture = Fixture::with([
            'odds' => function ($query) use ($excludedMarkets) {
                $query->select('id', 'fixture_id', 'market_id', 'value', 'odd')
                    ->whereNotIn('market_id', $excludedMarkets);
            },
            'homeTeam',
            'awayTeam'
        ])
            ->where('id_on_api', $fixture_id_on_api)
            ->whereHas('odds', function ($query) use ($excludedMarkets) {
                $query->whereNotIn('market_id', $excludedMarkets);
            })
            ->first();

        if (!$fixture) {
            return null;
        }

        // Key odds by market_id-value for O(1) lookups
        $oddsKeyed = $fixture->odds->keyBy(fn($odd) => "{$odd->market_id}-{$odd->value}");

        // Helper: fetch odd as ['id' => ..., 'value' => ...] or null
        $getOdd = function ($marketId, $value) use ($oddsKeyed) {
            $key = "{$marketId}-{$value}";
            $odd = $oddsKeyed->get($key);
            return $odd ? ['id' => $odd->id, 'value' => $odd->odd] : null;
        };

        // Helper: build exact-goals-number markets (0-10, 10+)
        $buildGoalsMarket = function ($marketId, $suffix = '') use ($getOdd) {
            $result = [];
            for ($i = 0; $i <= 10; $i++) {
                $key = $i === 10 ? '10+' : (string) $i;
                $result[$key] = $getOdd($marketId, $key);
            }
            return array_filter($result);
        };

        // Helper: build over/under markets for a given threshold list
        $buildOverUnder = function ($marketId, array $thresholds) use ($getOdd) {
            $result = [];
            foreach ($thresholds as $t) {
                $result["over_{$t}"] = $getOdd($marketId, "Over {$t}");
                $result["under_{$t}"] = $getOdd($marketId, "Under {$t}");
            }
            return array_filter($result);
        };

        return [
            'id' => $fixture->id,
            'date' => $fixture->date ? \Carbon\Carbon::parse($fixture->date)->format('d/m/y - H:i') : null,
            'home_team' => $fixture->homeTeam->name ?? 'Unknown',
            'away_team' => $fixture->awayTeam->name ?? 'Unknown',
            'boosted' => false,
            'odds' => array_filter([

                // 1: Match Winner
                'three_way' => array_filter([
                    'home' => $getOdd(1, 'Home'),
                    'draw' => $getOdd(1, 'Draw'),
                    'away' => $getOdd(1, 'Away'),
                ]),

                // 2: Home/Away
                'home_away' => array_filter([
                    'home_or_draw' => $getOdd(2, 'Home/Draw'),
                    'draw_or_away' => $getOdd(2, 'Draw/Away'),
                    'home_or_away' => $getOdd(2, 'Home/Away'),
                ]),

                // 3: Second Half Winner
                'second_half_winner' => array_filter([
                    'home' => $getOdd(3, 'Home'),
                    'draw' => $getOdd(3, 'Draw'),
                    'away' => $getOdd(3, 'Away'),
                ]),

                // 5: Goals Over/Under
                'goals_over_under' => $buildOverUnder(5, ['0.5', '1.5', '2.5', '3.5', '4.5', '5.5']),

                // 6: Goals Over/Under First Half
                'goals_over_under_first_half' => $buildOverUnder(6, ['0.5', '1.5', '2.5', '3.5']),

                // 7: HT/FT Double
                'ht_ft_double' => array_filter([
                    'home_home' => $getOdd(7, 'Home/Home'),
                    'home_draw' => $getOdd(7, 'Home/Draw'),
                    'home_away' => $getOdd(7, 'Home/Away'),
                    'draw_home' => $getOdd(7, 'Draw/Home'),
                    'draw_draw' => $getOdd(7, 'Draw/Draw'),
                    'draw_away' => $getOdd(7, 'Draw/Away'),
                    'away_home' => $getOdd(7, 'Away/Home'),
                    'away_draw' => $getOdd(7, 'Away/Draw'),
                    'away_away' => $getOdd(7, 'Away/Away'),
                ]),

                // 8: Both Teams Score
                'both_team_to_score' => array_filter([
                    'yes' => $getOdd(8, 'Yes'),
                    'no' => $getOdd(8, 'No'),
                ]),

                // 10: Exact Score
                'exact_score' => array_filter(
                    collect([
                        '0:0',
                        '1:0',
                        '2:0',
                        '3:0',
                        '4:0',
                        '5:0',
                        '0:1',
                        '1:1',
                        '2:1',
                        '3:1',
                        '4:1',
                        '5:1',
                        '0:2',
                        '1:2',
                        '2:2',
                        '3:2',
                        '4:2',
                        '5:2',
                        '0:3',
                        '1:3',
                        '2:3',
                        '3:3',
                        '4:3',
                        '5:3',
                        '0:4',
                        '1:4',
                        '2:4',
                        '3:4',
                        '4:4',
                        '5:4',
                        '0:5',
                        '1:5',
                        '2:5',
                        '3:5',
                        '4:5',
                        '5:5',
                    ])->mapWithKeys(fn($score) => [$score => $getOdd(10, $score)])->toArray()
                ),

                // 12: Double Chance
                'double_chance' => array_filter([
                    'one_x' => $getOdd(12, 'Home/Draw'),
                    'x_two' => $getOdd(12, 'Draw/Away'),
                    'one_two' => $getOdd(12, 'Home/Away'),
                ]),

                // 13: First Half Winner
                'first_half_winner' => array_filter([
                    'home' => $getOdd(13, 'Home'),
                    'draw' => $getOdd(13, 'Draw'),
                    'away' => $getOdd(13, 'Away'),
                ]),

                // 14: Team To Score First
                'team_to_score_first' => array_filter([
                    'home' => $getOdd(14, 'Home'),
                    'away' => $getOdd(14, 'Away'),
                    'no_goal' => $getOdd(14, 'No Goal'),
                ]),

                // 15: Team To Score Last
                'team_to_score_last' => array_filter([
                    'home' => $getOdd(15, 'Home'),
                    'away' => $getOdd(15, 'Away'),
                    'no_goal' => $getOdd(15, 'No Goal'),
                ]),

                // 16: Total - Home
                'total_home' => $buildOverUnder(16, ['0.5', '1.5', '2.5', '3.5', '4.5']),

                // 17: Total - Away
                'total_away' => $buildOverUnder(17, ['0.5', '1.5', '2.5', '3.5', '4.5']),

                // 20: Double Chance - First Half
                'double_chance_first_half' => array_filter([
                    'home_draw' => $getOdd(20, 'Home/Draw'),
                    'draw_away' => $getOdd(20, 'Draw/Away'),
                    'home_away' => $getOdd(20, 'Home/Away'),
                ]),

                // 21: Odd/Even
                'odd_even' => array_filter([
                    'odd' => $getOdd(21, 'Odd'),
                    'even' => $getOdd(21, 'Even'),
                ]),

                // 22: Odd/Even - First Half
                'odd_even_first_half' => array_filter([
                    'odd' => $getOdd(22, 'Odd'),
                    'even' => $getOdd(22, 'Even'),
                ]),

                // 24: Results/Both Teams Score
                'results_both_teams_score' => array_filter([
                    'home_yes' => $getOdd(24, 'Home/Yes'),
                    'home_no' => $getOdd(24, 'Home/No'),
                    'draw_yes' => $getOdd(24, 'Draw/Yes'),
                    'draw_no' => $getOdd(24, 'Draw/No'),
                    'away_yes' => $getOdd(24, 'Away/Yes'),
                    'away_no' => $getOdd(24, 'Away/No'),
                ]),

                // 25: Result/Total Goals
                'result_total_goals' => array_filter([
                    'home_over' => $getOdd(25, 'Home/Over 2.5'),
                    'home_under' => $getOdd(25, 'Home/Under 2.5'),
                    'draw_over' => $getOdd(25, 'Draw/Over 2.5'),
                    'draw_under' => $getOdd(25, 'Draw/Under 2.5'),
                    'away_over' => $getOdd(25, 'Away/Over 2.5'),
                    'away_under' => $getOdd(25, 'Away/Under 2.5'),
                ]),

                // 26: Goals Over/Under - Second Half
                'goals_over_under_second_half' => $buildOverUnder(26, ['0.5', '1.5', '2.5', '3.5']),

                // 29: Win to Nil - Home
                'win_to_nil_home' => array_filter([
                    'yes' => $getOdd(29, 'Yes'),
                    'no' => $getOdd(29, 'No'),
                ]),

                // 30: Win to Nil - Away
                'win_to_nil_away' => array_filter([
                    'yes' => $getOdd(30, 'Yes'),
                    'no' => $getOdd(30, 'No'),
                ]),

                // 32: Win Both Halves
                'win_both_halves' => array_filter([
                    'home' => $getOdd(32, 'Home'),
                    'away' => $getOdd(32, 'Away'),
                ]),

                // 34: Both Teams Score - First Half
                'both_teams_score_first_half' => array_filter([
                    'yes' => $getOdd(34, 'Yes'),
                    'no' => $getOdd(34, 'No'),
                ]),

                // 38: Exact Goals Number
                'exact_goals_number' => $buildGoalsMarket(38),

                // 40: Home Team Exact Goals Number
                'home_exact_goals_number' => $buildGoalsMarket(40),

                // 41: Away Team Exact Goals Number
                'away_exact_goals_number' => $buildGoalsMarket(41),

                // 42: Second Half Exact Goals Number
                'second_half_exact_goals_number' => $buildGoalsMarket(42),

                // 43: Home Team Score a Goal
                'home_score_goal' => array_filter([
                    'yes' => $getOdd(43, 'Yes'),
                    'no' => $getOdd(43, 'No'),
                ]),

                // 44: Away Team Score a Goal
                'away_score_goal' => array_filter([
                    'yes' => $getOdd(44, 'Yes'),
                    'no' => $getOdd(44, 'No'),
                ]),

                // 46: Exact Goals Number - First Half
                'exact_goals_number_first_half' => $buildGoalsMarket(46),
            ]),
        ];
    }
}
