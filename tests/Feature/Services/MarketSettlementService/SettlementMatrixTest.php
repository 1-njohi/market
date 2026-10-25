<?php

namespace Tests\Feature\Services\MarketSettlementService;

use App\Models\Fixture;
use App\Models\Odd;
use App\Services\MarketSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Integration coverage: exercises settleFixture() end-to-end against
 * fixtures with odds across multiple markets, and asserts each odd lands
 * on the correct status.
 *
 * Complements the unit-level resolver coverage in MarketResolversTest —
 * that file proves the resolver logic; this one proves the wiring.
 */
class SettlementMatrixTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_TEAM_ID = 100;
    private const AWAY_TEAM_ID = 200;

    private static int $apiIdCounter = 980000;

    private MarketSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketSettlementService::class);
    }

    // ────────────── §6.2 — Same market, both sides resolve independently ────

    public function test_over_and_under_on_same_market_resolve_independently(): void
    {
        // Fixture ends 0-2, total goals = 2.
        $fixture = $this->makeFixture(homeGoals: 0, awayGoals: 2);

        $over  = $this->makeOdd($fixture, 5, 'Over 2.5');
        $under = $this->makeOdd($fixture, 5, 'Under 2.5');

        $this->service->settleFixture($fixture);

        $this->assertSame('lost', $over->fresh()->status);
        $this->assertSame('won',  $under->fresh()->status);
    }

    public function test_three_way_market_resolves_all_three_selections(): void
    {
        // 0-2 away win.
        $fixture = $this->makeFixture(homeGoals: 0, awayGoals: 2);

        $home = $this->makeOdd($fixture, 1, 'Home');
        $draw = $this->makeOdd($fixture, 1, 'Draw');
        $away = $this->makeOdd($fixture, 1, 'Away');

        $this->service->settleFixture($fixture);

        $this->assertSame('lost', $home->fresh()->status);
        $this->assertSame('lost', $draw->fresh()->status);
        $this->assertSame('won',  $away->fresh()->status);
    }

    public function test_double_chance_marks_both_winning_selections_as_won(): void
    {
        // 2-0 home win: Home/Draw and Home/Away both win, Draw/Away loses.
        $fixture = $this->makeFixture(homeGoals: 2, awayGoals: 0);

        $hd = $this->makeOdd($fixture, 12, 'Home/Draw');
        $ha = $this->makeOdd($fixture, 12, 'Home/Away');
        $da = $this->makeOdd($fixture, 12, 'Draw/Away');

        $this->service->settleFixture($fixture);

        $this->assertSame('won',  $hd->fresh()->status);
        $this->assertSame('won',  $ha->fresh()->status);
        $this->assertSame('lost', $da->fresh()->status);
    }

    // ────────────── §6.5 — Settlement matrix on a single fixture ────────────

    public function test_multi_market_fixture_resolves_all_odds_correctly(): void
    {
        // 3-2 home win. Total 5. Both scored. HT 1-1.
        $fixture = $this->makeFixture(
            homeGoals: 3, awayGoals: 2,
            homeHT: 1, awayHT: 1,
        );

        $odds = [
            // market, value, expected status
            [1,  'Home',           'won'],
            [1,  'Draw',           'lost'],
            [1,  'Away',           'lost'],

            [5,  'Over 2.5',       'won'],
            [5,  'Under 2.5',      'lost'],
            [5,  'Over 4.5',       'won'],
            [5,  'Over 5.5',       'lost'],

            [8,  'Yes',            'won'],
            [8,  'No',             'lost'],

            [10, '3:2',            'won'],
            [10, '2:1',            'lost'],

            [12, 'Home/Draw',      'won'],
            [12, 'Home/Away',      'won'],
            [12, 'Draw/Away',      'lost'],

            [21, 'Odd',            'won'],
            [21, 'Even',           'lost'],

            [24, 'Home/Yes',       'won'],
            [24, 'Home/No',        'lost'],
            [24, 'Draw/Yes',       'lost'],
        ];

        $created = [];
        foreach ($odds as [$marketId, $value, $expected]) {
            $created[] = [
                'odd'      => $this->makeOdd($fixture, $marketId, $value),
                'expected' => $expected,
            ];
        }

        $this->service->settleFixture($fixture);

        $failures = [];
        foreach ($created as $row) {
            $actual = $row['odd']->fresh()->status;

            if ($actual !== $row['expected']) {
                $failures[] = sprintf(
                    'Odd #%d (market %d, "%s"): expected %s, got %s',
                    $row['odd']->id,
                    $row['odd']->market_id,
                    $row['odd']->value,
                    $row['expected'],
                    $actual,
                );
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_scoreless_draw_resolves_multi_market_fixture(): void
    {
        // 0-0.
        $fixture = $this->makeFixture(homeGoals: 0, awayGoals: 0);

        $odds = [
            [1,  'Home',        'lost'],
            [1,  'Draw',        'won'],
            [1,  'Away',        'lost'],

            [5,  'Over 0.5',    'lost'],
            [5,  'Under 0.5',   'won'],

            [8,  'Yes',         'lost'],
            [8,  'No',          'won'],

            [10, '0:0',         'won'],
            [10, '1:0',         'lost'],

            [14, 'No goal',     'won'],
            [14, 'Home',        'lost'],
            [14, 'Away',        'lost'],

            [21, 'Even',        'won'],
            [21, 'Odd',         'lost'],

            [24, 'Draw/No',     'won'],
            [24, 'Draw/Yes',    'lost'],
            [24, 'Home/No',     'lost'],
        ];

        $created = [];
        foreach ($odds as [$marketId, $value, $expected]) {
            $created[] = [
                'odd'      => $this->makeOdd($fixture, $marketId, $value),
                'expected' => $expected,
            ];
        }

        $this->service->settleFixture($fixture);

        foreach ($created as $row) {
            $this->assertSame(
                $row['expected'],
                $row['odd']->fresh()->status,
                "Odd #{$row['odd']->id} (market {$row['odd']->market_id}, value \"{$row['odd']->value}\") resolved to the wrong status.",
            );
        }
    }

    // ────────────── §6.6 — Monotonicity across Over/Under totals ────────────

    #[DataProvider('overUnderTotals')]
    public function test_over_under_2_5_resolves_correctly(int $totalGoals, string $expectation): void
    {
        $fixture = $this->makeFixture(homeGoals: $totalGoals, awayGoals: 0);

        $over  = $this->makeOdd($fixture, 5, 'Over 2.5');
        $under = $this->makeOdd($fixture, 5, 'Under 2.5');

        $this->service->settleFixture($fixture);

        if ($expectation === 'over') {
            $this->assertSame('won',  $over->fresh()->status);
            $this->assertSame('lost', $under->fresh()->status);
        } else {
            $this->assertSame('lost', $over->fresh()->status);
            $this->assertSame('won',  $under->fresh()->status);
        }
    }

    public static function overUnderTotals(): array
    {
        return [
            'total_0' => [0, 'under'],
            'total_1' => [1, 'under'],
            'total_2' => [2, 'under'],
            'total_3' => [3, 'over'],
            'total_4' => [4, 'over'],
            'total_5' => [5, 'over'],
            'total_6' => [6, 'over'],
            'total_7' => [7, 'over'],
        ];
    }

    public function test_exactly_one_over_under_selection_wins_for_every_total(): void
    {
        for ($total = 0; $total <= 10; $total++) {
            $fixture = $this->makeFixture(homeGoals: $total, awayGoals: 0);

            $over  = $this->makeOdd($fixture, 5, 'Over 2.5');
            $under = $this->makeOdd($fixture, 5, 'Under 2.5');

            $this->service->settleFixture($fixture);

            $overStatus  = $over->fresh()->status;
            $underStatus = $under->fresh()->status;

            $wins = ($overStatus  === 'won' ? 1 : 0) + ($underStatus === 'won' ? 1 : 0);

            $this->assertSame(
                1,
                $wins,
                "Total {$total}: expected exactly one Over/Under winner, got Over={$overStatus}, Under={$underStatus}.",
            );
        }
    }

    // ────────────── Helpers ─────────────────────────────────────────────────

    private function makeFixture(
        int $homeGoals = 0,
        int $awayGoals = 0,
        int $homeHT = 0,
        int $awayHT = 0,
    ): Fixture {
        $totalGoals = $homeGoals + $awayGoals;

        return Fixture::forceCreate([
            'id_on_api'     => self::$apiIdCounter++,
            'date'          => now(),
            'timestamp'     => now()->timestamp,
            'status_short'  => 'FT',
            'status_long'   => 'Match Finished',
            'home_team_id'  => self::HOME_TEAM_ID,
            'away_team_id'  => self::AWAY_TEAM_ID,
            'goals_home'    => $homeGoals,
            'goals_away'    => $awayGoals,
            'halftime_home' => $homeHT,
            'halftime_away' => $awayHT,
            'fulltime_home' => $homeGoals,
            'fulltime_away' => $awayGoals,
            'home_winner'   => false,
            'settled'       => false,
        ]);
    }

    private function makeOdd(Fixture $fixture, int $marketId, string $value): Odd
    {
        $id = DB::table('odds')->insertGetId([
            'fixture_id' => $fixture->id,
            'market_id'  => $marketId,
            'value'      => $value,
            'odd'        => 2.50,
            'status'     => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Odd::find($id);
    }
}