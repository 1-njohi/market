<?php

namespace Tests\Feature\Referral;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ReferralSignupTest extends TestCase
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

    public function test_referral_link_stores_code_and_redirects_to_register(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $response = $this->get('/r/' . $referrer->referral_code);

        $response->assertRedirect('/register');
        $response->assertSessionHas('referral_code', $referrer->referral_code);
    }

    public function test_referral_link_normalizes_case(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $response = $this->get('/r/' . strtolower($referrer->referral_code));

        $response->assertRedirect('/register');
        $response->assertSessionHas('referral_code', $referrer->referral_code);
    }

    public function test_referral_link_with_invalid_code_still_redirects_to_register(): void
    {
        // Don't blow up on a bad link — just go to signup with no code.
        $response = $this->get('/r/DOES-NOT-EXIST');

        $response->assertRedirect('/register');
        $response->assertSessionMissing('referral_code');
    }

    public function test_registering_with_session_code_creates_attribution(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $response = $this
            ->withSession(['referral_code' => $referrer->referral_code])
            ->post('/register', [
                'name' => 'Test Buyer',
                'email' => 'buyer@test.local',
                'phone' => '254700000000',
                'country_code' => 'KE',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $referee = User::where('email', 'buyer@test.local')->first();
        $this->assertNotNull($referee);
        // ...
    }
    public function test_registering_without_session_code_creates_no_attribution(): void
    {
        $this->post('/register', [
            'name' => 'Test Buyer',
            'email' => 'buyer@test.local',
            'phone' => '254700000000',
            'country_code' => 'KE',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $referee = User::where('email', 'buyer@test.local')->first();
        $this->assertNotNull($referee);
        $this->assertNull($referee->referred_by_id);
        $this->assertSame(0, Referral::count());
    }

    public function test_registering_with_invalid_session_code_does_not_fail(): void
    {
        $this->withSession(['referral_code' => 'DOES-NOT-EXIST'])
            ->post('/register', [
                'name' => 'Test Buyer',
                'email' => 'buyer@test.local',
                'phone' => '254700000000',
                'country_code' => 'KE',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $referee = User::where('email', 'buyer@test.local')->first();
        $this->assertNotNull($referee);
        $this->assertNull($referee->referred_by_id);
        $this->assertSame(0, Referral::count());
    }

    public function test_session_code_is_cleared_after_successful_registration(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $this
            ->withSession(['referral_code' => $referrer->referral_code])
            ->post('/register', [
                'name' => 'Test Buyer',
                'email' => 'buyer@test.local',
                'phone' => '254700000000',
                'country_code' => 'KE',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        // Session code should not persist — a second registration in the
        // same session shouldn't be attributed to the first referrer.
        $this->assertNull(session('referral_code'));
    }

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }
}