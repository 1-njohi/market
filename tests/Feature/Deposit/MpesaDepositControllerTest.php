<?php

namespace Tests\Feature\Deposit;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class MpesaDepositControllerTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake the Daraja OAuth + STK push endpoints.
        Http::fake([
            '*/oauth/*' => Http::response(['access_token' => 'test_token', 'expires_in' => 3599]),
            '*/mpesa/stkpush/*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CheckoutRequestID' => 'ws_CO_test123',
                'MerchantRequestID' => 'MR_test123',
            ]),
        ]);
    }

    public function test_authenticated_user_can_initiate_stk_deposit(): void
    {
        [$user] = $this->makeUserWithWallet();

        $response = $this->actingAs($user)->postJson('/deposit/mpesa/initiate', [
            'amount' => 500,
            'phone' => '254712345678',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'checkout_request_id' => 'ws_CO_test123',
        ]);

        $this->assertDatabaseHas('deposits', [
            'user_id' => $user->id,
            'amount' => 500,
            'status' => 'processing',
            'mpesa_checkout_request_id' => 'ws_CO_test123',
        ]);
    }

    public function test_minimum_amount_is_enforced(): void
    {
        [$user] = $this->makeUserWithWallet();

        $response = $this->actingAs($user)->postJson('/deposit/mpesa/initiate', [
            'amount' => 0.50,
            'phone' => '254712345678',
        ]);

        dump($response->status(), $response->json());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);

        $this->assertDatabaseCount('deposits', 0);
    }
    public function test_invalid_phone_is_rejected(): void
    {
        [$user] = $this->makeUserWithWallet();

        $response = $this->actingAs($user)->postJson('/deposit/mpesa/initiate', [
            'amount' => 500,
            'phone' => 'abc',
        ]);

        dump($response->status(), $response->json(), $response->headers->get('Content-Type'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson('/deposit/mpesa/initiate', [
            'amount' => 500,
            'phone' => '254712345678',
        ]);

        // Laravel's auth middleware redirects web requests to /login;
        // an API-style request returns 401. Either is a rejection.
        $this->assertContains($response->status(), [302, 401]);
    }

    public function test_non_safaricom_number_fails_gracefully(): void
    {
        [$user] = $this->makeUserWithWallet();

        $response = $this->actingAs($user)->postJson('/deposit/mpesa/initiate', [
            'amount' => 500,
            'phone' => '254900000000',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => false]);
        $this->assertDatabaseCount('deposits', 0);
    }
}