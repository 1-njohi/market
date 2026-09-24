<?php

namespace Tests\Feature\Referral;

use App\Models\Referral;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ReferralAttributionTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.betslip_pirates.referral_defaults' => [
                'reward_percentage' => 0.10,
                'max_transactions' => 20,
                'window_months' => 12,
                'referee_discount_pct' => 0.20,
                'referee_discount_cap' => 20.00,
            ],
        ]);
    }

    public function test_valid_code_creates_referral_and_sets_referred_by(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');
        $referee = $this->makeUser('Test Buyer');

        $referral = app(ReferralService::class)->attribute(
            $referee,
            $referrer->referral_code,
        );

        $this->assertNotNull($referral);
        $this->assertDatabaseHas('referrals', [
            'id' => $referral->id,
            'referrer_id' => $referrer->id,
            'referee_id' => $referee->id,
            'code_used' => $referrer->referral_code,
        ]);

        $this->assertSame($referrer->id, $referee->fresh()->referred_by_id);
    }

    public function test_code_lookup_is_case_insensitive(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');
        $referee = $this->makeUser('Test Buyer');

        $lowercase = strtolower($referrer->referral_code);

        $referral = app(ReferralService::class)->attribute($referee, $lowercase);

        $this->assertNotNull($referral);
        $this->assertSame($referrer->id, $referee->fresh()->referred_by_id);
        // The stored code is the canonical (uppercase) form.
        $this->assertSame($referrer->referral_code, $referral->code_used);
    }

    public function test_self_referral_returns_null_and_creates_no_row(): void
    {
        $user = $this->makeUser('Denis Wanjohi');

        $result = app(ReferralService::class)->attribute($user, $user->referral_code);

        $this->assertNull($result);
        $this->assertSame(0, Referral::count());
        $this->assertNull($user->fresh()->referred_by_id);
    }

    public function test_invalid_code_returns_null_and_creates_no_row(): void
    {
        $referee = $this->makeUser('Test Buyer');

        $result = app(ReferralService::class)->attribute($referee, 'DOES-NOT-EXIST');

        $this->assertNull($result);
        $this->assertSame(0, Referral::count());
        $this->assertNull($referee->fresh()->referred_by_id);
    }

    public function test_attribution_is_idempotent(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');
        $referee = $this->makeUser('Test Buyer');

        $first = app(ReferralService::class)->attribute($referee, $referrer->referral_code);
        $second = app(ReferralService::class)->attribute($referee->fresh(), $referrer->referral_code);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Referral::count());
    }

    public function test_expiry_is_computed_from_referrer_window_months(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');
        $referee = $this->makeUser('Test Buyer');

        \Carbon\Carbon::setTestNow('2026-09-24 12:00:00');

        $referral = app(ReferralService::class)->attribute($referee, $referrer->referral_code);

        // Default window is 12 months.
        $this->assertSame(
            '2027-09-24 12:00:00',
            $referral->expires_at->toDateTimeString(),
        );

        \Carbon\Carbon::setTestNow();
    }

    public function test_already_attributed_user_is_not_reassigned(): void
    {
        $referrerA = $this->makeUser('Denis Wanjohi');
        $referrerB = $this->makeUser('Mary Kilonzo');
        $referee = $this->makeUser('Test Buyer');

        app(ReferralService::class)->attribute($referee, $referrerA->referral_code);
        $second = app(ReferralService::class)->attribute($referee->fresh(), $referrerB->referral_code);

        // Second attempt does not overwrite.
        $this->assertSame(1, Referral::count());
        $this->assertSame($referrerA->id, $referee->fresh()->referred_by_id);
        // The service returns the existing referral for idempotency, not the new one.
        $this->assertSame($referrerA->id, $second->referrer_id);
    }

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }
}