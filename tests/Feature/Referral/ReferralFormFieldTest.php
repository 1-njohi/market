<?php

namespace Tests\Feature\Referral;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ReferralFormFieldTest extends TestCase
{
    use DatabaseMigrations;

    public function test_register_page_prefills_code_from_session(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $response = $this->withSession(['referral_code' => $referrer->referral_code])
            ->get('/register');

        $response->assertInertia(
            fn($page) => $page
                ->component('auth/Register')
                ->where('referral_code', $referrer->referral_code)
        );
    }

    public function test_register_page_shows_empty_code_without_session(): void
    {
        $response = $this->get('/register');

        $response->assertInertia(
            fn($page) => $page
                ->component('auth/Register')
                ->where('referral_code', '')
        );
    }

    public function test_registering_with_code_in_payload_creates_attribution(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $this->post('/register', $this->payload([
            'referral_code' => $referrer->referral_code,
        ]));

        $referee = User::where('email', 'buyer@test.local')->first();
        $this->assertNotNull($referee);
        $this->assertSame($referrer->id, $referee->referred_by_id);
    }

    public function test_payload_code_overrides_session_code(): void
    {
        $referrerA = $this->makeUser('Denis Wanjohi');
        $referrerB = $this->makeUser('Mary Kilonzo');

        $this->withSession(['referral_code' => $referrerA->referral_code])
            ->post('/register', $this->payload([
                'referral_code' => $referrerB->referral_code,
            ]));

        $referee = User::where('email', 'buyer@test.local')->first();
        $this->assertSame($referrerB->id, $referee->referred_by_id);
    }

    public function test_empty_payload_code_falls_back_to_session(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $this->withSession(['referral_code' => $referrer->referral_code])
            ->post('/register', $this->payload([
                'referral_code' => '',
            ]));

        $referee = User::where('email', 'buyer@test.local')->first();
        $this->assertSame($referrer->id, $referee->referred_by_id);
    }

    public function test_invalid_payload_code_does_not_fail_registration(): void
    {
        $this->post('/register', $this->payload([
            'referral_code' => 'DOES-NOT-EXIST',
        ]));

        $referee = User::where('email', 'buyer@test.local')->first();
        $this->assertNotNull($referee);
        $this->assertNull($referee->referred_by_id);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Buyer',
            'email' => 'buyer@test.local',
            'phone' => '254700000000',
            'country_code' => 'KE',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }
}