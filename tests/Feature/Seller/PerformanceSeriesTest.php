<?php

namespace Tests\Feature\Seller;

use App\Models\User;
use App\Services\SellerDashboardService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase; 

/**
 * Contract for seller_data.performance.series.
 *
 * Same shape as the buyer, with the seller-specific addition of a
 * revenue series for the Earnings card sparkline.
 *
 * These tests will FAIL until the service is updated.
 */
class PerformanceSeriesTest extends TestCase
{
    use RefreshDatabase; 
    private function service(): SellerDashboardService
    {
        return app(SellerDashboardService::class);
    }

    public function test_performance_exposes_series_block(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('series', $data['performance']);
    }

    public function test_win_rate_series_is_array(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('win_rate_7d', $data['performance']['series']);
        $this->assertIsArray($data['performance']['series']['win_rate_7d']);
    }

    public function test_revenue_series_is_array(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('revenue_7d', $data['performance']['series']);
        $this->assertIsArray($data['performance']['series']['revenue_7d']);
    }

    public function test_series_have_at_most_seven_points(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertLessThanOrEqual(
            7,
            count($data['performance']['series']['win_rate_7d'])
        );
        $this->assertLessThanOrEqual(
            7,
            count($data['performance']['series']['revenue_7d'])
        );
    }

    public function test_series_values_are_numeric(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        foreach ($data['performance']['series']['win_rate_7d'] as $v) {
            $this->assertIsNumeric($v);
        }
        foreach ($data['performance']['series']['revenue_7d'] as $v) {
            $this->assertIsNumeric($v);
        }
    }
}