<?php

namespace Tests\Unit\Services;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Prediction;
use App\Models\User;
use App\Services\PredictionScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionScoringServiceTest extends TestCase
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

    private function service(): PredictionScoringService
    {
        return app(PredictionScoringService::class);
    }

    public function test_scores_winning_prediction(): void
    {
        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => $market->id, 'value' => 'Home', 'odd' => 2.00, 'status' => 'won']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => $market->id, 'value' => 'Away', 'odd' => 3.00, 'status' => 'lost']);

        $user = User::factory()->create(['prediction_points' => 0]);
        $prediction = Prediction::create([
            'user_id' => $user->id,
            'fixture_id' => $fixture->id,
            'market_id' => $market->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'confidence' => 5,
            'source_type' => 'betslip',
        ]);

        $this->service()->scoreFixture($fixture->id);

        $prediction->refresh();
        $this->assertSame('won', $prediction->status);
        $this->assertSame(50, $prediction->points_impact);
        $this->assertSame(50, $user->fresh()->prediction_points);
    }

    public function test_scores_losing_prediction_negatively(): void
    {
        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => $market->id, 'value' => 'Home', 'odd' => 2.00, 'status' => 'lost']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => $market->id, 'value' => 'Away', 'odd' => 3.00, 'status' => 'won']);

        $user = User::factory()->create(['prediction_points' => 0]);
        $prediction = Prediction::create([
            'user_id' => $user->id,
            'fixture_id' => $fixture->id,
            'market_id' => $market->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'confidence' => 5,
            'source_type' => 'betslip',
        ]);

        $this->service()->scoreFixture($fixture->id);

        $prediction->refresh();
        $this->assertSame('lost', $prediction->status);
        $this->assertSame(-75, $prediction->points_impact);
        $this->assertSame(-75, $user->fresh()->prediction_points);
    }

    public function test_void_market_scores_zero(): void
    {
        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => $market->id, 'value' => 'Home', 'odd' => 2.00, 'status' => 'void']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => $market->id, 'value' => 'Away', 'odd' => 3.00, 'status' => 'void']);

        $user = User::factory()->create(['prediction_points' => 100]);
        $prediction = Prediction::create([
            'user_id' => $user->id,
            'fixture_id' => $fixture->id,
            'market_id' => $market->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'confidence' => 5,
            'source_type' => 'betslip',
        ]);

        $this->service()->scoreFixture($fixture->id);

        $prediction->refresh();
        $this->assertSame('void', $prediction->status);
        $this->assertSame(0, $prediction->points_impact);
        $this->assertSame(100, $user->fresh()->prediction_points);
    }

    public function test_scoring_is_idempotent(): void
    {
        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => $market->id, 'value' => 'Home', 'odd' => 2.00, 'status' => 'won']);

        $user = User::factory()->create(['prediction_points' => 0]);
        Prediction::create([
            'user_id' => $user->id,
            'fixture_id' => $fixture->id,
            'market_id' => $market->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'confidence' => 5,
            'source_type' => 'betslip',
        ]);

        $this->service()->scoreFixture($fixture->id);
        $this->service()->scoreFixture($fixture->id); // Second call should be a no-op

        $this->assertSame(50, $user->fresh()->prediction_points);
    }
}