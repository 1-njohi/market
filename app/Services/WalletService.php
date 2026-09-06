<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Get or create a wallet for a user
     */
    public function getWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'pending_balance' => 0,
                'total_deposited' => 0,
                'total_withdrawn' => 0,
                'currency' => 'KES',
            ]
        );
    }
    /**
     * Credit a user's wallet
     */
    public function credit(User $user, float $amount, string $type, $reference = null, $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
            $wallet = $this->getWallet($user);
            $balanceBefore = $wallet->balance;

            $wallet->balance += $amount;

            // Update total_deposited if this is a deposit
            if ($type === 'deposit') {
                $wallet->total_deposited += $amount;
            }

            $wallet->save();

            return $this->createTransaction(
                $user,
                $amount,
                $type,
                $balanceBefore,
                $wallet->balance,
                $reference,
                $description
            );
        });
    }

    /**
     * Debit a user's wallet
     */
    public function debit(User $user, float $amount, string $type, $reference = null, $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
            $wallet = $this->getWallet($user);

            if ($wallet->balance < $amount) {
                throw new \Exception('Insufficient balance');
            }

            $balanceBefore = $wallet->balance;
            $wallet->balance -= $amount;

            // Update total_withdrawn if this is a withdrawal
            if ($type === 'withdrawal') {
                $wallet->total_withdrawn += $amount;
            }

            $wallet->save();

            return $this->createTransaction(
                $user,
                -$amount,
                $type,
                $balanceBefore,
                $wallet->balance,
                $reference,
                $description
            );
        });
    }

    // In WalletService

    /**
     * Credit a user's pending balance (for sellers)
     */
    public function creditPending(User $user, float $amount, $reference = null, $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $reference, $description) {
            $wallet = $this->getWallet($user);
            $balanceBefore = $wallet->pending_balance;

            $wallet->pending_balance += $amount;
            $wallet->save();

            return $this->createTransaction(
                $user,
                $amount,
                'pending_payout',
                $balanceBefore,
                $wallet->pending_balance,
                $reference,
                $description
            );
        });
    }

    /**
     * Debit a user's pending balance (reverse payout)
     */
    public function debitPending(User $user, float $amount, $reference = null, $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $reference, $description) {
            $wallet = $this->getWallet($user);

            if ($wallet->pending_balance < $amount) {
                throw new \Exception('Insufficient pending balance.');
            }

            $balanceBefore = $wallet->pending_balance;
            $wallet->pending_balance -= $amount;
            $wallet->save();

            return $this->createTransaction(
                $user,
                -$amount,
                'pending_release',
                $balanceBefore,
                $wallet->pending_balance,
                $reference,
                $description
            );
        });
    }

    /**
     * Release pending balance to available balance (when betslip wins)
     */
    public function releasePendingToAvailable(User $user, float $amount, $reference = null, $description = null): void
    {
        DB::transaction(function () use ($user, $amount, $reference, $description) {
            $wallet = $this->getWallet($user);

            if ($wallet->pending_balance < $amount) {
                throw new \Exception('Insufficient pending balance.');
            }

            // Debit pending
            $wallet->pending_balance -= $amount;

            // Credit available
            $wallet->balance += $amount;

            $wallet->save();

            // Record pending release
            $this->createTransaction(
                $user,
                -$amount,
                'pending_release',
                $wallet->pending_balance + $amount,
                $wallet->pending_balance,
                $reference,
                $description
            );

            // Record payout
            $this->createTransaction(
                $user,
                $amount,
                'payout',
                $wallet->balance - $amount,
                $wallet->balance,
                $reference,
                $description
            );
        });
    }
    /**
     * Release seller's pending balance to available balance (when betslip settles)
     */
    public function releasePayout(User $seller, float $amount, $reference = null, $description = null): Transaction
    {
        return DB::transaction(function () use ($seller, $amount, $reference, $description) {
            $wallet = $this->getWallet($seller);

            if ($wallet->pending_balance < $amount) {
                throw new \Exception('Insufficient pending balance');
            }

            $wallet->pending_balance -= $amount;
            $wallet->balance += $amount;
            $wallet->save();

            // Create two transactions: one for pending release, one for available credit
            $this->createTransaction(
                $seller,
                -$amount,
                'pending_release',
                $wallet->pending_balance + $amount,
                $wallet->pending_balance,
                $reference,
                $description
            );

            return $this->createTransaction(
                $seller,
                $amount,
                'payout',
                $wallet->balance - $amount,
                $wallet->balance,
                $reference,
                $description
            );
        });
    }

    /**
     * Check if user has sufficient balance
     */
    public function hasSufficientBalance(User $user, float $amount): bool
    {
        $wallet = $this->getWallet($user);
        return $wallet->balance >= $amount;
    }

    /**
     * Get current balance
     */
    public function getBalance(User $user): float
    {
        return $this->getWallet($user)->balance;
    }

    /**
     * Get pending balance (for sellers)
     */
    public function getPendingBalance(User $user): float
    {
        return $this->getWallet($user)->pending_balance;
    }

    /**
     * Create a transaction record
     */
    private function createTransaction(User $user, float $amount, string $type, float $balanceBefore, float $balanceAfter, $reference = null, $description = null, string $status = 'completed'): Transaction
    {
        return Transaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => $reference ?? Str::uuid(),
            'description' => $description,
            'status' => $status,
            'completed_at' => now(),
        ]);
    }
}