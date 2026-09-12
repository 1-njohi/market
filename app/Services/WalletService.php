<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Get or create a wallet for a user.
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
     * Credit a user's available balance.
     */
    public function credit(
        User $user,
        float $amount,
        string $type,
        ?string $reference = null,
        ?string $description = null
    ): Transaction {
        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
            $wallet = $this->getWallet($user);
            $balanceBefore = $wallet->balance;

            $wallet->balance += $amount;

            if ($type === Transaction::TYPE_DEPOSIT) {
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
     * Debit a user's available balance.
     */
    public function debit(
        User $user,
        float $amount,
        string $type,
        ?string $reference = null,
        ?string $description = null
    ): Transaction {
        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
            $wallet = $this->getWallet($user);

            if ($wallet->balance < $amount) {
                throw new \Exception('Insufficient balance');
            }

            $balanceBefore = $wallet->balance;
            $wallet->balance -= $amount;

            if ($type === Transaction::TYPE_WITHDRAWAL) {
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

    /**
     * Credit a user's pending balance (for sellers).
     */
    public function creditPending(
        User $user,
        float $amount,
        ?string $reference = null,
        ?string $description = null
    ): Transaction {
        return DB::transaction(function () use ($user, $amount, $reference, $description) {
            $wallet = $this->getWallet($user);
            $balanceBefore = $wallet->pending_balance;

            $wallet->pending_balance += $amount;
            $wallet->save();

            return $this->createTransaction(
                $user,
                $amount,
                Transaction::TYPE_PENDING_PAYOUT,
                $balanceBefore,
                $wallet->pending_balance,
                $reference,
                $description
            );
        });
    }

    /**
     * Debit a user's pending balance.
     *
     * @param string $type  Transaction type label — 'pending_release' for payouts,
     *                      'fee' for platform fee deductions.
     */
    public function debitPending(
        User $user,
        float $amount,
        string $type = Transaction::TYPE_PENDING_RELEASE,
        ?string $reference = null,
        ?string $description = null
    ): Transaction {
        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
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
                $type,
                $balanceBefore,
                $wallet->pending_balance,
                $reference,
                $description
            );
        });
    }

    /**
     * Move funds from pending balance to available balance.
     * Used when a betslip wins and the seller payout is released.
     */
    public function releasePendingToAvailable(
        User $user,
        float $amount,
        ?string $reference = null,
        ?string $description = null
    ): void {
        DB::transaction(function () use ($user, $amount, $reference, $description) {
            $wallet = $this->getWallet($user);

            if ($wallet->pending_balance < $amount) {
                throw new \Exception('Insufficient pending balance.');
            }

            $pendingBefore = $wallet->pending_balance;
            $balanceBefore = $wallet->balance;

            $wallet->pending_balance -= $amount;
            $wallet->balance += $amount;
            $wallet->save();

            // Ledger: pending debited
            $this->createTransaction(
                $user,
                -$amount,
                Transaction::TYPE_PENDING_RELEASE,
                $pendingBefore,
                $wallet->pending_balance,
                $reference,
                $description
            );

            // Ledger: available credited
            $this->createTransaction(
                $user,
                $amount,
                Transaction::TYPE_PAYOUT,
                $balanceBefore,
                $wallet->balance,
                $reference,
                $description
            );
        });
    }

    /**
     * Charge a platform fee: debit the seller's pending balance
     * and credit the platform's available balance.
     */
    public function chargeFee(
        User $seller,
        User $platform,
        float $feeAmount,
        ?string $reference = null,
        ?string $description = null
    ): void {
        DB::transaction(function () use ($seller, $platform, $feeAmount, $reference, $description) {
            // 1. Deduct fee from seller's pending balance
            $this->debitPending(
                $seller,
                $feeAmount,
                Transaction::TYPE_FEE,
                $reference,
                $description
            );

            // 2. Credit platform's available balance
            $this->credit(
                $platform,
                $feeAmount,
                Transaction::TYPE_FEE,
                $reference,
                $description
            );
        });
    }

    /**
     * Check if user has sufficient available balance.
     */
    public function hasSufficientBalance(User $user, float $amount): bool
    {
        $wallet = $this->getWallet($user);
        return $wallet->balance >= $amount;
    }

    /**
     * Get current available balance.
     */
    public function getBalance(User $user): float
    {
        return (float) $this->getWallet($user)->balance;
    }

    /**
     * Get pending balance (for sellers).
     */
    public function getPendingBalance(User $user): float
    {
        return (float) $this->getWallet($user)->pending_balance;
    }

    /**
     * Fetch all transactions tied to a reference (for auditing/debugging).
     */
    public function findTransactionsByReference(string $reference): Collection
    {
        return Transaction::where('reference', $reference)
            ->orderBy('id')
            ->get();
    }

    /**
     * Create a transaction record.
     */
    private function createTransaction(
        User $user,
        float $amount,
        string $type,
        float $balanceBefore,
        float $balanceAfter,
        ?string $reference = null,
        ?string $description = null,
        string $status = Transaction::STATUS_COMPLETED
    ): Transaction {
        return Transaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => $reference ?? Str::uuid()->toString(),
            'description' => $description,
            'status' => $status,
            'completed_at' => now(),
        ]);
    }
}