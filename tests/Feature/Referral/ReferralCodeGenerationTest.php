<?php

namespace Tests\Feature\Referral;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ReferralCodeGenerationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_new_user_gets_a_referral_code(): void
    {
        $user = User::factory()->create([
            'name' => 'Denis Wanjohi',
            'code' => 'ZwZ-P5WK-Zxc',
        ]);

        $this->assertNotNull($user->referral_code);
        $this->assertNotSame('', $user->referral_code);
    }

    public function test_referral_code_has_expected_format(): void
    {
        $user = User::factory()->create([
            'name' => 'Denis Wanjohi',
            'code' => 'ZwZ-P5WK-Zxc',
        ]);

        // PREFIX-XXXX where PREFIX is 2-5 uppercase alnum, suffix is 4 uppercase alnum.
        $this->assertMatchesRegularExpression(
            '/^[A-Z0-9]{2,5}-[A-Z0-9]{4}$/',
            $user->referral_code
        );
    }

    public function test_referral_code_derives_prefix_from_first_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Denis Wanjohi',
            'code' => 'ZwZ-P5WK-Zxc',
        ]);

        $this->assertStringStartsWith('DENIS-', $user->referral_code);
    }

    public function test_referral_codes_are_unique(): void
    {
        $codes = [];

        for ($i = 0; $i < 20; $i++) {
            $user = User::factory()->create([
                'name' => 'Denis Wanjohi',
                'code' => 'U' . $i,
            ]);

            $this->assertNotContains(
                $user->referral_code,
                $codes,
                'Duplicate referral code generated.'
            );

            $codes[] = $user->referral_code;
        }
    }

    public function test_first_name_is_sanitized(): void
    {
        $user = User::factory()->create([
            'name' => "O'Brien-Smith",
            'code' => 'OB1',
        ]);

        // Non-alnum stripped, then uppercase. Result should still match format.
        $this->assertMatchesRegularExpression(
            '/^[A-Z0-9]{2,5}-[A-Z0-9]{4}$/',
            $user->referral_code
        );
    }

    public function test_short_name_falls_back_to_random_prefix(): void
    {
        $user = User::factory()->create([
            'name' => 'X',
            'code' => 'X1',
        ]);

        // Still has a valid format even though the name is too short for a
        // meaningful prefix.
        $this->assertMatchesRegularExpression(
            '/^[A-Z0-9]{2,5}-[A-Z0-9]{4}$/',
            $user->referral_code
        );
    }
}