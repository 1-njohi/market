<?php

namespace Tests\Feature\Seller;

use App\Models\Betslip;
use App\Models\User;
use App\Services\SellerDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contract for seller_data.onboarding.
 *
 * Mirrors the buyer contract but with seller-specific steps:
 *   - has_created_betslip
 *   - has_first_sale
 *   - has_hosted_contest
 *   - has_withdrawn
 *
 * Plus first_sale_at, useful for "Welcome, first sale!" celebration UI.
 *
 * These tests will FAIL until the service is updated.
 */
class OnboardingStateTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SellerDashboardService
    {
        return app(SellerDashboardService::class);
    }

    /**
     * Create a betslip owned by the given seller, mirroring the shape
     * the real create-betslip flow writes.
     */
    private function makeBetslipFor(User $seller): Betslip
    {
        return Betslip::create([
            'user_id' => $seller->id,
            'code' => strtoupper('T' . substr(uniqid(), -5)),
            'price' => 100,
            'total_odds' => 2.00,
            'status' => 'pending',
            'remaining' => 5,
            'is_winner' => false,
            'caption' => null,
        ]);
    }

    public function test_dashboard_exposes_onboarding_block(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('onboarding', $data);
    }

    public function test_fresh_user_is_first_session(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertTrue($data['onboarding']['is_first_session']);
    }

    public function test_user_with_betslip_is_not_first_session(): void
    {
        $user = User::factory()->create();
        $this->makeBetslipFor($user);

        $data = $this->service()->getDashboardData($user);

        $this->assertFalse($data['onboarding']['is_first_session']);
    }

    public function test_onboarding_exposes_seller_step_flags(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('steps', $data['onboarding']);
        $this->assertArrayHasKey('has_created_betslip', $data['onboarding']['steps']);
        $this->assertArrayHasKey('has_first_sale', $data['onboarding']['steps']);
        $this->assertArrayHasKey('has_hosted_contest', $data['onboarding']['steps']);
        $this->assertArrayHasKey('has_withdrawn', $data['onboarding']['steps']);
    }

    public function test_all_step_flags_are_boolean(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        foreach ($data['onboarding']['steps'] as $key => $value) {
            $this->assertIsBool($value, "steps.{$key} must be a boolean");
        }
    }

    public function test_first_sale_at_key_exists(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('first_sale_at', $data['onboarding']);
    }

    public function test_first_sale_at_is_null_for_fresh_user(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertNull($data['onboarding']['first_sale_at']);
    }
}