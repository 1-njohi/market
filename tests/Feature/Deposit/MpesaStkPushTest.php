<?php

namespace Tests\Feature\Deposit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Deposit;
use Tests\Concerns\CreatesWalletUsers;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Services\MpesaDepositService;
class MpesaStkPushTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;
    /**
     * A basic feature test example.
     */
    public function test_initiating_deposit_creates_pending_record_and_returns_checkout_id(): void
    {
        [$user] = $this->makeUserWithWallet();

        // Http::fake() for Daraja OAuth + STK Push endpoints
        Http::fake([
            '*/oauth/*' => Http::response(['access_token' => 'test_token']),
            '*/mpesa/stkpush/*' => Http::response([
                'ResponseCode' => '0',
                'CheckoutRequestID' => 'ws_CO_test123',
            ]),
        ]);

        $result = app(MpesaDepositService::class)->initiateDeposit(
            $user,
            amount: 500.00,
            phone: '254712345678',
        );

        $this->assertTrue($result['success']);
        $this->assertSame('ws_CO_test123', $result['checkout_request_id']);

        $deposit = Deposit::where('user_id', $user->id)->first();
        $this->assertNotNull($deposit);
        $this->assertSame('processing', $deposit->status);
        $this->assertSame('ws_CO_test123', $deposit->mpesa_checkout_request_id);
    }
}
