<?php

namespace Tests\Unit\Services\MarketSettlementService;

use App\Enums\OddOutcome;
use App\Services\MarketSettlementService;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Unit coverage for the resolver contract. Calls the protected resolvers
 * via reflection so we don't need a fixture or DB row to exercise the
 * outcome logic.
 *
 * The (string, array) resolvers are invoked via resolve(); the
 * (string, int) resolvers — currently only resolveExactGoalsNumber —
 * are invoked via resolveIntArg().
 */
class MarketResolversTest extends TestCase
{
    private MarketSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketSettlementService::class);
    }

    // ───────────────────────────── Market 2 — Home/Away ─────────────────────

    public function test_home_away_home_win_marks_home_won(): void
    {
        $outcome = $this->resolve('resolveHomeAway', 'Home', $this->match(homeGoals: 2, awayGoals: 0));
        $this->assertSame(OddOutcome::Won, $outcome);
    }

    public function test_home_away_away_win_marks_away_won(): void
    {
        $outcome = $this->resolve('resolveHomeAway', 'Away', $this->match(homeGoals: 0, awayGoals: 1));
        $this->assertSame(OddOutcome::Won, $outcome);
    }

    public function test_home_away_draw_voids_both_sides(): void
    {
        $home = $this->resolve('resolveHomeAway', 'Home', $this->match(homeGoals: 1, awayGoals: 1));
        $away = $this->resolve('resolveHomeAway', 'Away', $this->match(homeGoals: 1, awayGoals: 1));

        $this->assertSame(OddOutcome::Void, $home);
        $this->assertSame(OddOutcome::Void, $away);
    }

    public function test_home_away_malformed_value_voids(): void
    {
        $outcome = $this->resolve('resolveHomeAway', 'Draw', $this->match(homeGoals: 2, awayGoals: 0));
        $this->assertSame(OddOutcome::Void, $outcome);
    }

    // ───────────────────────────── Market 9 — European Handicap ─────────────

    #[DataProvider('handicapCases')]
    public function test_handicap_result_resolves_correctly(
        int $homeGoals,
        int $awayGoals,
        string $selection,
        OddOutcome $expected,
    ): void {
        $outcome = $this->resolve(
            'resolveHandicapResult',
            $selection,
            $this->match(homeGoals: $homeGoals, awayGoals: $awayGoals),
        );

        $this->assertSame($expected, $outcome);
    }

    public static function handicapCases(): array
    {
        return [
            // 2-0 → Home -1 wins, Draw -1 loses, Away -1 loses
            'home_win_by_two_home-1' => [2, 0, 'Home -1', OddOutcome::Won],
            'home_win_by_two_draw-1' => [2, 0, 'Draw -1', OddOutcome::Lost],
            'home_win_by_two_away-1' => [2, 0, 'Away -1', OddOutcome::Lost],

            // 1-0 → Draw -1 wins (home wins by exactly 1)
            'home_win_by_one_home-1' => [1, 0, 'Home -1', OddOutcome::Lost],
            'home_win_by_one_draw-1' => [1, 0, 'Draw -1', OddOutcome::Won],
            'home_win_by_one_away-1' => [1, 0, 'Away -1', OddOutcome::Lost],

            // 0-0 → Away -1 wins (home -1 < away)
            'scoreless_away-1' => [0, 0, 'Away -1', OddOutcome::Won],
            'scoreless_home-1' => [0, 0, 'Home -1', OddOutcome::Lost],
            'scoreless_draw-1' => [0, 0, 'Draw -1', OddOutcome::Lost],

            // Positive handicap: 1-0 → Home +1 wins
            'home_plus_one' => [1, 0, 'Home +1', OddOutcome::Won],
        ];
    }

    public function test_handicap_result_malformed_value_voids(): void
    {
        $outcome = $this->resolve('resolveHandicapResult', 'Home -1.5', $this->match(homeGoals: 2, awayGoals: 0));
        $this->assertSame(OddOutcome::Void, $outcome);
    }

    // ───────────────────────────── Market 12 — Double Chance ────────────────

    #[DataProvider('doubleChanceCases')]
    public function test_double_chance_multi_winner(
        int $homeGoals,
        int $awayGoals,
        string $selection,
        OddOutcome $expected,
    ): void {
        $outcome = $this->resolve(
            'resolveDoubleChance',
            $selection,
            $this->match(homeGoals: $homeGoals, awayGoals: $awayGoals),
        );

        $this->assertSame($expected, $outcome);
    }

    public static function doubleChanceCases(): array
    {
        return [
            // Home win: Home/Draw and Home/Away both win
            'home_win_draw' => [2, 0, 'Home/Draw', OddOutcome::Won],
            'home_win_away' => [2, 0, 'Home/Away', OddOutcome::Won],
            'home_win_dc' => [2, 0, 'Draw/Away', OddOutcome::Lost],

            // Away win: Home/Away and Draw/Away both win
            'away_win_home' => [0, 2, 'Home/Draw', OddOutcome::Lost],
            'away_win_away' => [0, 2, 'Home/Away', OddOutcome::Won],
            'away_win_dc' => [0, 2, 'Draw/Away', OddOutcome::Won],

            // Draw: Home/Draw and Draw/Away both win
            'draw_home' => [1, 1, 'Home/Draw', OddOutcome::Won],
            'draw_away' => [1, 1, 'Home/Away', OddOutcome::Lost],
            'draw_dc' => [1, 1, 'Draw/Away', OddOutcome::Won],
        ];
    }

    public function test_double_chance_malformed_value_voids(): void
    {
        $outcome = $this->resolve('resolveDoubleChance', 'Foo/Bar', $this->match(homeGoals: 2, awayGoals: 0));
        $this->assertSame(OddOutcome::Void, $outcome);
    }

    // ───────────────────────────── Market 20 — DC First Half ────────────────

    #[DataProvider('doubleChanceFirstHalfCases')]
    public function test_double_chance_first_half_multi_winner(
        int $htHome,
        int $htAway,
        string $selection,
        OddOutcome $expected,
    ): void {
        $outcome = $this->resolve(
            'resolveDoubleChanceFirstHalf',
            $selection,
            $this->match(homeHT: $htHome, awayHT: $htAway),
        );

        $this->assertSame($expected, $outcome);
    }

    public static function doubleChanceFirstHalfCases(): array
    {
        return [
            'ht_home_win_draw' => [2, 0, 'Home/Draw', OddOutcome::Won],
            'ht_home_win_away' => [2, 0, 'Home/Away', OddOutcome::Won],
            'ht_home_win_dc' => [2, 0, 'Draw/Away', OddOutcome::Lost],

            'ht_away_win_home' => [0, 2, 'Home/Draw', OddOutcome::Lost],
            'ht_away_win_away' => [0, 2, 'Home/Away', OddOutcome::Won],
            'ht_away_win_dc' => [0, 2, 'Draw/Away', OddOutcome::Won],

            'ht_draw_home' => [1, 1, 'Home/Draw', OddOutcome::Won],
            'ht_draw_away' => [1, 1, 'Home/Away', OddOutcome::Lost],
            'ht_draw_dc' => [1, 1, 'Draw/Away', OddOutcome::Won],
        ];
    }

    // ───────────────────────────── Market 14 — Team To Score First ──────────

    public function test_team_to_score_first_home_wins_when_home_scores_first(): void
    {
        $match = $this->match() + ['home_scored_first' => true, 'away_scored_first' => false];
        $this->assertSame(OddOutcome::Won, $this->resolve('resolveTeamToScoreFirst', 'Home', $match));
    }

    public function test_team_to_score_first_away_wins_when_away_scores_first(): void
    {
        $match = $this->match() + ['home_scored_first' => false, 'away_scored_first' => true];
        $this->assertSame(OddOutcome::Won, $this->resolve('resolveTeamToScoreFirst', 'Away', $match));
    }

    public function test_team_to_score_first_no_goal_wins_when_scoreless(): void
    {
        $match = $this->match() + ['home_scored_first' => false, 'away_scored_first' => false];
        $this->assertSame(OddOutcome::Won, $this->resolve('resolveTeamToScoreFirst', 'No goal', $match));
        $this->assertSame(OddOutcome::Won, $this->resolve('resolveTeamToScoreFirst', 'Draw', $match));
    }

    public function test_team_to_score_first_home_loses_when_away_scores_first(): void
    {
        $match = $this->match() + ['home_scored_first' => false, 'away_scored_first' => true];
        $this->assertSame(OddOutcome::Lost, $this->resolve('resolveTeamToScoreFirst', 'Home', $match));
    }

    public function test_team_to_score_first_malformed_value_voids(): void
    {
        $match = $this->match() + ['home_scored_first' => true, 'away_scored_first' => false];
        $this->assertSame(OddOutcome::Void, $this->resolve('resolveTeamToScoreFirst', 'Nobody', $match));
    }

    // ───────────────────────────── Market 15 — Team To Score Last ───────────

    public function test_team_to_score_last_no_goal_wins_when_scoreless(): void
    {
        $match = $this->match() + ['home_scored_last' => false, 'away_scored_last' => false];
        $this->assertSame(OddOutcome::Won, $this->resolve('resolveTeamToScoreLast', 'No goal', $match));
    }

    public function test_team_to_score_last_home_wins_when_home_scores_last(): void
    {
        $match = $this->match() + ['home_scored_last' => true, 'away_scored_last' => false];
        $this->assertSame(OddOutcome::Won, $this->resolve('resolveTeamToScoreLast', 'Home', $match));
    }

    // ───────────────────────────── Market 24 — Result/BTTS ──────────────────

    #[DataProvider('resultBttsCases')]
    public function test_result_btts_resolves_correctly(
        int $homeGoals,
        int $awayGoals,
        string $selection,
        OddOutcome $expected,
    ): void {
        $outcome = $this->resolve(
            'resolveResultsBothTeamsScore',
            $selection,
            $this->match(homeGoals: $homeGoals, awayGoals: $awayGoals),
        );
        $this->assertSame($expected, $outcome);
    }

    public static function resultBttsCases(): array
    {
        return [
            // 2-1: Home win, both scored
            'home_win_btts_home_yes' => [2, 1, 'Home/Yes', OddOutcome::Won],
            'home_win_btts_draw_yes' => [2, 1, 'Draw/Yes', OddOutcome::Lost],
            'home_win_btts_home_no' => [2, 1, 'Home/No', OddOutcome::Lost],

            // 2-0: Home win, BTTS no
            'home_clean_home_no' => [2, 0, 'Home/No', OddOutcome::Won],
            'home_clean_home_yes' => [2, 0, 'Home/Yes', OddOutcome::Lost],

            // 1-1: Draw, both scored
            'draw_btts_draw_yes' => [1, 1, 'Draw/Yes', OddOutcome::Won],
            'draw_btts_away_yes' => [1, 1, 'Away/Yes', OddOutcome::Lost],

            // 0-1: Away win, BTTS no
            'away_clean_away_no' => [0, 1, 'Away/No', OddOutcome::Won],
            'away_clean_away_yes' => [0, 1, 'Away/Yes', OddOutcome::Lost],
        ];
    }

    public function test_result_btts_malformed_value_voids(): void
    {
        $this->assertSame(
            OddOutcome::Void,
            $this->resolve('resolveResultsBothTeamsScore', 'Home & Yes', $this->match(homeGoals: 2, awayGoals: 1)),
        );
    }

    // ───────────────────────────── Market 25 — Result/Total Goals ───────────

    #[DataProvider('resultTotalGoalsCases')]
    public function test_result_total_goals_resolves_correctly(
        int $homeGoals,
        int $awayGoals,
        string $selection,
        OddOutcome $expected,
    ): void {
        $outcome = $this->resolve(
            'resolveResultTotalGoals',
            $selection,
            $this->match(homeGoals: $homeGoals, awayGoals: $awayGoals),
        );
        $this->assertSame($expected, $outcome);
    }

    public static function resultTotalGoalsCases(): array
    {
        return [
            // 3-0: Home win, total 3, Over 2.5
            'home_3_0_over_2_5' => [3, 0, 'Home/Over 2.5', OddOutcome::Won],
            'home_3_0_under_2_5' => [3, 0, 'Home/Under 2.5', OddOutcome::Lost],
            'home_3_0_over_3_5' => [3, 0, 'Home/Over 3.5', OddOutcome::Lost],
            'home_3_0_over_1_5' => [3, 0, 'Home/Over 1.5', OddOutcome::Won],

            // 1-0: Home win, total 1, Under 2.5
            'home_1_0_under_2_5' => [1, 0, 'Home/Under 2.5', OddOutcome::Won],
            'home_1_0_over_2_5' => [1, 0, 'Home/Over 2.5', OddOutcome::Lost],

            // 0-2: Away win, total 2, Under 2.5
            'away_0_2_under_2_5' => [0, 2, 'Away/Under 2.5', OddOutcome::Won],
            'away_0_2_home_side' => [0, 2, 'Home/Under 2.5', OddOutcome::Lost],

            // 1-1: Draw, total 2, Under 2.5
            'draw_1_1_under' => [1, 1, 'Draw/Under 2.5', OddOutcome::Won],
            'draw_1_1_over' => [1, 1, 'Draw/Over 2.5', OddOutcome::Lost],
        ];
    }

    public function test_result_total_goals_malformed_value_voids(): void
    {
        $this->assertSame(
            OddOutcome::Void,
            $this->resolve('resolveResultTotalGoals', 'Home & Over', $this->match(homeGoals: 3, awayGoals: 0)),
        );
    }

    // ───────────────────────────── Markets 38/40/41/42/46 — Exact Goals ─────

    #[DataProvider('exactGoalsCases')]
    public function test_exact_goals_number_resolves_correctly(
        string $selection,
        int $goals,
        OddOutcome $expected,
    ): void {
        $this->assertSame(
            $expected,
            $this->resolveIntArg('resolveExactGoalsNumber', $selection, $goals),
        );
    }

    public static function exactGoalsCases(): array
    {
        return [
            // Exact integer form
            'integer_match' => ['2', 2, OddOutcome::Won],
            'integer_under' => ['2', 1, OddOutcome::Lost],
            'integer_over' => ['2', 3, OddOutcome::Lost],
            'zero_match' => ['0', 0, OddOutcome::Won],
            'zero_miss' => ['0', 1, OddOutcome::Lost],

            // "more N" form (>= N)
            'more_6_hits_at_6' => ['more 6', 6, OddOutcome::Won],
            'more_6_hits_at_9' => ['more 6', 9, OddOutcome::Won],
            'more_6_misses_at_5' => ['more 6', 5, OddOutcome::Lost],
            'more_4_hits_at_4' => ['more 4', 4, OddOutcome::Won],
            'more_4_misses_at_3' => ['more 4', 3, OddOutcome::Lost],

            // Legacy "N+" form
            'plus_6_hits_at_6' => ['6+', 6, OddOutcome::Won],
            'plus_6_hits_at_8' => ['6+', 8, OddOutcome::Won],
            'plus_6_misses' => ['6+', 5, OddOutcome::Lost],

            // Malformed
            'malformed_text' => ['foo', 3, OddOutcome::Void],
            'malformed_spaces' => ['6 +', 6, OddOutcome::Void],
            'malformed_empty' => ['', 0, OddOutcome::Void],
        ];
    }

    // ──────────── Markets 5/6/16/17/26 — Goals Over/Under ───────────────────

    #[DataProvider('overUnderCases')]
    public function test_over_under_resolves_correctly(
        int $goals,
        string $selection,
        OddOutcome $expected,
    ): void {
        $this->assertSame(
            $expected,
            $this->resolveIntArg('resolveOverUnder', $selection, $goals),
        );
    }

    public static function overUnderCases(): array
    {
        return [
            // Half-line thresholds: no push possible
            'over_2_5_at_3' => [3, 'Over 2.5', OddOutcome::Won],
            'over_2_5_at_2' => [2, 'Over 2.5', OddOutcome::Lost],
            'over_2_5_at_0' => [0, 'Over 2.5', OddOutcome::Lost],

            'under_2_5_at_2' => [2, 'Under 2.5', OddOutcome::Won],
            'under_2_5_at_3' => [3, 'Under 2.5', OddOutcome::Lost],

            'under_0_5_at_0' => [0, 'Under 0.5', OddOutcome::Won],
            'under_0_5_at_1' => [1, 'Under 0.5', OddOutcome::Lost],
            'over_0_5_at_1' => [1, 'Over 0.5', OddOutcome::Won],
            'over_0_5_at_0' => [0, 'Over 0.5', OddOutcome::Lost],

            // Integer-line push
            'over_2_0_at_2_pushes' => [2, 'Over 2.0', OddOutcome::Void],
            'under_2_0_at_2_pushes' => [2, 'Under 2.0', OddOutcome::Void],
            'over_2_0_at_3_wins' => [3, 'Over 2.0', OddOutcome::Won],
            'over_2_0_at_1_loses' => [1, 'Over 2.0', OddOutcome::Lost],
            'under_2_0_at_1_wins' => [1, 'Under 2.0', OddOutcome::Won],
            'under_2_0_at_3_loses' => [3, 'Under 2.0', OddOutcome::Lost],

            // Integer line written without decimal
            'over_2_at_2_pushes' => [2, 'Over 2', OddOutcome::Void],
            'over_2_at_3_wins' => [3, 'Over 2', OddOutcome::Won],

            // Malformed → Void (was: Won, via the old return-$oddValue fallback)
            'malformed_ovr' => [2, 'OVR 2.5', OddOutcome::Void],
            'malformed_junk' => [2, 'foo', OddOutcome::Void],
            'malformed_empty' => [2, '', OddOutcome::Void],
            'malformed_trailing' => [2, 'Over 2.5 foo', OddOutcome::Void],
            'malformed_no_space' => [2, 'Over2.5', OddOutcome::Void],
            'malformed_leading' => [2, 'foo Over 2.5', OddOutcome::Void],
        ];
    }

    // ───────────────────────────── Market 32 — Win Both Halves ──────────────

    #[DataProvider('winBothHalvesCases')]
    public function test_win_both_halves_resolves_correctly(
        int $htHome,
        int $htAway,
        int $ftHome,
        int $ftAway,
        string $selection,
        OddOutcome $expected,
    ): void {
        $outcome = $this->resolve(
            'resolveWinBothHalves',
            $selection,
            $this->match(
                homeGoals: $ftHome,
                awayGoals: $ftAway,
                homeHT: $htHome,
                awayHT: $htAway,
            ),
        );

        $this->assertSame($expected, $outcome);
    }

    public static function winBothHalvesCases(): array
    {
        return [
            // Home leads at HT and FT: Home wins, Away loses
            'home_leads_both_home' => [1, 0, 2, 0, 'Home', OddOutcome::Won],
            'home_leads_both_away' => [1, 0, 2, 0, 'Away', OddOutcome::Lost],

            // Away leads at HT and FT: Away wins, Home loses
            'away_leads_both_home' => [0, 1, 0, 2, 'Home', OddOutcome::Lost],
            'away_leads_both_away' => [0, 1, 0, 2, 'Away', OddOutcome::Won],

            // Scoreless throughout: neither achieves it — both lose
            'scoreless_home' => [0, 0, 0, 0, 'Home', OddOutcome::Lost],
            'scoreless_away' => [0, 0, 0, 0, 'Away', OddOutcome::Lost],

            // Draw at FT (HT also drawn): neither achieves it
            'draw_ht_draw_ft_home' => [1, 1, 1, 1, 'Home', OddOutcome::Lost],
            'draw_ht_draw_ft_away' => [1, 1, 1, 1, 'Away', OddOutcome::Lost],

            // Away leads HT, draw FT: Away didn't lead at FT
            'away_ht_draw_ft_home' => [0, 1, 0, 0, 'Home', OddOutcome::Lost],
            'away_ht_draw_ft_away' => [0, 1, 0, 0, 'Away', OddOutcome::Lost],

            // Draw HT, Home leads FT: Home didn't lead at HT
            'draw_ht_home_ft_home' => [0, 0, 1, 0, 'Home', OddOutcome::Lost],
            'draw_ht_home_ft_away' => [0, 0, 1, 0, 'Away', OddOutcome::Lost],

            // Lead changes hands: neither team led at both
            'home_ht_away_ft_home' => [1, 0, 1, 2, 'Home', OddOutcome::Lost],
            'home_ht_away_ft_away' => [1, 0, 1, 2, 'Away', OddOutcome::Lost],
            'away_ht_home_ft_home' => [0, 1, 2, 1, 'Home', OddOutcome::Lost],
            'away_ht_home_ft_away' => [0, 1, 2, 1, 'Away', OddOutcome::Lost],
        ];
    }
    // ───────────────────────────── Helpers ──────────────────────────────────

    /**
     * Invoke a (string $oddValue, array $match) resolver via reflection.
     */
    private function resolve(string $method, string $oddValue, array $match): OddOutcome
    {
        $ref = new ReflectionMethod($this->service, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->service, $oddValue, $match);
    }

    /**
     * Invoke a (string $oddValue, int $goals) resolver via reflection.
     */
    private function resolveIntArg(string $method, string $oddValue, int $goals): OddOutcome
    {
        $ref = new ReflectionMethod($this->service, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->service, $oddValue, $goals);
    }

    /**
     * Minimal match-data array covering just the keys the resolvers read.
     * Matches the shape buildMatchDataFromDb() returns.
     */
    private function match(
        int $homeGoals = 0,
        int $awayGoals = 0,
        int $homeHT = 0,
        int $awayHT = 0,
    ): array {
        return [
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'home_ht_goals' => $homeHT,
            'away_ht_goals' => $awayHT,
            'home_ft_goals' => $homeGoals,
            'away_ft_goals' => $awayGoals,
            'total_goals' => $homeGoals + $awayGoals,
            'ht_total_goals' => $homeHT + $awayHT,
            'st_total_goals' => ($homeGoals - $homeHT) + ($awayGoals - $awayHT),
            'both_scored' => $homeGoals > 0 && $awayGoals > 0,
            'home_winner' => $homeGoals > $awayGoals,
            'away_winner' => $awayGoals > $homeGoals,
            'draw' => $homeGoals === $awayGoals,
            'halftime_home_winner' => $homeHT > $awayHT,
            'halftime_away_winner' => $awayHT > $homeHT,
            'halftime_draw' => $homeHT === $awayHT,
        ];
    }
}