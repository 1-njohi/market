<?php

namespace Tests\Feature\Buyer;

use App\Models\User;
use App\Services\BuyerDashboardService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase; 

/**
 * Contract for buyer_data.performance.series.
 *
 * The Sparkline component on the Performance and Money cards currently
 * uses frontend-derived fallbacks. This test locks in server-side series
 * so the sparkline reflects real data.
 *
 * These tests will FAIL until the service is updated.
 */
class PerformanceSeriesTest extends TestCase
{
    use RefreshDatabase; 
    private function service(): BuyerDashboardService
    {
        return app(BuyerDashboardService::class);
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

    public function test_spent_series_is_array(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('spent_7d', $data['performance']['series']);
        $this->assertIsArray($data['performance']['series']['spent_7d']);
    }

    public function test_win_rate_series_has_at_most_seven_points(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertLessThanOrEqual(
            7,
            count($data['performance']['series']['win_rate_7d'])
        );
    }

    public function test_spent_series_has_at_most_seven_points(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertLessThanOrEqual(
            7,
            count($data['performance']['series']['spent_7d'])
        );
    }

    public function test_series_values_are_numeric(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        foreach ($data['performance']['series']['win_rate_7d'] as $v) {
            $this->assertIsNumeric($v);
        }
        foreach ($data['performance']['series']['spent_7d'] as $v) {
            $this->assertIsNumeric($v);
        }
    }
}