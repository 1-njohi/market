<?php

namespace App\Services;

use App\Models\User;
use App\Models\Withdrawal;
use FelixMuhoro\Mpesa\Facades\Mpesa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MpesaWithdrawalService
{
    public function __construct(
        protected WalletService $walletService,
    ) {}

    /**
     * Initiate a B2C withdrawal.
     *
     * @throws \Exception
     */
    public function initiate(User $user, float $amount, string $phone): Withdrawal
    {
        $phone = $this->normalizePhone($phone);

        if (!$this->isValidSafaricomNumber($phone)) {
            throw new \Exception('Invalid M-Pesa phone number. Use format 2547XXXXXXXX or 2541XXXXXXXX.');
        }

        if ($amount < 10) {
            throw new \Exception('Minimum withdrawal amount is KES 10.');
        }

        if (!$this->walletService->hasSufficientBalance($user, $amount)) {
            throw new \Exception('Insufficient wallet balance.');
        }

        $reference = 'WTH-' . strtoupper(Str::random(10));

        return DB::transaction(function () use ($user, $amount, $phone, $reference) {
            // 1. Debit user's wallet immediately (locks funds)
            $this->walletService->debit(
                $user,
                $amount,
                'withdrawal',
                $reference,
                "M-Pesa withdrawal to {$phone}"
            );

            // 2. Create withdrawal record
            $withdrawal = Withdrawal::create([
                'user_id'         => $user->id,
                'reference'       => $reference,
                'amount'          => $amount,
                'currency'        => 'KES',
                'payment_method'  => 'mpesa_b2c',
                'destination'     => ['phone' => $phone],
                'status'          => Withdrawal::STATUS_PENDING,
            ]);

            // 3. Fire the B2C request
            try {
                $response = Mpesa::b2cSend(
                    amount: (int) $amount,
                    phone: $phone,
                    remarks: 'Betslip Pirates Withdrawal',
                    commandId: $reference,
                );

                $withdrawal->update([
                    'status' => Withdrawal::STATUS_PROCESSING,
                    'mpesa_conversation_id' => $response['ConversationID'] ?? null,
                    'mpesa_originator_conversation_id' => $response['OriginatorConversationID'] ?? null,
                    'mpesa_response' => $response,
                ]);

                Log::info("B2C withdrawal initiated", [
                    'reference' => $reference,
                    'user_id'   => $user->id,
                    'amount'    => $amount,
                ]);
            } catch (Throwable $e) {
                // Roll back the wallet debit and mark failed
                $this->walletService->credit(
                    $user,
                    $amount,
                    'refund',
                    $reference . '-reversal',
                    "Reversal: B2C request failed"
                );

                $withdrawal->update([
                    'status'         => Withdrawal::STATUS_FAILED,
                    'failure_reason' => $e->getMessage(),
                ]);

                Log::error("B2C withdrawal failed to send", [
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);

                throw $e;
            }

            return $withdrawal;
        });
    }

    /**
     * Handle the async result callback from Safaricom.
     */
    public function handleResult(array $payload): void
    {
        $result = $payload['Result'] ?? [];

        $conversationId  = $result['ConversationID'] ?? null;
        $originatorId    = $result['OriginatorConversationID'] ?? null;
        $resultCode      = (int) ($result['ResultCode'] ?? -1);
        $resultDesc      = $result['ResultDesc'] ?? 'Unknown';
        $transactionId   = $result['TransactionID'] ?? null;

        if (!$originatorId && !$conversationId) {
            Log::warning("B2C callback missing conversation identifiers", $payload);
            return;
        }

        $withdrawal = Withdrawal::where('mpesa_originator_conversation_id', $originatorId)
            ->orWhere('mpesa_conversation_id', $conversationId)
            ->first();

        if (!$withdrawal) {
            Log::warning("B2C callback: withdrawal not found", [
                'originator_id' => $originatorId,
                'conversation_id' => $conversationId,
            ]);
            return;
        }

        // Idempotency: only process if still pending
        if (!$withdrawal->isPending()) {
            Log::info("B2C callback ignored (already settled)", [
                'reference' => $withdrawal->reference,
                'status'    => $withdrawal->status,
            ]);
            return;
        }

        DB::transaction(function () use ($withdrawal, $resultCode, $resultDesc, $transactionId, $result) {
            if ($resultCode === 0) {
                $withdrawal->update([
                    'status'         => Withdrawal::STATUS_COMPLETED,
                    'mpesa_receipt'  => $transactionId,
                    'mpesa_response' => $result,
                    'completed_at'   => now(),
                ]);

                Log::info("B2C withdrawal completed", [
                    'reference' => $withdrawal->reference,
                    'receipt'   => $transactionId,
                ]);
            } else {
                // Refund the user's wallet
                $this->walletService->credit(
                    $withdrawal->user,
                    (float) $withdrawal->amount,
                    'refund',
                    $withdrawal->reference . '-refund',
                    "Refund for failed withdrawal ({$resultDesc})"
                );

                $withdrawal->update([
                    'status'         => Withdrawal::STATUS_FAILED,
                    'failure_reason' => $resultDesc,
                    'mpesa_response' => $result,
                    'completed_at'   => now(),
                ]);

                Log::warning("B2C withdrawal failed and refunded", [
                    'reference' => $withdrawal->reference,
                    'reason'    => $resultDesc,
                ]);
            }
        });
    }

    /**
     * Handle Safaricom's timeout callback.
     */
    public function handleTimeout(array $payload): void
    {
        Log::warning("B2C timeout callback received", $payload);
        // Safaricom may still deliver the Result URL later.
        // Leave the withdrawal in `processing` so the result callback can settle it.
    }

    /**
     * Normalize a Kenyan phone number to 2547XXXXXXXX / 2541XXXXXXXX.
     */
    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '254' . substr($digits, 1);
        } elseif (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            $digits = '254' . $digits;
        }

        return $digits;
    }

    protected function isValidSafaricomNumber(string $phone): bool
    {
        return (bool) preg_match('/^254(7\d{8}|1\d{8})$/', $phone);
    }
}