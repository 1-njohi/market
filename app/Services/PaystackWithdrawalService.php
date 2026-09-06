<?php

namespace App\Services;

use App\Models\Withdrawal;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackWithdrawalService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Initiate a withdrawal via Paystack
     */
    public function initiateWithdrawal(User $user, float $amount, array $bankDetails): array
    {
        // Check balance
        if (!$this->walletService->hasSufficientBalance($user, $amount)) {
            return ['success' => false, 'message' => 'Insufficient balance'];
        }

        $reference = 'WTH-' . strtoupper(Str::random(10));

        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => 'KES',
            'payment_method' => $bankDetails['bank'] ?? 'bank_transfer',
            'destination' => $bankDetails,
            'status' => 'pending',
        ]);

        try {
            $paystack = paystack();

            // Create a transfer recipient first using transferRecipient() resource
            $recipient = $this->createTransferRecipient($user, $bankDetails);

            if (!$recipient['success']) {
                return ['success' => false, 'message' => $recipient['message']];
            }

            // Initiate the transfer using transfer() resource
            $response = $paystack->transfer()->initiate([
                'source' => 'balance',
                'amount' => $amount * 100,
                'recipient' => $recipient['recipient_code'],
                'reason' => 'Withdrawal from Betslip Pirates',
                'reference' => $reference,
            ]);

            if ($response['status']) {
                // Debit user's wallet
                $this->walletService->debit(
                    $user,
                    $amount,
                    'withdrawal',
                    $reference,
                    "Withdrawal to {$bankDetails['bank_name']} ({$reference})"
                );

                $withdrawal->update([
                    'status' => 'processing',
                    'metadata' => $response['data'],
                ]);

                return [
                    'success' => true,
                    'message' => 'Withdrawal initiated successfully',
                    'withdrawal' => $withdrawal,
                ];
            }

            Log::error('Paystack withdrawal failed', [
                'user_id' => $user->id,
                'reference' => $reference,
                'response' => $response,
            ]);

            return ['success' => false, 'message' => $response['message'] ?? 'Withdrawal failed'];

        } catch (Exception $e) {
            Log::error('Paystack withdrawal exception', [
                'user_id' => $user->id,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Withdrawal error'];
        }
    }

    /**
     * Create a transfer recipient on Paystack
     */
    protected function createTransferRecipient(User $user, array $bankDetails): array
    {
        try {
            $paystack = paystack();

            // Use transferRecipient() resource to create a recipient
            $response = $paystack->transferRecipient()->create([
                'type' => 'nuban',
                'name' => $user->name,
                'account_number' => $bankDetails['account_number'],
                'bank_code' => $bankDetails['bank_code'],
                'currency' => 'KES',
                'description' => "Recipient for {$user->name}",
            ]);

            if ($response['status']) {
                return [
                    'success' => true,
                    'recipient_code' => $response['data']['recipient_code'],
                ];
            }

            Log::error('Paystack recipient creation failed', [
                'user_id' => $user->id,
                'response' => $response,
            ]);

            return ['success' => false, 'message' => $response['message'] ?? 'Failed to create recipient'];

        } catch (Exception $e) {
            Log::error('Paystack recipient creation exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Recipient creation error'];
        }
    }

    /**
     * Handle withdrawal webhook
     */
    public function handleWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;

        if ($event === 'transfer.success') {
            $this->handleSuccessfulTransfer($payload['data']);
        } elseif ($event === 'transfer.failed') {
            $this->handleFailedTransfer($payload['data']);
        }
    }

    /**
     * Handle successful transfer webhook
     */
    protected function handleSuccessfulTransfer(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            return;
        }

        $withdrawal = Withdrawal::where('reference', $reference)->first();

        if (!$withdrawal || $withdrawal->status === 'completed') {
            return;
        }

        $withdrawal->update([
            'status' => 'completed',
            'completed_at' => now(),
            'metadata' => array_merge($withdrawal->metadata ?? [], [
                'paystack_data' => $data,
            ]),
        ]);
    }

    /**
     * Handle failed transfer webhook
     */
    protected function handleFailedTransfer(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            return;
        }

        $withdrawal = Withdrawal::where('reference', $reference)->first();

        if (!$withdrawal || $withdrawal->status === 'failed') {
            return;
        }

        // Refund the user
        $this->walletService->credit(
            $withdrawal->user,
            $withdrawal->amount,
            'refund',
            $reference,
            "Refund for failed withdrawal ({$reference})"
        );

        $withdrawal->update([
            'status' => 'failed',
            'metadata' => array_merge($withdrawal->metadata ?? [], [
                'paystack_data' => $data,
                'refunded_at' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Check transfer status (manual fallback)
     */
    public function checkTransferStatus(string $reference): array
    {
        try {
            $paystack = paystack();
            $response = $paystack->transfer()->verify($reference);

            if ($response['status']) {
                if ($response['data']['status'] === 'success') {
                    $this->handleSuccessfulTransfer($response['data']);
                } elseif ($response['data']['status'] === 'failed') {
                    $this->handleFailedTransfer($response['data']);
                }

                return ['success' => true, 'data' => $response['data']];
            }

            return ['success' => false, 'message' => $response['message'] ?? 'Failed to check status'];

        } catch (Exception $e) {
            Log::error('Paystack transfer status check failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Status check error'];
        }
    }
}