<?php

namespace Tests\Feature\Services\MarketSettlementService;

use App\Models\Fixture;
use App\Models\Odd;
use App\Services\MarketSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettleFixtureTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_TEAM_ID = 100;
    private const AWAY_TEAM_ID = 200;

    // market_id 1 = Match Winner (values: Home | Draw | Away)
    private const MARKET_MATCH_WINNER = 1;

    private static int $apiIdCounter = 900000;

    private MarketSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketSettlementService::class);
    }

    public function test_returns_early_if_fixture_already_settled(): void
    {
        $fixture = $this->makeFixture(['status_short' => 'FT', 'settled' => true]);
        $odd = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');

        $this->service->settleFixture($fixture);

        $this->assertSame('pending', $odd->fresh()->status);
    }

    public function test_does_nothing_for_a_fixture_that_is_not_finished_or_void(): void
    {
        $fixture = $this->makeFixture(['status_short' => 'NS', 'settled' => false]);
        $odd = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');

        $this->service->settleFixture($fixture);

        $this->assertFalse($fixture->fresh()->settled);
        $this->assertSame('pending', $odd->fresh()->status);
    }

    public function test_settles_odds_for_a_finished_fixture(): void
    {
        // Away wins 0-2, so market 1 resolves to 'Away'.
        $fixture = $this->makeFixture([
            'status_short'  => 'FT',
            'settled'       => false,
            'goals_home'    => 0,
            'goals_away'    => 2,
            'halftime_home' => 0,
            'halftime_away' => 1,
            'fulltime_home' => 0,
            'fulltime_away' => 2,
        ]);

        $awayOdd = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');
        $homeOdd = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Home');
        $drawOdd = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Draw');

        $this->service->settleFixture($fixture);

        $this->assertSame('won',  $awayOdd->fresh()->status);
        $this->assertSame('lost', $homeOdd->fresh()->status);
        $this->assertSame('lost', $drawOdd->fresh()->status);
    }

    #[DataProvider('voidStatuses')]
    public function test_voids_all_odds_for_each_void_status(string $status): void
    {
        $fixture = $this->makeFixture([
            'status_short' => $status,
            'settled'      => false,
        ]);

        $awayOdd = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');
        $homeOdd = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Home');

        $this->service->settleFixture($fixture);

        $this->assertSame('void', $awayOdd->fresh()->status);
        $this->assertSame('void', $homeOdd->fresh()->status);
    }

    public static function voidStatuses(): array
    {
        return [
            'cancelled' => ['CANC'],
            'abandoned' => ['ABD'],
            'awarded'   => ['AWD'],
            'walkover'  => ['WO'],
            'postponed' => ['PST'],
        ];
    }

    public function test_marks_fixture_settled_after_processing(): void
    {
        $fixture = $this->makeFixture([
            'status_short'  => 'FT',
            'settled'       => false,
            'goals_home'    => 0,
            'goals_away'    => 2,
            'halftime_home' => 0,
            'halftime_away' => 1,
            'fulltime_home' => 0,
            'fulltime_away' => 2,
        ]);

        $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');

        $this->service->settleFixture($fixture);

        $this->assertTrue($fixture->fresh()->settled);
    }

    public function test_marks_fixture_settled_even_when_it_has_no_odds(): void
    {
        $fixture = $this->makeFixture([
            'status_short'  => 'FT',
            'settled'       => false,
            'goals_home'    => 0,
            'goals_away'    => 2,
            'halftime_home' => 0,
            'halftime_away' => 1,
            'fulltime_home' => 0,
            'fulltime_away' => 2,
        ]);

        $this->service->settleFixture($fixture);

        $this->assertTrue($fixture->fresh()->settled);
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
            'goals_home'    => 0,
            'goals_away'    => 0,
            'halftime_home' => 0,
            'halftime_away' => 0,
            'fulltime_home' => 0,
            'fulltime_away' => 0,
            'home_winner'   => false,
            'settled'       => false,
        ], $attributes));
    }

    private function makeOdd(
        Fixture $fixture,
        int $marketId,
        string $value,
        string $status = 'pending',
    ): Odd {
        $id = DB::table('odds')->insertGetId([
            'fixture_id' => $fixture->id,
            'market_id'  => $marketId,
            'value'      => $value,
            'odd'        => 2.50,
            'status'     => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Odd::find($id);
    }
}