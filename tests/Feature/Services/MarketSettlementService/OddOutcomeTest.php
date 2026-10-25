<?php

namespace Tests\Feature\Services\MarketSettlementService;

use App\Models\Fixture;
use App\Models\Odd;
use App\Services\MarketSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Smoke coverage for the Phase 1 plumbing change: settleOdd now maps an
 * OddOutcome onto odds.status. Uses market 1 (Match Winner) because its
 * resolver is already correct — the full per-market matrix lands later.
 */
class OddOutcomeTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_TEAM_ID = 100;
    private const AWAY_TEAM_ID = 200;
    private const MARKET_MATCH_WINNER = 1;

    private static int $apiIdCounter = 950000;

    private MarketSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketSettlementService::class);
    }

    public function test_odd_is_marked_won_when_its_value_matches_the_resolver_selection(): void
    {
        $fixture = $this->fixtureAwayWins();
        $odd     = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');

        $this->service->settleFixture($fixture);

        $this->assertSame('won', $odd->fresh()->status);
    }

    public function test_odd_is_marked_lost_when_its_value_does_not_match(): void
    {
        $fixture = $this->fixtureAwayWins();
        $odd     = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Home');

        $this->service->settleFixture($fixture);

        $this->assertSame('lost', $odd->fresh()->status);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function fixtureAwayWins(): Fixture
    {
        return Fixture::forceCreate([
            'id_on_api'     => self::$apiIdCounter++,
            'date'          => now(),
            'timestamp'     => now()->timestamp,
            'status_short'  => 'FT',
            'status_long'   => 'Match Finished',
            'home_team_id'  => self::HOME_TEAM_ID,
            'away_team_id'  => self::AWAY_TEAM_ID,
            'goals_home'    => 0, 'goals_away' => 2,
            'halftime_home' => 0, 'halftime_away' => 1,
            'fulltime_home' => 0, 'fulltime_away' => 2,
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