<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\User;
use App\Notifications\DepositConfirmedNotification;
use Exception;
use FelixMuhoro\Mpesa\Facades\Mpesa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MpesaDepositService
{
    public function __construct(
        protected WalletService $walletService,
    ) {
    }

    /**
     * Initiate an M-Pesa STK Push deposit.
     *
     * Creates a pending Deposit, sends the STK prompt, and stores the
     * CheckoutRequestID so the async callback can find the row.
     *
     * @return array{success: bool, deposit?: Deposit, checkout_request_id?: string, message?: string}
     */
    public function initiateDeposit(User $user, float $amount, string $phone): array
    {
        $reference = 'DEP-' . strtoupper(Str::random(10));
        $normalizedPhone = $this->normalizePhone($phone);

        if (!$this->isValidSafaricomNumber($normalizedPhone)) {
            return [
                'success' => false,
                'message' => 'Invalid M-Pesa phone number. Use format 2547XXXXXXXX or 2541XXXXXXXX.',
            ];
        }

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => 'KES',
            'status' => Deposit::STATUS_PENDING,
            'payment_method' => 'mpesa_stk',
        ]);

        try {
            $response = Mpesa::stkPush(
                phone: $normalizedPhone,
                amount: (int) $amount,
                reference: $reference,
                description: 'Wallet deposit',
            );

            // Package returns a DTO; the CheckoutRequestID is the key field.
            $checkoutId = $response->checkoutRequestId ?? null;

            if (!$checkoutId) {
                throw new Exception('M-Pesa did not return a CheckoutRequestID.');
            }

            $deposit->update([
                'mpesa_checkout_request_id' => $checkoutId,
                'status' => Deposit::STATUS_PROCESSING,
                'mpesa_response' => method_exists($response, 'toArray')
                    ? $response->toArray()
                    : (array) $response,
            ]);

            Log::info('M-Pesa STK push initiated', [
                'reference' => $reference,
                'checkout_id' => $checkoutId,
                'user_id' => $user->id,
            ]);

            return [
                'success' => true,
                'deposit' => $deposit->fresh(),
                'checkout_request_id' => $checkoutId,
            ];
        } catch (Exception $e) {
            Log::error('M-Pesa STK push failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            $deposit->update([
                'status' => Deposit::STATUS_FAILED,
                'mpesa_response' => ['error' => $e->getMessage()],
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate M-Pesa prompt. Please try again.',
            ];
        }
    }

    /**
     * Handle Safaricom's STK callback.
     *
     * Triggered by the package's PaymentSuccessful / PaymentFailed events.
     * Idempotent — a duplicate callback is a no-op.
     */
    public function handleCallback(array $payload): void
    {
        $checkoutId = $payload['CheckoutRequestID'] ?? null;

        if (!$checkoutId) {
            Log::warning('STK callback missing CheckoutRequestID', $payload);
            return;
        }

        $deposit = Deposit::where('mpesa_checkout_request_id', $checkoutId)->first();

        if (!$deposit) {
            Log::warning('STK callback: deposit not found', ['checkout_id' => $checkoutId]);
            return;
        }

        // Idempotency: only process if still processing or pending.
        if (!in_array($deposit->status, [Deposit::STATUS_PENDING, Deposit::STATUS_PROCESSING], true)) {
            Log::info('STK callback ignored (already settled)', [
                'reference' => $deposit->reference,
                'status' => $deposit->status,
            ]);
            return;
        }

        $resultCode = (int) ($payload['ResultCode'] ?? -1);

        if ($resultCode === 0) {
            $this->settleSuccess($deposit, $payload);
        } else {
            $this->settleFailure($deposit, $payload);
        }
    }

    private function settleSuccess(Deposit $deposit, array $payload): void
    {
        $receipt = $payload['CallbackMetadata']['Item'][1]['Value'] ?? null;

        $this->walletService->credit(
            $deposit->user,
            (float) $deposit->amount,
            'deposit',
            $deposit->reference,
            "M-Pesa deposit ({$deposit->reference})",
        );

        $deposit->update([
            'status' => Deposit::STATUS_COMPLETED,
            'completed_at' => now(),
            'mpesa_receipt' => $receipt,
            'mpesa_response' => $payload,
        ]);

        $deposit->user->notify(new DepositConfirmedNotification($deposit->fresh()));

        Log::info('M-Pesa deposit completed', [
            'reference' => $deposit->reference,
            'receipt' => $receipt,
        ]);
    }

    private function settleFailure(Deposit $deposit, array $payload): void
    {
        $deposit->update([
            'status' => Deposit::STATUS_FAILED,
            'mpesa_response' => $payload,
        ]);

        Log::warning('M-Pesa deposit failed', [
            'reference' => $deposit->reference,
            'result_code' => $payload['ResultCode'] ?? null,
            'result_desc' => $payload['ResultDesc'] ?? null,
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '254' . substr($digits, 1);
        } elseif (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            $digits = '254' . $digits;
        }

        return $digits;
    }

    private function isValidSafaricomNumber(string $phone): bool
    {
        return (bool) preg_match('/^254(7\d{8}|1\d{8})$/', $phone);
    }
}