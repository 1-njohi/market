<?php

namespace Tests\Feature\Seller;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/seller/dashboard')->assertRedirect('/login');
    }

    public function test_ineligible_user_is_redirected_to_buyer_dashboard(): void
    {
        $user = User::factory()->create(['sellable_status' => 'ineligible']);

        $this->actingAs($user)
            ->get('/seller/dashboard')
            ->assertRedirect('/buyer/dashboard');
    }

    public function test_sellable_user_can_access_seller_dashboard(): void
    {
        $user = User::factory()->create(['sellable_status' => 'sellable']);

        $this->actingAs($user)
            ->get('/seller/dashboard')
            ->assertOk();
    }

    public function test_warned_user_can_access_seller_dashboard(): void
    {
        $user = User::factory()->create(['sellable_status' => 'warned']);

        $this->actingAs($user)
            ->get('/seller/dashboard')
            ->assertOk();
    }

    public function test_delisted_user_can_access_seller_dashboard(): void
    {
        $user = User::factory()->create(['sellable_status' => 'delisted']);

        $this->actingAs($user)
            ->get('/seller/dashboard')
            ->assertOk();
    }

    public function test_ineligible_user_is_blocked_from_every_seller_subpage(): void
    {
        $user = User::factory()->create(['sellable_status' => 'ineligible']);

        $paths = [
            '/seller/betslips',
            '/seller/settlements',
            '/seller/followers',
        ];

        foreach ($paths as $path) {
            $this->actingAs($user)
                ->get($path)
                ->assertRedirect('/buyer/dashboard');
        }
    }
}