<?php

namespace Tests\Unit\Models;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        League::firstOrCreate(['id' => 39], [
            'id_on_api' => 39,
            'sport_id' => 1,
            'name' => 'Premier League',
            'country_id' => 1,
            'country' => 'England',
            'season' => 2026,
        ]);
    }

    private function fixture(): Fixture
    {
        return Fixture::factory()->create(['league_id' => 39]);
    }

    private function market(): Market
    {
        return Market::factory()->create();
    }

    public function test_potential_points_uses_confidence_and_odds(): void
    {
        $p = new Prediction([
            'confidence' => 5,
            'odds_at_pick' => 2.00,
        ]);

        // 5 × (2.00 - 1) × 10 = 50
        $this->assertSame(50, $p->potentialPoints());
    }

    public function test_potential_points_scales_with_odds(): void
    {
        $p = new Prediction([
            'confidence' => 5,
            'odds_at_pick' => 10.00,
        ]);

        // 5 × (10.00 - 1) × 10 = 450
        $this->assertSame(450, $p->potentialPoints());
    }

    public function test_potential_points_at_even_odds_is_zero(): void
    {
        $p = new Prediction([
            'confidence' => 3,
            'odds_at_pick' => 1.00,
        ]);

        // 3 × (1.00 - 1) × 10 = 0
        $this->assertSame(0, $p->potentialPoints());
    }

    public function test_potential_penalty_is_1_5x_the_reward(): void
    {
        $p = new Prediction([
            'confidence' => 5,
            'odds_at_pick' => 2.00,
        ]);

        // 5 × (2.00 - 1) × 15 = 75
        $this->assertSame(75, $p->potentialPenalty());
    }

    public function test_potential_penalty_scales_with_odds(): void
    {
        $p = new Prediction([
            'confidence' => 5,
            'odds_at_pick' => 10.00,
        ]);

        // 5 × (10.00 - 1) × 15 = 675
        $this->assertSame(675, $p->potentialPenalty());
    }

    public function test_unique_constraint_prevents_duplicate_predictions(): void
    {
        $user = User::factory()->create();
        $fixture = $this->fixture();
        $market = $this->market();

        Prediction::create([
            'user_id' => $user->id,
            'fixture_id' => $fixture->id,
            'market_id' => $market->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'confidence' => 3,
            'source_type' => 'betslip',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Prediction::create([
            'user_id' => $user->id,
            'fixture_id' => $fixture->id,
            'market_id' => $market->id,
            'selection' => 'Away',
            'odds_at_pick' => 3.00,
            'confidence' => 4,
            'source_type' => 'jackpot',
        ]);
    }

    public function test_belongs_to_user_fixture_and_market(): void
    {
        $user = User::factory()->create();
        $fixture = $this->fixture();
        $market = $this->market();

        $p = Prediction::create([
            'user_id' => $user->id,
            'fixture_id' => $fixture->id,
            'market_id' => $market->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'confidence' => 3,
            'source_type' => 'betslip',
        ]);

        $this->assertTrue($p->user->is($user));
        $this->assertTrue($p->fixture->is($fixture));
        $this->assertTrue($p->market->is($market));
    }
}