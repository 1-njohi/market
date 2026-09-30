<?php

namespace Tests\Feature\Deposit;

use App\Models\Deposit;
use App\Services\MpesaDepositService;
use FelixMuhoro\Mpesa\DTOs\CallbackPayload;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class MpesaStkCallbackTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;

    public function test_success_callback_credits_wallet_and_marks_completed(): void
    {
        [$user, $wallet] = $this->makeUserWithWallet(balance: 0);

        $deposit = $this->makeProcessingDeposit($user->id, amount: 500.00, checkoutId: 'ws_CO_123');

        $payload = CallbackPayload::fromArray([
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => 'MR_123',
                    'CheckoutRequestID' => 'ws_CO_123',
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 500],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'RECEIPT123'],
                        ],
                    ],
                ],
            ],
        ]);

        app(MpesaDepositService::class)->handleCallback($payload);

        $deposit->refresh();

        $this->assertSame(Deposit::STATUS_COMPLETED, $deposit->status);
        $this->assertSame('RECEIPT123', $deposit->mpesa_receipt);
        $this->assertNotNull($deposit->completed_at);
        $this->assertSame(500.00, (float) $wallet->fresh()->balance);
    }

    public function test_failure_callback_marks_failed_without_credit(): void
    {
        [$user, $wallet] = $this->makeUserWithWallet(balance: 0);

        $deposit = $this->makeProcessingDeposit($user->id, amount: 500.00, checkoutId: 'ws_CO_456');

        $payload = CallbackPayload::fromArray([
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => 'MR_456',
                    'CheckoutRequestID' => 'ws_CO_456',
                    'ResultCode' => 1032,
                    'ResultDesc' => 'Request cancelled by user',
                ],
            ],
        ]);

        app(MpesaDepositService::class)->handleCallback($payload);

        $deposit->refresh();

        $this->assertSame(Deposit::STATUS_FAILED, $deposit->status);
        $this->assertSame(0.00, (float) $wallet->fresh()->balance);
    }

    public function test_duplicate_success_callback_does_not_double_credit(): void
    {
        [$user, $wallet] = $this->makeUserWithWallet(balance: 0);

        $deposit = $this->makeProcessingDeposit($user->id, amount: 500.00, checkoutId: 'ws_CO_789');

        $payload = CallbackPayload::fromArray([
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => 'MR_789',
                    'CheckoutRequestID' => 'ws_CO_789',
                    'ResultCode' => 0,
                    'ResultDesc' => 'OK',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 500],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'RECEIPT789'],
                        ],
                    ],
                ],
            ],
        ]);

        $service = app(MpesaDepositService::class);
        $service->handleCallback($payload);
        $service->handleCallback($payload);

        $deposit->refresh();

        $this->assertSame(Deposit::STATUS_COMPLETED, $deposit->status);
        $this->assertSame(500.00, (float) $wallet->fresh()->balance);
    }

    public function test_callback_for_unknown_checkout_id_is_ignored(): void
    {
        [$user, $wallet] = $this->makeUserWithWallet(balance: 0);

        $payload = CallbackPayload::fromArray([
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => 'MR_unknown',
                    'CheckoutRequestID' => 'ws_CO_does_not_exist',
                    'ResultCode' => 0,
                    'ResultDesc' => 'OK',
                ],
            ],
        ]);

        app(MpesaDepositService::class)->handleCallback($payload);

        $this->assertSame(0.00, (float) $wallet->fresh()->balance);
    }

    private function makeProcessingDeposit(int $userId, float $amount, string $checkoutId): Deposit
    {
        return Deposit::create([
            'user_id' => $userId,
            'reference' => 'DEP-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'amount' => $amount,
            'currency' => 'KES',
            'status' => Deposit::STATUS_PROCESSING,
            'payment_method' => 'mpesa_stk',
            'mpesa_checkout_request_id' => $checkoutId,
        ]);
    }
}