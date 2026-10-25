<?php

namespace Tests\Unit\Services\MarketSettlementService;

use App\Models\Fixture;
use App\Services\MarketSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuildMatchDataTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_TEAM_ID = 100;
    private const AWAY_TEAM_ID = 200;

    private static int $apiIdCounter = 900000;

    private MarketSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MarketSettlementService::class);
    }

    public function test_builds_match_data_for_a_typical_finished_match(): void
    {
        $fixture = $this->makeFixture([
            'goals_home'    => 0,
            'goals_away'    => 2,
            'halftime_home' => 0,
            'halftime_away' => 1,
            'fulltime_home' => 0,
            'fulltime_away' => 2,
        ]);

        $this->insertGoalEvent($fixture, self::AWAY_TEAM_ID, 20);
        $this->insertGoalEvent($fixture, self::HOME_TEAM_ID, 85, 'Own Goal');

        $data = $this->service->buildMatchDataFromDb($fixture);

        $this->assertSame(0, $data['home_goals']);
        $this->assertSame(2, $data['away_goals']);
        $this->assertSame(2, $data['total_goals']);
        $this->assertSame(1, $data['ht_total_goals']);
        $this->assertSame(1, $data['st_total_goals']);

        $this->assertFalse($data['both_scored']);
        $this->assertTrue($data['away_scored_first']);
        $this->assertTrue($data['home_scored_last']);

        $this->assertFalse($data['home_winner']);
        $this->assertTrue($data['away_winner']);
        $this->assertFalse($data['draw']);
        $this->assertFalse($data['halftime_home_winner']);
        $this->assertTrue($data['halftime_away_winner']);
        $this->assertFalse($data['halftime_draw']);

        $this->assertSame('0:2', $data['score']);
    }

    public function test_handles_a_scoreless_draw(): void
    {
        $fixture = $this->makeFixture([
            'goals_home'    => 0,
            'goals_away'    => 0,
            'halftime_home' => 0,
            'halftime_away' => 0,
            'fulltime_home' => 0,
            'fulltime_away' => 0,
        ]);

        $data = $this->service->buildMatchDataFromDb($fixture);

        $this->assertSame(0, $data['total_goals']);
        $this->assertFalse($data['both_scored']);
        $this->assertFalse($data['home_scored_first']);
        $this->assertFalse($data['away_scored_first']);
        $this->assertFalse($data['home_scored_last']);
        $this->assertFalse($data['away_scored_last']);
        $this->assertTrue($data['draw']);
        $this->assertTrue($data['halftime_draw']);
        $this->assertSame('0:0', $data['score']);
    }

    public function test_derives_second_half_goals_from_fulltime_minus_halftime(): void
    {
        // HT 1-0, FT 3-1 → second half 2-1 → 3 goals total in 2H.
        $fixture = $this->makeFixture([
            'goals_home'    => 3,
            'goals_away'    => 1,
            'halftime_home' => 1,
            'halftime_away' => 0,
            'fulltime_home' => 3,
            'fulltime_away' => 1,
        ]);

        $data = $this->service->buildMatchDataFromDb($fixture);

        $this->assertSame(4, $data['total_goals']);
        $this->assertSame(1, $data['ht_total_goals']);
        $this->assertSame(3, $data['st_total_goals']);
        $this->assertTrue($data['both_scored']);
        $this->assertTrue($data['home_winner']);
    }

    public function test_orders_first_and_last_scorer_by_elapsed_then_extra_then_id(): void
    {
        $fixture = $this->makeFixture([
            'goals_home'    => 1,
            'goals_away'    => 1,
            'halftime_home' => 0,
            'halftime_away' => 0,
            'fulltime_home' => 1,
            'fulltime_away' => 1,
        ]);

        // Inserted out of order on purpose.
        $this->insertGoalEvent($fixture, self::HOME_TEAM_ID, 90, 'Normal Goal', 3);
        $this->insertGoalEvent($fixture, self::AWAY_TEAM_ID, 12);

        $data = $this->service->buildMatchDataFromDb($fixture);

        $this->assertTrue($data['away_scored_first']);
        $this->assertTrue($data['home_scored_last']);
    }

    // ------------------------------------------------------------------
    // Helpers — no test_ prefix
    // ------------------------------------------------------------------

    private function makeFixture(array $attributes = []): Fixture
    {
        return Fixture::forceCreate(array_merge([
            'id_on_api'     => self::$apiIdCounter++,
            'date'          => now(),
            'timestamp'     => now()->timestamp,
            'status_short'  => 'FT',
            'status_long'   => 'Match Finished',
            'home_team_id'  => self::HOME_TEAM_ID,
            'away_team_id'  => self::AWAY_TEAM_ID,
            'home_winner'   => false,
            'settled'       => false,
        ], $attributes));
    }

    private function insertGoalEvent(
        Fixture $fixture,
        int $teamId,
        int $elapsed,
        string $detail = 'Normal Goal',
        ?int $extra = null,
    ): void {
        DB::table('fixture_events')->insert([
            'fixture_id'   => $fixture->id,
            'team_id'      => $teamId,
            'type'         => 'Goal',
            'detail'       => $detail,
            'time_elapsed' => $elapsed,
            'time_extra'   => $extra,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}