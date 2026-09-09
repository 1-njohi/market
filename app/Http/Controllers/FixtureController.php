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
        \Log::info($mappedFixture);
        return Inertia::render('Fixture', [
            'fixture' => $mappedFixture
        ]);
    }
    private function getFixture($fixture_id_on_api)
    {
        $excludedMarkets = [99];

        // Get a fixture with its pending odds and relationships
        $fixture = Fixture::with([
            'odds' => function ($query) use ($excludedMarkets) {
                $query
                // ->where('status', 'pending')
                    ->select('id', 'fixture_id', 'market_id', 'value', 'odd')
                    ->whereNotIn('market_id', $excludedMarkets);
            },
            'homeTeam',
            'awayTeam'
        ])
            ->where('id_on_api', $fixture_id_on_api)
            ->whereHas('odds', function ($query) use ($excludedMarkets) {
                $query//->where('status', 'pending')
                    ->whereNotIn('market_id', $excludedMarkets);
            })
            ->first();
        \Log::info($fixture -> odds -> count());
        // If no fixture found, return null or handle appropriately
        if (!$fixture) {
            return null;
        }

        // Key the odds by a unique string combination like "market_id-value" for O(1) lookups
        $oddsKeyed = $fixture->odds->keyBy(function ($odd) {
            return "{$odd->market_id}-{$odd->value}";
        });

        return [
            'id' => $fixture->id,
            'date' => $fixture->date ? \Carbon\Carbon::parse($fixture->date)->format('d/m/y - H:i') : null,
            'home_team' => $fixture->homeTeam->name ?? 'Unknown',
            'away_team' => $fixture->awayTeam->name ?? 'Unknown',
            'boosted' => false,
            'odds' => array_filter([
                // Market 1: Match Winner
                'three_way' => array_filter([
                    'home' => $oddsKeyed->get('1-Home') ? [
                        'id' => $oddsKeyed->get('1-Home')->id,
                        'value' => $oddsKeyed->get('1-Home')->odd
                    ] : null,
                    'draw' => $oddsKeyed->get('1-Draw') ? [
                        'id' => $oddsKeyed->get('1-Draw')->id,
                        'value' => $oddsKeyed->get('1-Draw')->odd
                    ] : null,
                    'away' => $oddsKeyed->get('1-Away') ? [
                        'id' => $oddsKeyed->get('1-Away')->id,
                        'value' => $oddsKeyed->get('1-Away')->odd
                    ] : null,
                ]),
                // Market 2: Home/Away
                'home_away' => array_filter([
                    'home_or_draw' => $oddsKeyed->get('2-Home/Draw') ? [
                        'id' => $oddsKeyed->get('2-Home/Draw')->id,
                        'value' => $oddsKeyed->get('2-Home/Draw')->odd
                    ] : null,
                    'draw_or_away' => $oddsKeyed->get('2-Draw/Away') ? [
                        'id' => $oddsKeyed->get('2-Draw/Away')->id,
                        'value' => $oddsKeyed->get('2-Draw/Away')->odd
                    ] : null,
                    'home_or_away' => $oddsKeyed->get('2-Home/Away') ? [
                        'id' => $oddsKeyed->get('2-Home/Away')->id,
                        'value' => $oddsKeyed->get('2-Home/Away')->odd
                    ] : null,
                ]),
                // Market 3: Second Half Winner
                'second_half_winner' => array_filter([
                    'home' => $oddsKeyed->get('3-Home') ? [
                        'id' => $oddsKeyed->get('3-Home')->id,
                        'value' => $oddsKeyed->get('3-Home')->odd
                    ] : null,
                    'draw' => $oddsKeyed->get('3-Draw') ? [
                        'id' => $oddsKeyed->get('3-Draw')->id,
                        'value' => $oddsKeyed->get('3-Draw')->odd
                    ] : null,
                    'away' => $oddsKeyed->get('3-Away') ? [
                        'id' => $oddsKeyed->get('3-Away')->id,
                        'value' => $oddsKeyed->get('3-Away')->odd
                    ] : null,
                ]),
                'first_half_winner' => array_filter([
                    'home' => $oddsKeyed->get('11-Home') ? [
                        'id' => $oddsKeyed->get('11-Home')->id,
                        'value' => $oddsKeyed->get('11-Home')->odd
                    ] : null,
                    'draw' => $oddsKeyed->get('11-Draw') ? [
                        'id' => $oddsKeyed->get('11-Draw')->id,
                        'value' => $oddsKeyed->get('11-Draw')->odd
                    ] : null,
                    'away' => $oddsKeyed->get('11-Away') ? [
                        'id' => $oddsKeyed->get('11-Away')->id,
                        'value' => $oddsKeyed->get('11-Away')->odd
                    ] : null,
                ]),
                'double_chance' => array_filter([
                    'one_x' => $oddsKeyed->get('10-Home/Draw') ? [
                        'id' => $oddsKeyed->get('10-Home/Draw')->id,
                        'value' => $oddsKeyed->get('10-Home/Draw')->odd
                    ] : null,
                    'x_two' => $oddsKeyed->get('10-Draw/Away') ? [
                        'id' => $oddsKeyed->get('10-Draw/Away')->id,
                        'value' => $oddsKeyed->get('10-Draw/Away')->odd
                    ] : null,
                    'one_two' => $oddsKeyed->get('10-Home/Away') ? [
                        'id' => $oddsKeyed->get('10-Home/Away')->id,
                        'value' => $oddsKeyed->get('10-Home/Away')->odd
                    ] : null,
                ]),
                'over_under' => array_filter([
                    'over' => $oddsKeyed->get('4-Over 2.5') ? [
                        'id' => $oddsKeyed->get('4-Over 2.5')->id,
                        'value' => $oddsKeyed->get('4-Over 2.5')->odd
                    ] : null,
                    'under' => $oddsKeyed->get('4-Under 2.5') ? [
                        'id' => $oddsKeyed->get('4-Under 2.5')->id,
                        'value' => $oddsKeyed->get('4-Under 2.5')->odd
                    ] : null,
                ]),
                // Market 6: HT/FT Double
                'ht_ft_double' => array_filter([
                    'home_home' => $oddsKeyed->get('6-Home/Home') ? [
                        'id' => $oddsKeyed->get('6-Home/Home')->id,
                        'value' => $oddsKeyed->get('6-Home/Home')->odd
                    ] : null,
                    'home_draw' => $oddsKeyed->get('6-Home/Draw') ? [
                        'id' => $oddsKeyed->get('6-Home/Draw')->id,
                        'value' => $oddsKeyed->get('6-Home/Draw')->odd
                    ] : null,
                    'home_away' => $oddsKeyed->get('6-Home/Away') ? [
                        'id' => $oddsKeyed->get('6-Home/Away')->id,
                        'value' => $oddsKeyed->get('6-Home/Away')->odd
                    ] : null,
                    'draw_home' => $oddsKeyed->get('6-Draw/Home') ? [
                        'id' => $oddsKeyed->get('6-Draw/Home')->id,
                        'value' => $oddsKeyed->get('6-Draw/Home')->odd
                    ] : null,
                    'draw_draw' => $oddsKeyed->get('6-Draw/Draw') ? [
                        'id' => $oddsKeyed->get('6-Draw/Draw')->id,
                        'value' => $oddsKeyed->get('6-Draw/Draw')->odd
                    ] : null,
                    'draw_away' => $oddsKeyed->get('6-Draw/Away') ? [
                        'id' => $oddsKeyed->get('6-Draw/Away')->id,
                        'value' => $oddsKeyed->get('6-Draw/Away')->odd
                    ] : null,
                    'away_home' => $oddsKeyed->get('6-Away/Home') ? [
                        'id' => $oddsKeyed->get('6-Away/Home')->id,
                        'value' => $oddsKeyed->get('6-Away/Home')->odd
                    ] : null,
                    'away_draw' => $oddsKeyed->get('6-Away/Draw') ? [
                        'id' => $oddsKeyed->get('6-Away/Draw')->id,
                        'value' => $oddsKeyed->get('6-Away/Draw')->odd
                    ] : null,
                    'away_away' => $oddsKeyed->get('6-Away/Away') ? [
                        'id' => $oddsKeyed->get('6-Away/Away')->id,
                        'value' => $oddsKeyed->get('6-Away/Away')->odd
                    ] : null,
                ]),
                'both_team_to_score' => array_filter([
                    'yes' => $oddsKeyed->get('7-Yes') ? [
                        'id' => $oddsKeyed->get('7-Yes')->id,
                        'value' => $oddsKeyed->get('7-Yes')->odd
                    ] : null,
                    'no' => $oddsKeyed->get('7-No') ? [
                        'id' => $oddsKeyed->get('7-No')->id,
                        'value' => $oddsKeyed->get('7-No')->odd
                    ] : null,
                ]),
                'exact_goals_number' => array_filter([
                    '0' => $oddsKeyed->get('26-0') ? [
                        'id' => $oddsKeyed->get('26-0')->id,
                        'value' => $oddsKeyed->get('26-0')->odd
                    ] : null,
                    '1' => $oddsKeyed->get('26-1') ? [
                        'id' => $oddsKeyed->get('26-1')->id,
                        'value' => $oddsKeyed->get('26-1')->odd
                    ] : null,
                    '2' => $oddsKeyed->get('26-2') ? [
                        'id' => $oddsKeyed->get('26-2')->id,
                        'value' => $oddsKeyed->get('26-2')->odd
                    ] : null,
                    '3' => $oddsKeyed->get('26-3') ? [
                        'id' => $oddsKeyed->get('26-3')->id,
                        'value' => $oddsKeyed->get('26-3')->odd
                    ] : null,
                    '4' => $oddsKeyed->get('26-4') ? [
                        'id' => $oddsKeyed->get('26-4')->id,
                        'value' => $oddsKeyed->get('26-4')->odd
                    ] : null,
                    '5' => $oddsKeyed->get('26-5') ? [
                        'id' => $oddsKeyed->get('26-5')->id,
                        'value' => $oddsKeyed->get('26-5')->odd
                    ] : null,
                    '6' => $oddsKeyed->get('26-6') ? [
                        'id' => $oddsKeyed->get('26-6')->id,
                        'value' => $oddsKeyed->get('26-6')->odd
                    ] : null,
                    '7' => $oddsKeyed->get('26-7') ? [
                        'id' => $oddsKeyed->get('26-7')->id,
                        'value' => $oddsKeyed->get('26-7')->odd
                    ] : null,
                    '8' => $oddsKeyed->get('26-8') ? [
                        'id' => $oddsKeyed->get('26-8')->id,

                        'value' => $oddsKeyed->get('26-8')->odd
                    ] : null,
                    '9' => $oddsKeyed->get('26-9') ? [
                        'id' => $oddsKeyed->get('26-9')->id,
                        'value' => $oddsKeyed->get('26-9')->odd
                    ] : null,
                    '10' => $oddsKeyed->get('26-10') ? [
                        'id' => $oddsKeyed->get('26-10')->id,
                        'value' => $oddsKeyed->get('26-10')->odd
                    ] : null,
                    '10+' => $oddsKeyed->get('26-10+') ? [
                        'id' => $oddsKeyed->get('26-10+')->id,
                        'value' => $oddsKeyed->get('26-10+')->odd
                    ] : null,
                ]),
                // Market 27: Home Team Exact Goals Number
                'home_exact_goals_number' => array_filter([
                    '0' => $oddsKeyed->get('27-0') ? [
                        'id' => $oddsKeyed->get('27-0')->id,
                        'value' => $oddsKeyed->get('27-0')->odd
                    ] : null,
                    '1' => $oddsKeyed->get('27-1') ? [
                        'id' => $oddsKeyed->get('27-1')->id,
                        'value' => $oddsKeyed->get('27-1')->odd
                    ] : null,
                    '2' => $oddsKeyed->get('27-2') ? [
                        'id' => $oddsKeyed->get('27-2')->id,
                        'value' => $oddsKeyed->get('27-2')->odd
                    ] : null,
                    '3' => $oddsKeyed->get('27-3') ? [
                        'id' => $oddsKeyed->get('27-3')->id,
                        'value' => $oddsKeyed->get('27-3')->odd
                    ] : null,
                    '4' => $oddsKeyed->get('27-4') ? [
                        'id' => $oddsKeyed->get('27-4')->id,
                        'value' => $oddsKeyed->get('27-4')->odd
                    ] : null,
                    '5' => $oddsKeyed->get('27-5') ? [
                        'id' => $oddsKeyed->get('27-5')->id,
                        'value' => $oddsKeyed->get('27-5')->odd
                    ] : null,
                    '6' => $oddsKeyed->get('27-6') ? [
                        'id' => $oddsKeyed->get('27-6')->id,
                        'value' => $oddsKeyed->get('27-6')->odd
                    ] : null,
                    '7' => $oddsKeyed->get('27-7') ? [
                        'id' => $oddsKeyed->get('27-7')->id,
                        'value' => $oddsKeyed->get('27-7')->odd
                    ] : null,
                    '8' => $oddsKeyed->get('27-8') ? [
                        'id' => $oddsKeyed->get('27-8')->id,
                        'value' => $oddsKeyed->get('27-8')->odd
                    ] : null,
                    '9' => $oddsKeyed->get('27-9') ? [
                        'id' => $oddsKeyed->get('27-9')->id,
                        'value' => $oddsKeyed->get('27-9')->odd
                    ] : null,
                    '10' => $oddsKeyed->get('27-10') ? [
                        'id' => $oddsKeyed->get('27-10')->id,
                        'value' => $oddsKeyed->get('27-10')->odd
                    ] : null,
                    '10+' => $oddsKeyed->get('27-10+') ? [
                        'id' => $oddsKeyed->get('27-10+')->id,
                        'value' => $oddsKeyed->get('27-10+')->odd
                    ] : null,
                ]),
                // Market 12: Team To Score First
                'team_to_score_first' => array_filter([
                    'home' => $oddsKeyed->get('12-Home') ? [
                        'id' => $oddsKeyed->get('12-Home')->id,
                        'value' => $oddsKeyed->get('12-Home')->odd
                    ] : null,
                    'away' => $oddsKeyed->get('12-Away') ? [
                        'id' => $oddsKeyed->get('12-Away')->id,
                        'value' => $oddsKeyed->get('12-Away')->odd
                    ] : null,
                    'no_goal' => $oddsKeyed->get('12-No-Goal') ? [
                        'id' => $oddsKeyed->get('12-No-Goal')->id,
                        'value' => $oddsKeyed->get('12-No-Goal')->odd
                    ] : null,
                ]),
                // Market 13: Team To Score Last
                'team_to_score_last' => array_filter([
                    'home' => $oddsKeyed->get('13-Home') ? [
                        'id' => $oddsKeyed->get('13-Home')->id,
                        'value' => $oddsKeyed->get('13-Home')->odd
                    ] : null,
                    'away' => $oddsKeyed->get('13-Away') ? [
                        'id' => $oddsKeyed->get('13-Away')->id,
                        'value' => $oddsKeyed->get('13-Away')->odd
                    ] : null,
                    'no_goal' => $oddsKeyed->get('13-No Goal') ? [
                        'id' => $oddsKeyed->get('13-No Goal')->id,
                        'value' => $oddsKeyed->get('13-No Goal')->odd
                    ] : null,
                ]),
                // Market 14: Total - Home
                'total_home' => array_filter([
                    'over_0.5' => $oddsKeyed->get('14-Over-0.5') ? [
                        'id' => $oddsKeyed->get('14-Over -0.5')->id,
                        'value' => $oddsKeyed->get('14-Over-0.5')->odd
                    ] : null,
                    'under_0.5' => $oddsKeyed->get('14-Under-0.5') ? [
                        'id' => $oddsKeyed->get('14-Under-0.5')->id,
                        'value' => $oddsKeyed->get('14-Under-0.5')->odd
                    ] : null,
                    'over_1.5' => $oddsKeyed->get('14-Over-1.5') ? [
                        'id' => $oddsKeyed->get('14-Over-1.5')->id,
                        'value' => $oddsKeyed->get('14-Over-1.5')->odd
                    ] : null,
                    'under_1.5' => $oddsKeyed->get('14-Under-1.5') ? [
                        'id' => $oddsKeyed->get('14-Under-1.5')->id,
                        'value' => $oddsKeyed->get('14-Under-1.5')->odd
                    ] : null,
                    'over_2.5' => $oddsKeyed->get('14-Over-2.5') ? [
                        'id' => $oddsKeyed->get('14-Over-2.5')->id,
                        'value' => $oddsKeyed->get('14-Over-2.5')->odd
                    ] : null,
                    'under_2.5' => $oddsKeyed->get('14-Under-2.5') ? [
                        'id' => $oddsKeyed->get('14-Under-2.5')->id,
                        'value' => $oddsKeyed->get('14-Under-2.5')->odd
                    ] : null,
                    'over_3.5' => $oddsKeyed->get('14-Over 3.5') ? [
                        'id' => $oddsKeyed->get('14-Over 3.5')->id,
                        'value' => $oddsKeyed->get('14-Over 3.5')->odd
                    ] : null,
                    'under_3.5' => $oddsKeyed->get('14-Under 3.5') ? [
                        'id' => $oddsKeyed->get('14-Under-3.5')->id,
                        'value' => $oddsKeyed->get('14-Under 3.5')->odd
                    ] : null,
                    'over_4.5' => $oddsKeyed->get('14-Over 4.5') ? [
                        'id' => $oddsKeyed->get('14-Over 4.5')->id,
                        'value' => $oddsKeyed->get('14-Over 4.5')->odd
                    ] : null,
                    'under_4.5' => $oddsKeyed->get('14-Under 4.5') ? [
                        'id' => $oddsKeyed->get('14-Under 4.5')->id,
                        'value' => $oddsKeyed->get('14-Under 4.5')->odd
                    ] : null,
                ]),
                // Market 15: Total - Away
                'total_away' => array_filter([
                    'over_0.5' => $oddsKeyed->get('15-Over 0.5') ? [
                        'id' => $oddsKeyed->get('15-Over 0.5')->id,
                        'value' => $oddsKeyed->get('15-Over 0.5')->odd
                    ] : null,
                    'under_0.5' => $oddsKeyed->get('15-Under 0.5') ? [
                        'id' => $oddsKeyed->get('15-Under 0.5')->id,
                        'value' => $oddsKeyed->get('15-Under 0.5')->odd
                    ] : null,
                    'over_1.5' => $oddsKeyed->get('15-Over 1.5') ? [
                        'id' => $oddsKeyed->get('15-Over 1.5')->id,
                        'value' => $oddsKeyed->get('15-Over 1.5')->odd
                    ] : null,
                    'under_1.5' => $oddsKeyed->get('15-Under 1.5') ? [
                        'id' => $oddsKeyed->get('15-Under 1.5')->id,
                        'value' => $oddsKeyed->get('15-Under 1.5')->odd
                    ] : null,
                    'over_2.5' => $oddsKeyed->get('15-Over 2.5') ? [
                        'id' => $oddsKeyed->get('15-Over 2.5')->id,
                        'value' => $oddsKeyed->get('15-Over 2.5')->odd
                    ] : null,
                    'under_2.5' => $oddsKeyed->get('15-Under 2.5') ? [
                        'id' => $oddsKeyed->get('15-Under 2.5')->id,
                        'value' => $oddsKeyed->get('15-Under 2.5')->odd
                    ] : null,
                    'over_3.5' => $oddsKeyed->get('15-Over 3.5') ? [
                        'id' => $oddsKeyed->get('15-Over 3.5')->id,
                        'value' => $oddsKeyed->get('15-Over 3.5')->odd
                    ] : null,
                    'under_3.5' => $oddsKeyed->get('15-Under 3.5') ? [
                        'id' => $oddsKeyed->get('15-Under 3.5')->id,
                        'value' => $oddsKeyed->get('15-Under 3.5')->odd
                    ] : null,
                    'over_4.5' => $oddsKeyed->get('15-Over 4.5') ? [
                        'id' => $oddsKeyed->get('15-Over 4.5')->id,
                        'value' => $oddsKeyed->get('15-Over 4.5')->odd
                    ] : null,
                    'under_4.5' => $oddsKeyed->get('15-Under 4.5') ? [
                        'id' => $oddsKeyed->get('15-Under 4.5')->id,
                        'value' => $oddsKeyed->get('15-Under 4.5')->odd
                    ] : null,
                ]),
                // Market 16: Double Chance - First Half
                'double_chance_first_half' => array_filter([
                    'home_draw' => $oddsKeyed->get('16-Home/Draw') ? [
                        'id' => $oddsKeyed->get('16-Home/Draw')->id,
                        'value' => $oddsKeyed->get('16-Home/Draw')->odd
                    ] : null,
                    'draw_away' => $oddsKeyed->get('16-Draw/Away') ? [
                        'id' => $oddsKeyed->get('16-Draw/Away')->id,
                        'value' => $oddsKeyed->get('16-Draw/Away')->odd
                    ] : null,
                    'home_away' => $oddsKeyed->get('16-Home/Away') ? [
                        'id' => $oddsKeyed->get('16-Home/Away')->id,
                        'value' => $oddsKeyed->get('16-Home/Away')->odd
                    ] : null,
                ]),
                // Market 17: Odd/Even
                'odd_even' => array_filter([
                    'odd' => $oddsKeyed->get('17-Odd') ? [
                        'id' => $oddsKeyed->get('17-Odd')->id,
                        'value' => $oddsKeyed->get('17-Odd')->odd
                    ] : null,
                    'even' => $oddsKeyed->get('17-Even') ? [
                        'id' => $oddsKeyed->get('17-Even')->id,
                        'value' => $oddsKeyed->get('17-Even')->odd
                    ] : null,
                ]),
                // Market 18: Odd/Even - First Half
                'odd_even_first_half' => array_filter([
                    'odd' => $oddsKeyed->get('18-Odd') ? [
                        'id' => $oddsKeyed->get('18-Odd')->id,
                        'value' => $oddsKeyed->get('18-Odd')->odd
                    ] : null,
                    'even' => $oddsKeyed->get('18-Even') ? [
                        'id' => $oddsKeyed->get('18-Even')->id,
                        'value' => $oddsKeyed->get('18-Even')->odd
                    ] : null,
                ]),
                // Market 19: Results/Both Teams Score
                'results_both_teams_score' => array_filter([
                    'home_yes' => $oddsKeyed->get('19-Home/Yes') ? [
                        'id' => $oddsKeyed->get('19-Home/Yes')->id,
                        'value' => $oddsKeyed->get('19-Home/Yes')->odd
                    ] : null,
                    'home_no' => $oddsKeyed->get('19-Home/No') ? [
                        'id' => $oddsKeyed->get('19-Home/No')->id,
                        'value' => $oddsKeyed->get('19-Home/No')->odd
                    ] : null,
                    'draw_yes' => $oddsKeyed->get('19-Draw/Yes') ? [
                        'id' => $oddsKeyed->get('19-Draw/Yes')->id,
                        'value' => $oddsKeyed->get('19-Draw/Yes')->odd
                    ] : null,
                    'draw_no' => $oddsKeyed->get('19-Draw/No') ? [
                        'id' => $oddsKeyed->get('19-Draw/No')->id,
                        'value' => $oddsKeyed->get('19-Draw/No')->odd
                    ] : null,
                    'away_yes' => $oddsKeyed->get('19-Away/Yes') ? [
                        'id' => $oddsKeyed->get('19-Away/Yes')->id,
                        'value' => $oddsKeyed->get('19-Away/Yes')->odd
                    ] : null,
                    'away_no' => $oddsKeyed->get('19-Away/No') ? [
                        'id' => $oddsKeyed->get('19-Away/No')->id,
                        'value' => $oddsKeyed->get('19-Away/No')->odd
                    ] : null,
                ]),
                // Market 20: Result/Total Goals
                'result_total_goals' => array_filter([
                    'home_over' => $oddsKeyed->get('20-Home/Over') ? [
                        'id' => $oddsKeyed->get('20-Home /Over')->id,
                        'value' => $oddsKeyed->get('20-Home/Over')->odd
                    ] : null,
                    'home_under' => $oddsKeyed->get('20-Home/Under') ? [
                        'id' => $oddsKeyed->get('20-Home/Under')->id,
                        'value' => $oddsKeyed->get('20-Home/Under')->odd
                    ] : null,
                    'draw_over' => $oddsKeyed->get('20-Draw/Over') ? [
                        'id' => $oddsKeyed->get('20-Draw/Over')->id,
                        'value' => $oddsKeyed->get('20-Draw/Over')->odd
                    ] : null,
                    'draw_under' => $oddsKeyed->get('20-Draw/Under') ? [
                        'id' => $oddsKeyed->get('20-Draw/Under')->id,
                        'value' => $oddsKeyed->get('20-Draw/Under')->odd
                    ] : null,
                    'away_over' => $oddsKeyed->get('20-Away/Over') ? [
                        'id' => $oddsKeyed->get('20-Away/Over')->id,
                        'value' => $oddsKeyed->get('20-Away/Over')->odd
                    ] : null,
                    'away_under' => $oddsKeyed->get('20-Away/Under') ? [
                        'id' => $oddsKeyed->get('20-Away/Under')->id,
                        'value' => $oddsKeyed->get('20-Away/Under')->odd
                    ] : null,
                ]),
                // Market 21: Goals Over/Under - Second Half
                'goals_over_under_second_half' => array_filter([
                    'over_0.5' => $oddsKeyed->get('21-Over-0.5') ? [
                        'id' => $oddsKeyed->get('21-Over-0.5')->id,
                        'value' => $oddsKeyed->get('21-Over-0.5')->odd
                    ] : null,
                    'under_0.5' => $oddsKeyed->get('21-Under-0.5') ? [
                        'id' => $oddsKeyed->get('21-Under-0.5')->id,
                        'value' => $oddsKeyed->get('21-Under-0.5')->odd
                    ] : null,
                    'over_1.5' => $oddsKeyed->get('21-Over-1.5') ? [
                        'id' => $oddsKeyed->get('21-Over-1.5')->id,
                        'value' => $oddsKeyed->get('21-Over-1.5')->odd
                    ] : null,
                    'under_1.5' => $oddsKeyed->get('21-Under-1.5') ? [
                        'id' => $oddsKeyed->get('21-Under-1.5')->id,
                        'value' => $oddsKeyed->get('21-Under-1.5')->odd
                    ] : null,
                    'over_2.5' => $oddsKeyed->get('21-Over-2.5') ? [
                        'id' => $oddsKeyed->get('21-Over-2.5')->id,
                        'value' => $oddsKeyed->get('21-Over-2.5')->odd
                    ] : null,
                    'under_2.5' => $oddsKeyed->get('21-Under-2.5') ? [
                        'id' => $oddsKeyed->get('21-Under-2.5')->id,
                        'value' => $oddsKeyed->get('21-Under-2.5')->odd
                    ] : null,
                ]),
                // Market 22: Win to Nil - Home
                'win_to_nil_home' => array_filter([
                    'yes' => $oddsKeyed->get('22-Yes') ? [
                        'id' => $oddsKeyed->get('22-Yes')->id,
                        'value' => $oddsKeyed->get('22-Yes')->odd
                    ] : null,
                    'no' => $oddsKeyed->get('22-No') ? [
                        'id' => $oddsKeyed->get('22-No')->id,
                        'value' => $oddsKeyed->get('22-No')->odd
                    ] : null,
                ]),
                // Market 23: Win to Nil - Away
                'win_to_nil_away' => array_filter([
                    'yes' => $oddsKeyed->get('23-Yes') ? [
                        'id' => $oddsKeyed->get('23-Yes')->id,
                        'value' => $oddsKeyed->get('23-Yes')->odd
                    ] : null,
                    'no' => $oddsKeyed->get('23-No') ? [
                        'id' => $oddsKeyed->get('23-No')->id,
                        'value' => $oddsKeyed->get('23-No')->odd
                    ] : null,
                ]),
                // Market 24: Win Both Halves
                'win_both_halves' => array_filter([
                    'home' => $oddsKeyed->get('24-Home') ? [
                        'id' => $oddsKeyed->get('24-Home')->id,
                        'value' => $oddsKeyed->get('24-Home')->odd
                    ] : null,
                    'away' => $oddsKeyed->get('24-Away') ? [
                        'id' => $oddsKeyed->get('24-Away')->id,
                        'value' => $oddsKeyed->get('24-Away')->odd
                    ] : null,
                ]),
                // Market 25: Both Teams Score - First Half
                'both_teams_score_first_half' => array_filter([
                    'yes' => $oddsKeyed->get('25-Yes') ? [
                        'id' => $oddsKeyed->get('25-Yes')->id,
                        'value' => $oddsKeyed->get('25-Yes')->odd
                    ] : null,
                    'no' => $oddsKeyed->get('25-No') ? [
                        'id' => $oddsKeyed->get('25-No')->id,
                        'value' => $oddsKeyed->get('25-No')->odd
                    ] : null,
                ]),
                // Market 28: Away Team Exact Goals Number
                'away_exact_goals_number' => array_filter([
                    '0' => $oddsKeyed->get('28-0') ? [
                        'id' => $oddsKeyed->get('28-0')->id,
                        'value' => $oddsKeyed->get('28-0')->odd
                    ] : null,
                    '1' => $oddsKeyed->get('28-1') ? [
                        'id' => $oddsKeyed->get('28-1')->id,
                        'value' => $oddsKeyed->get('28-1')->odd
                    ] : null,
                    '2' => $oddsKeyed->get('28-2') ? [
                        'id' => $oddsKeyed->get('28-2')->id,
                        'value' => $oddsKeyed->get('28-2')->odd
                    ] : null,
                    '3' => $oddsKeyed->get('28-3') ? [
                        'id' => $oddsKeyed->get('28-3')->id,
                        'value' => $oddsKeyed->get('28-3')->odd
                    ] : null,
                    '4' => $oddsKeyed->get('28-4') ? [
                        'id' => $oddsKeyed->get('28-4')->id,
                        'value' => $oddsKeyed->get('28-4')->odd
                    ] : null,
                    '5' => $oddsKeyed->get('28-5') ? [
                        'id' => $oddsKeyed->get('28-5')->id,
                        'value' => $oddsKeyed->get('28-5')->odd
                    ] : null,
                    '6' => $oddsKeyed->get('28-6') ? [
                        'id' => $oddsKeyed->get('28-6')->id,
                        'value' => $oddsKeyed->get('28-6')->odd
                    ] : null,
                    '7' => $oddsKeyed->get('28-7') ? [
                        'id' => $oddsKeyed->get('28-7')->id,
                        'value' => $oddsKeyed->get('28-7')->odd
                    ] : null,
                    '8' => $oddsKeyed->get('28-8') ? [
                        'id' => $oddsKeyed->get('28-8')->id,
                        'value' => $oddsKeyed->get('28-8')->odd
                    ] : null,
                    '9' => $oddsKeyed->get('28-9') ? [
                        'id' => $oddsKeyed->get('28-9')->id,
                        'value' => $oddsKeyed->get('28-9')->odd
                    ] : null,
                    '10' => $oddsKeyed->get('28-10') ? [
                        'id' => $oddsKeyed->get('28-10')->id,
                        'value' => $oddsKeyed->get('28-10')->odd
                    ] : null,
                    '10+' => $oddsKeyed->get('28-10+') ? [
                        'id' => $oddsKeyed->get('28-10+')->id,
                        'value' => $oddsKeyed->get('28-10+')->odd
                    ] : null,
                ]),
                // Market 29: Second Half Exact Goals Number
                'second_half_exact_goals_number' => array_filter([
                    '0' => $oddsKeyed->get('29-0') ? [
                        'id' => $oddsKeyed->get('29-0')->id,
                        'value' => $oddsKeyed->get('29-0')->odd
                    ] : null,
                    '1' => $oddsKeyed->get('29-1') ? [
                        'id' => $oddsKeyed->get('29-1')->id,
                        'value' => $oddsKeyed->get('29-1')->odd
                    ] : null,
                    '2' => $oddsKeyed->get('29-2') ? [
                        'id' => $oddsKeyed->get('29-2')->id,
                        'value' => $oddsKeyed->get('29-2')->odd
                    ] : null,
                    '3' => $oddsKeyed->get('29-3') ? [
                        'id' => $oddsKeyed->get('29-3')->id,
                        'value' => $oddsKeyed->get('29-3')->odd
                    ] : null,
                    '4' => $oddsKeyed->get('29-4') ? [
                        'id' => $oddsKeyed->get('29-4')->id,
                        'value' => $oddsKeyed->get('29-4')->odd
                    ] : null,
                    '5' => $oddsKeyed->get('29-5') ? [
                        'id' => $oddsKeyed->get('29-5')->id,
                        'value' => $oddsKeyed->get('29-5')->odd
                    ] : null,
                    '6' => $oddsKeyed->get('29-6') ? [
                        'id' => $oddsKeyed->get('29-6')->id,
                        'value' => $oddsKeyed->get('29-6')->odd
                    ] : null,
                    '7' => $oddsKeyed->get('29-7') ? [
                        'id' => $oddsKeyed->get('29-7')->id,
                        'value' => $oddsKeyed->get('29-7')->odd
                    ] : null,
                    '8' => $oddsKeyed->get('29-8') ? [
                        'id' => $oddsKeyed->get('29-8')->id,
                        'value' => $oddsKeyed->get('29-8')->odd
                    ] : null,
                    '9' => $oddsKeyed->get('29-9') ? [
                        'id' => $oddsKeyed->get('29-9')->id,
                        'value' => $oddsKeyed->get('29-9')->odd
                    ] : null,
                    '10' => $oddsKeyed->get('29-10') ? [
                        'id' => $oddsKeyed->get('29-10')->id,
                        'value' => $oddsKeyed->get('29-10')->odd
                    ] : null,
                    '10+' => $oddsKeyed->get('29-10+') ? [
                        'id' => $oddsKeyed->get('29-10+')->id,
                        'value' => $oddsKeyed->get('29-10+')->odd
                    ] : null,
                ]),
                'home_score_goal' => array_filter([
                    'yes' => $oddsKeyed->get('30-Yes') ? [
                        'id' => $oddsKeyed->get('30-Yes')->id,
                        'value' => $oddsKeyed->get('30-Yes')->odd
                    ] : null,
                    'no' => $oddsKeyed->get('30-No') ? [
                        'id' => $oddsKeyed->get('30-No')->id,
                        'value' => $oddsKeyed->get('30-No')->odd
                    ] : null,
                ]),
                'away_score_goal' => ([
                    'yes' => $oddsKeyed->get('31-Yes') ? [
                        'id' => $oddsKeyed->get('31-Yes')->id,
                        'value' => $oddsKeyed->get('31-Yes')->odd
                    ] : null,
                    'no' => $oddsKeyed->get('31-No') ? [
                        'id' => $oddsKeyed->get('31-No')->id,
                        'value' => $oddsKeyed->get('31-No')->odd
                    ] : null,
                ]),
                'exact_goals_number_first_half' => array_filter([
                    '0' => $oddsKeyed->get('32-0') ? [
                        'id' => $oddsKeyed->get('32-0')->id,
                        'value' => $oddsKeyed->get('32-0')->odd
                    ] : null,
                    '1' => $oddsKeyed->get('32-1') ? [
                        'id' => $oddsKeyed->get('32-1')->id,
                        'value' => $oddsKeyed->get('32-1')->odd
                    ] : null,
                    '2' => $oddsKeyed->get('32-2') ? [
                        'id' => $oddsKeyed->get('32-2')->id,
                        'value' => $oddsKeyed->get('32-2')->odd
                    ] : null,
                    '3' => $oddsKeyed->get('32-3') ? [
                        'id' => $oddsKeyed->get('32-3')->id,
                        'value' => $oddsKeyed->get('32-3')->odd
                    ] : null,
                    '4' => $oddsKeyed->get('32-4') ? [
                        'id' => $oddsKeyed->get('32-4')->id,
                        'value' => $oddsKeyed->get('32-4')->odd
                    ] : null,
                    '5' => $oddsKeyed->get('32-5') ? [
                        'id' => $oddsKeyed->get('32-5')->id,
                        'value' => $oddsKeyed->get('32-5')->odd
                    ] : null,
                    '6' => $oddsKeyed->get('32-6') ? [
                        'id' => $oddsKeyed->get('32-6')->id,
                        'value' => $oddsKeyed->get('32-6')->odd
                    ] : null,
                    '7' => $oddsKeyed->get('32-7') ? [
                        'id' => $oddsKeyed->get('32-7')->id,
                        'value' => $oddsKeyed->get('32-7')->odd
                    ] : null,
                    '8' => $oddsKeyed->get('32-8') ? [
                        'id' => $oddsKeyed->get('32-8')->id,
                        'value' => $oddsKeyed->get('32-8')->odd
                    ] : null,
                    '9' => $oddsKeyed->get('32-9') ? [
                        'id' => $oddsKeyed->get('32-9')->id,
                        'value' => $oddsKeyed->get('32-9')->odd
                    ] : null,
                    '10' => $oddsKeyed->get('32-10') ? [
                        'id' => $oddsKeyed->get('32-10')->id,
                        'value' => $oddsKeyed->get('32-10')->odd
                    ] : null,
                    '10+' => $oddsKeyed->get('32-10+') ? [
                        'id' => $oddsKeyed->get('32-10+')->id,
                        'value' => $oddsKeyed->get('32-10+')->odd
                    ] : null,
                ]),
            ]),
        ];
    }
}
