<?php

namespace Tests\Feature\Wallet;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase; 

/**
 * Contract for /wallet/balance.
 *
 * The endpoint is already polled by DepositPopover and by our new
 * LiveValue component. Currently it returns only `balance`; the frontend
 * needs `currency` and `updated_at` too so that:
 *   - currency is rendered correctly without hardcoding KES
 *   - AsOf can display a real "Updated Xm ago" hint
 *
 * These tests will FAIL until the controller is updated.
 */
class BalanceLiveTest extends TestCase
{
    use RefreshDatabase; 
    public function test_endpoint_returns_full_shape(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/wallet/balance');

        $response->assertOk();
        $response->assertJsonStructure([
            'balance',
            'currency',
            'updated_at',
        ]);
    }

    public function test_balance_is_numeric(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/wallet/balance');

        $this->assertIsNumeric($response->json('balance'));
    }

    public function test_currency_is_a_string(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/wallet/balance');

        $this->assertIsString($response->json('currency'));
        $this->assertNotEmpty($response->json('currency'));
    }

    public function test_updated_at_is_a_valid_iso_timestamp(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/wallet/balance');

        $updatedAt = $response->json('updated_at');
        $this->assertIsString($updatedAt);
        $this->assertNotNull(
            \Carbon\Carbon::parse($updatedAt),
            'updated_at must be parseable as a date'
        );
    }

    public function test_endpoint_rejects_guests(): void
    {
        $this->getJson('/wallet/balance')->assertUnauthorized();
    }

    public function test_zero_balance_still_returns_full_shape(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/wallet/balance');

        $response->assertOk();
        $this->assertEquals(0, (float) $response->json('balance'));
        $this->assertIsString($response->json('currency'));
    }
}