<?php

namespace Tests\Feature\Services;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Prediction;
use App\Models\User;
use App\Services\MarketSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketSettlementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        League::firstOrCreate(['id' => 39], [
            'id_on_api' => 39,
            'sport_id' => 1,
            'name' => 'PL',
            'country_id' => 1,
            'country' => 'England',
            'season' => 2026,
        ]);
    }

    public function test_settling_fixture_triggers_prediction_scoring(): void
    {
        $fixture = Fixture::factory()->create([
            'league_id' => 39,
            'status_short' => 'FT',
            'goals_home' => 2,
            'goals_away' => 0,
            'home_winner' => true,
            'settled' => false,
        ]);
        $market = Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Home', 'odd' => 2.00, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Away', 'odd' => 3.00, 'status' => 'pending']);

        $user = User::factory()->create(['prediction_points' => 0]);
        $prediction = Prediction::create([
            'user_id' => $user->id,
            'fixture_id' => $fixture->id,
            'market_id' => 1,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'confidence' => 5,
            'source_type' => 'betslip',
        ]);

        app(MarketSettlementService::class)->settleFixture($fixture);

        $prediction->refresh();
        $this->assertSame('won', $prediction->status);
        $this->assertSame(50, $user->fresh()->prediction_points);
    }
}