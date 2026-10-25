<?php

namespace Tests\Feature\Buyer;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\User;
use App\Services\BuyerDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contract for buyer_data.onboarding.
 *
 * The frontend's useOnboarding composable currently infers "fresh" state
 * client-side by counting purchases/watches/follows/contests. This test
 * locks in a backend-provided signal so the inference can be trusted:
 *
 *   - is_first_session: authoritative flag
 *   - steps.{has_watched,has_purchased,has_followed,has_joined_contest}
 *   - first_deposit_amount: money the user first put in
 *
 * These tests will FAIL until the service is updated.
 */
class OnboardingStateTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BuyerDashboardService
    {
        return app(BuyerDashboardService::class);
    }

    /**
     * Create a pending purchase for the given buyer, with a freshly-created
     * seller and betslip behind it. Mirrors the shape the real checkout
     * writes — including the purchased_at timestamp that the pivot table
     * requires as NOT NULL.
     */
    private function makePurchaseFor(User $buyer): BetslipUserPurchase
    {
        $seller = User::factory()->create();

        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'code' => strtoupper('T' . substr(uniqid(), -5)),
            'price' => 100,
            'total_odds' => 2.00,
            'status' => 'pending',
            'remaining' => 5,
            'is_winner' => false,
            'caption' => null,
        ]);

        return BetslipUserPurchase::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'betslip_id' => $betslip->id,
            'purchase_price' => 100,
            'total_odds' => 2.00,
            'status' => 'pending',
            'purchased_at' => now(),
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

    public function test_user_with_purchase_is_not_first_session(): void
    {
        $user = User::factory()->create();
        $this->makePurchaseFor($user);

        $data = $this->service()->getDashboardData($user);

        $this->assertFalse($data['onboarding']['is_first_session']);
    }

    public function test_onboarding_exposes_step_completion_flags(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('steps', $data['onboarding']);
        $this->assertArrayHasKey('has_watched', $data['onboarding']['steps']);
        $this->assertArrayHasKey('has_purchased', $data['onboarding']['steps']);
        $this->assertArrayHasKey('has_followed', $data['onboarding']['steps']);
        $this->assertArrayHasKey('has_joined_contest', $data['onboarding']['steps']);
    }

    public function test_all_step_flags_are_boolean(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        foreach ($data['onboarding']['steps'] as $key => $value) {
            $this->assertIsBool($value, "steps.{$key} must be a boolean");
        }
    }

    public function test_first_deposit_amount_key_exists(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('first_deposit_amount', $data['onboarding']);
    }

    public function test_first_deposit_amount_is_null_for_fresh_user(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertNull($data['onboarding']['first_deposit_amount']);
    }
}