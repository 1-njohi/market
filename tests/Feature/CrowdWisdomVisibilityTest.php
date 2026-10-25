<?php

namespace Tests\Feature;

use App\Models\Betslip;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Prediction;
use App\Models\User;
use App\Services\CrowdWisdomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrowdWisdomVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure a league exists for fixtures
        League::firstOrCreate(['id' => 39], [
            'id_on_api' => 39, 
            'sport_id' => 1, 
            'name' => 'Premier League',
            'country_id' => 1, 
            'country' => 'England', 
            'season' => 2026,
        ]);

        // Set a deterministic default for percentile testing
        config()->set('marketplace.crowd_wisdom.top_percentiles', [5, 10, 25, 50]);
    }

    public function test_distribution_hidden_below_threshold(): void
    {
        config()->set('marketplace.crowd_wisdom.min_threshold', 50);

        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();

        // 10 predictions — well below 50
        foreach (range(1, 10) as $_) {
            $u = User::factory()->create();
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

        $result = app(CrowdWisdomService::class)->forFixture($fixture->id, $market->id);

        $this->assertFalse($result['is_visible']);
        $this->assertSame(10, $result['raw_count']);
        $this->assertSame(50, $result['threshold']);
        
        // No data should leak
        $this->assertNull($result['all']);
        $this->assertNull($result['sellers']);
        $this->assertNull($result['top_percentiles']);
    }

    public function test_distribution_visible_at_threshold(): void
    {
        config()->set('marketplace.crowd_wisdom.min_threshold', 5);

        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();

        // Exactly 5 predictions — at the threshold
        foreach (range(1, 5) as $_) {
            $u = User::factory()->create();
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

        $result = app(CrowdWisdomService::class)->forFixture($fixture->id, $market->id);

        $this->assertTrue($result['is_visible']);
        $this->assertSame(5, $result['raw_count']);
        $this->assertSame(100, $result['all']['Home']);
        
        // Sellers view is null because none of these users have a listed slip
        $this->assertNull($result['sellers']);
    }

    public function test_sellers_lens_only_includes_listed_slip_owners(): void
    {
        config()->set('marketplace.crowd_wisdom.min_threshold', 2);

        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();

        // User A: has a listed betslip → counts as seller
        $seller = User::factory()->create();
        Betslip::create([
            'user_id' => $seller->id, 
            'code' => 'SELLER1',
            'total_odds' => 2.0, 
            'price' => 100, 
            'status' => 'pending',
            'remaining' => 1, 
            'is_winner' => false, 
            'is_sellable' => true,
        ]);
        Prediction::create([
            'user_id' => $seller->id, 
            'fixture_id' => $fixture->id,
            'market_id' => $market->id, 
            'selection' => 'Home',
            'odds_at_pick' => 2.00, 
            'confidence' => 3, 
            'source_type' => 'betslip',
        ]);

        // User B: has a private (non-sellable) slip → NOT a seller
        $privateUser = User::factory()->create();
        Betslip::create([
            'user_id' => $privateUser->id, 
            'code' => 'PRIVATE1',
            'total_odds' => 2.0, 
            'price' => 100, 
            'status' => 'pending',
            'remaining' => 1, 
            'is_winner' => false, 
            'is_sellable' => false,
        ]);
        Prediction::create([
            'user_id' => $privateUser->id, 
            'fixture_id' => $fixture->id,
            'market_id' => $market->id, 
            'selection' => 'Away',
            'odds_at_pick' => 3.00, 
            'confidence' => 3, 
            'source_type' => 'betslip',
        ]);

        $result = app(CrowdWisdomService::class)->forFixture($fixture->id, $market->id);

        $this->assertTrue($result['is_visible']);
        $this->assertSame(2, $result['raw_count']);
        
        // 'All' view sees both
        $this->assertSame(50, $result['all']['Home']);
        $this->assertSame(50, $result['all']['Away']);

        // 'Sellers' view only sees Home
        $this->assertSame(100, $result['sellers']['Home']);
        $this->assertArrayNotHasKey('Away', $result['sellers']);
    }

    public function test_top_percentile_lenses_are_weighted_by_reputation(): void
    {
        config()->set('marketplace.crowd_wisdom.min_threshold', 2);

        $fixture = Fixture::factory()->create(['league_id' => 39]);
        $market = Market::factory()->create();

        // 1. The "Oracle" (Top rank, high points) picks Away
        $oracle = User::factory()->create(['prediction_points' => 1000]);
        Prediction::create([
            'user_id' => $oracle->id, 
            'fixture_id' => $fixture->id,
            'market_id' => $market->id, 
            'selection' => 'Away',
            'odds_at_pick' => 3.00, 
            'confidence' => 5, 
            'source_type' => 'betslip',
        ]);

        // 2. A low-reputation user picks Home
        $novice = User::factory()->create(['prediction_points' => 10]);
        Prediction::create([
            'user_id' => $novice->id, 
            'fixture_id' => $fixture->id,
            'market_id' => $market->id, 
            'selection' => 'Home',
            'odds_at_pick' => 2.00, 
            'confidence' => 5, 
            'source_type' => 'betslip',
        ]);

        $result = app(CrowdWisdomService::class)->forFixture($fixture->id, $market->id);

        // The 'All' view is weighted by confidence AND reputation.
        // Oracle: 5 * repWeight(1000) ≈ 5 * 2.0 = 10
        // Novice: 5 * repWeight(10) ≈ 5 * 1.1 = 5.5
        // Total: 15.5. Away = 10/15.5 = 64%. Home = 5.5/15.5 = 36%.
        $this->assertGreaterThan($result['all']['Home'], $result['all']['Away']);

        // The 'Top 5%' view should only contain the Oracle.
        // Since there are 2 total ranked users, ceil(2 * 0.05) = 1.
        // So top 5% = just the Oracle.
        $top5 = $result['top_percentiles'][5] ?? null;
        
        $this->assertNotNull($top5);
        $this->assertSame(100, $top5['Away']);
        $this->assertArrayNotHasKey('Home', $top5);
    }
}