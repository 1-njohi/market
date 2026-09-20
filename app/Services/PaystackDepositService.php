<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackDepositService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Initiate a deposit via Paystack
     */
    public function initiateDeposit(User $user, float $amount, string $email): array
    {
        $reference = 'DEP-' . strtoupper(Str::random(10));

        // Create deposit record
        $deposit = Deposit::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => 'KES',
            'status' => 'pending',
        ]);

        try {
            // Use the paystack() helper
            $paystack = paystack();

            // Initialize transaction using the transaction() resource
            $response = $paystack->transaction()->initialize([
                'email' => $email,
                'amount' => $amount * 100, // Paystack uses kobo
                'reference' => $reference,
                'callback_url' => config('paystack.callback_url'),
                'metadata' => [
                    'user_id' => $user->id,
                    'deposit_id' => $deposit->id,
                ],
            ]);

            if ($response['status']) {
                return [
                    'success' => true,
                    'deposit' => $deposit,
                    'authorization_url' => $response['data']['authorization_url'],
                    'reference' => $response['data']['reference'],
                ];
            }

            Log::error('Paystack deposit initiation failed', [
                'user_id' => $user->id,
                'reference' => $reference,
                'response' => $response,
            ]);

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Failed to initiate deposit',
            ];

        } catch (Exception $e) {
            Log::error('Paystack deposit exception', [
                'user_id' => $user->id,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.',
            ];
        }
    }

    /**
     * Handle Paystack webhook
     */
    public function handleWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;

        if ($event === 'charge.success') {
            $this->handleSuccessfulCharge($payload['data']);
        }
    }

    /**
     * Handle successful charge webhook
     */
    protected function handleSuccessfulCharge(array $data): void
    {
        $reference = $data['reference'];
        $amount = $data['amount'] / 100; // Convert from kobo
        $metadata = $data['metadata'] ?? [];
        $channel = $data['channel'];

        $deposit = Deposit::where('reference', $reference)->first();

        if (!$deposit || $deposit->status !== 'pending') {
            Log::warning('Deposit not found or already processed', ['reference' => $reference]);
            return;
        }

        // Credit user's wallet
        $user = $deposit->user;
        $this->walletService->credit(
            $user,
            $amount,
            'deposit',
            $reference,
            "Deposit via Paystack ({$reference})"
        );

        $deposit->update([
            'status' => 'completed',
            'completed_at' => now(),
            'payment_method' => $channel,
            'paystack_response' => $metadata
        ]);

        $user->notify(new \App\Notifications\DepositConfirmedNotification($deposit->fresh()));
    }

    /**
     * Verify a transaction manually (fallback)
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $paystack = paystack();
            $response = $paystack->transaction()->verify($reference);

            if ($response['status'] && $response['data']['status'] === 'success') {
                $this->handleSuccessfulCharge($response['data']);
                return ['success' => true, 'message' => 'Transaction verified'];
            }

            return ['success' => false, 'message' => 'Transaction verification failed'];

        } catch (Exception $e) {
            Log::error('Paystack verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Verification error'];
        }
    }
}