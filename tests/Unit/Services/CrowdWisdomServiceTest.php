<?php

namespace Tests\Unit\Services;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Prediction;
use App\Models\User;
use App\Services\CrowdWisdomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrowdWisdomServiceTest extends TestCase
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

    private function service(): CrowdWisdomService
    {
        return app(CrowdWisdomService::class);
    }

    public function test_returns_empty_when_no_predictions(): void
    {
        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();

        $result = $this->service()->forFixture($fixture->id, $market->id);

        $this->assertSame([], $result['consensus']);
        $this->assertSame(0, $result['raw_count']);
    }

    public function test_computes_confidence_weighted_percentages(): void
    {
        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();

        // Three users pick Home (confidence 5, 5, 5) → total 15
        // One user picks Away (confidence 1) → total 1
        // Weighted: Home 15/16 ≈ 94%, Away 1/16 ≈ 6%
        foreach ([5, 5, 5] as $conf) {
            $u = User::factory()->create(['prediction_points' => 0]);
            Prediction::create([
                'user_id' => $u->id,
                'fixture_id' => $fixture->id,
                'market_id' => $market->id,
                'selection' => 'Home',
                'odds_at_pick' => 2.00,
                'confidence' => $conf,
                'source_type' => 'betslip',
            ]);
        }
        $u = User::factory()->create(['prediction_points' => 0]);
        Prediction::create([
            'user_id' => $u->id,
            'fixture_id' => $fixture->id,
            'market_id' => $market->id,
            'selection' => 'Away',
            'odds_at_pick' => 3.00,
            'confidence' => 1,
            'source_type' => 'betslip',
        ]);

        $result = $this->service()->forFixture($fixture->id, $market->id);

        $this->assertSame(94, $result['consensus']['Home']);
        $this->assertSame(6, $result['consensus']['Away']);
        $this->assertSame(4, $result['raw_count']);
    }

    public function test_confidence_outweighs_raw_count(): void
    {
        // 2 users pick Away at conf 5 → weight 10
        // 3 users pick Home at conf 1 → weight 3
        // Weighted: Away 10/13 ≈ 77%, Home 3/13 ≈ 23%
        // Raw count would say Home wins 3-2. Weighted says Away wins.
        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();

        foreach ([5, 5] as $conf) {
            $u = User::factory()->create(['prediction_points' => 0]);
            Prediction::create([
                'user_id' => $u->id,
                'fixture_id' => $fixture->id,
                'market_id' => $market->id,
                'selection' => 'Away',
                'odds_at_pick' => 3.00,
                'confidence' => $conf,
                'source_type' => 'betslip',
            ]);
        }
        foreach ([1, 1, 1] as $conf) {
            $u = User::factory()->create(['prediction_points' => 0]);
            Prediction::create([
                'user_id' => $u->id,
                'fixture_id' => $fixture->id,
                'market_id' => $market->id,
                'selection' => 'Home',
                'odds_at_pick' => 2.00,
                'confidence' => $conf,
                'source_type' => 'betslip',
            ]);
        }

        $result = $this->service()->forFixture($fixture->id, $market->id);

        $this->assertGreaterThan($result['consensus']['Home'], $result['consensus']['Away']);
    }

    public function test_reputation_weight_boosts_sharp_users(): void
    {
        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();

        // Oracle: +5000 points → weight = 1 + log10(51) ≈ 2.7
        $oracle = User::factory()->create(['prediction_points' => 5000]);
        Prediction::create([
            'user_id' => $oracle->id,
            'fixture_id' => $fixture->id,
            'market_id' => $market->id,
            'selection' => 'Away',
            'odds_at_pick' => 3.00,
            'confidence' => 5,
            'source_type' => 'betslip',
        ]);

        // Three normal users: 0 points → weight 1 each
        foreach (range(1, 3) as $i) {
            $u = User::factory()->create(['prediction_points' => 0]);
            Prediction::create([
                'user_id' => $u->id,
                'fixture_id' => $fixture->id,
                'market_id' => $market->id,
                'selection' => 'Home',
                'odds_at_pick' => 2.00,
                'confidence' => 3,
                'source_type' => 'betslip',
            ]);
        }

        $result = $this->service()->forFixture($fixture->id, $market->id);

        // Oracle: 5 × 2.7 = 13.5
        // Three normals: 3 × 3 = 9
        // Total: 22.5
        // Away: 13.5/22.5 = 60%, Home: 9/22.5 = 40%
        $this->assertSame(60, $result['consensus']['Away']);
        $this->assertSame(40, $result['consensus']['Home']);
    }
}