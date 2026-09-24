<?php

namespace Tests\Feature\Referral;

use App\Models\ReferralTerm;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ReferralTermsTest extends TestCase
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

    public function test_user_with_no_override_gets_config_defaults(): void
    {
        $referrer = $this->makeUser();

        $terms = app(ReferralService::class)->termsFor($referrer);

        $this->assertSame(0.10, $terms->reward_percentage);
        $this->assertSame(20, $terms->max_transactions);
        $this->assertSame(12, $terms->window_months);
        $this->assertSame(0.20, $terms->referee_discount_pct);
        $this->assertSame(20.00, $terms->referee_discount_cap);
        $this->assertFalse($terms->is_override);
    }

    public function test_user_with_override_gets_their_custom_terms(): void
    {
        $referrer = $this->makeUser();

        ReferralTerm::create([
            'user_id' => $referrer->id,
            'reward_percentage' => 0.25,
            'max_transactions' => 50,
            'window_months' => 24,
            'referee_discount_pct' => 0.30,
            'referee_discount_cap' => 50.00,
            'granted_at' => now(),
        ]);

        $terms = app(ReferralService::class)->termsFor($referrer);

        $this->assertSame(0.25, $terms->reward_percentage);
        $this->assertSame(50, $terms->max_transactions);
        $this->assertSame(24, $terms->window_months);
        $this->assertSame(0.30, $terms->referee_discount_pct);
        $this->assertSame(50.00, $terms->referee_discount_cap);
        $this->assertTrue($terms->is_override);
    }

    public function test_expired_override_reverts_to_defaults(): void
    {
        $referrer = $this->makeUser();

        ReferralTerm::create([
            'user_id' => $referrer->id,
            'reward_percentage' => 0.25,
            'max_transactions' => 50,
            'window_months' => 24,
            'referee_discount_pct' => 0.30,
            'referee_discount_cap' => 50.00,
            'granted_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $terms = app(ReferralService::class)->termsFor($referrer);

        $this->assertSame(0.10, $terms->reward_percentage);
        $this->assertSame(20, $terms->max_transactions);
        $this->assertFalse($terms->is_override);
    }

    public function test_override_with_null_expiry_never_expires(): void
    {
        $referrer = $this->makeUser();

        ReferralTerm::create([
            'user_id' => $referrer->id,
            'reward_percentage' => 0.25,
            'max_transactions' => 50,
            'window_months' => 24,
            'referee_discount_pct' => 0.30,
            'referee_discount_cap' => 50.00,
            'granted_at' => now()->subYears(5),
            'expires_at' => null,
        ]);

        $terms = app(ReferralService::class)->termsFor($referrer);

        $this->assertSame(0.25, $terms->reward_percentage);
        $this->assertTrue($terms->is_override);
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }
}