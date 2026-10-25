<?php

namespace Tests\Feature\Buyer;

use App\Models\User;
use App\Services\BuyerDashboardService;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase; 

/**
 * Contract for buyer_data.periods, buyer_data.generated_at, and the
 * absence of buyer_data.quick_stats.
 *
 * The frontend's usePeriod composable currently filters list arrays
 * client-side. This test locks in server-side period aggregation so that:
 *   - metric card values become period-scoped (instead of lifetime)
 *   - deltas ("+12% vs last 7d") can be rendered
 *   - lists can be requested filtered by the server when arrays grow large
 *
 * Uses named arguments to force a stable signature:
 *   getDashboardData(User $user, ?string $period = null, string $comparison = 'previous')
 *
 * These tests will FAIL until the service is updated.
 */
class PeriodComparisonTest extends TestCase
{
    use RefreshDatabase; 
    private function service(): BuyerDashboardService
    {
        return app(BuyerDashboardService::class);
    }

    // ──────────────────────────────────────────────────────────
    //  generated_at
    // ──────────────────────────────────────────────────────────

    public function test_dashboard_exposes_generated_at_timestamp(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('generated_at', $data);
        $this->assertIsString($data['generated_at']);
        $this->assertNotNull(Carbon::parse($data['generated_at']));
    }

    // ──────────────────────────────────────────────────────────
    //  periods block
    // ──────────────────────────────────────────────────────────

    public function test_dashboard_exposes_periods_block(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('periods', $data);
    }

    public function test_periods_block_has_all_supported_windows(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        foreach (['24h', '7d', '30d', '90d', 'ytd', 'all'] as $window) {
            $this->assertArrayHasKey(
                $window,
                $data['periods'],
                "periods.{$window} is missing"
            );
        }
    }

    public function test_each_window_exposes_required_metrics(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $required = [
            'win_rate',
            'total_purchases',
            'total_spent',
            'net_spent',
            'refund_rate',
            'avg_price',
        ];

        foreach ($required as $key) {
            $this->assertArrayHasKey(
                $key,
                $data['periods']['7d'],
                "periods.7d.{$key} is missing"
            );
        }
    }

    public function test_each_window_exposes_comparison_deltas(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('comparison', $data['periods']['7d']);

        $deltas = [
            'win_rate_delta',
            'total_spent_delta',
            'total_purchases_delta',
        ];

        foreach ($deltas as $key) {
            $this->assertArrayHasKey(
                $key,
                $data['periods']['7d']['comparison'],
                "periods.7d.comparison.{$key} is missing"
            );
        }
    }

    public function test_delta_values_are_numeric(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        foreach ($data['periods']['7d']['comparison'] as $key => $value) {
            $this->assertIsNumeric($value, "comparison.{$key} must be numeric");
        }
    }

    // ──────────────────────────────────────────────────────────
    //  named-argument signature
    // ──────────────────────────────────────────────────────────

    public function test_service_accepts_period_named_argument(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user, period: '7d');

        // If the signature is right, we still get a full payload back.
        $this->assertArrayHasKey('periods', $data);
    }

    public function test_service_accepts_comparison_named_argument(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData(
            $user,
            comparison: 'yoy',
        );

        $this->assertArrayHasKey('periods', $data);
    }

    public function test_service_accepts_both_named_arguments(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData(
            $user,
            period: '30d',
            comparison: 'yoy',
        );

        $this->assertArrayHasKey('periods', $data);
    }

    // ──────────────────────────────────────────────────────────
    //  quick_stats removal (cleanup)
    // ──────────────────────────────────────────────────────────

    public function test_quick_stats_block_is_no_longer_returned(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayNotHasKey(
            'quick_stats',
            $data,
            'quick_stats should be deleted — nothing in the template reads it'
        );
    }
}