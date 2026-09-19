<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Ledger-backed wallet operations.
 *
 * Conventions
 * -----------
 *  - All public mutators open their own DB transaction and take a row lock
 *    (`SELECT ... FOR UPDATE`) on every wallet they touch, in ascending
 *    user_id order, before reading a balance.
 *  - Money is handled as float. Wallet/Transaction columns are decimal(15,2)
 *    and cast to `decimal:2` on the models, so Eloquent returns strings —
 *    every read below casts with (float).
 *  - `reference` identifies one *logical operation*. Where an operation
 *    writes two ledger rows (releasePendingToAvailable, chargeFee), each row
 *    gets its own reference, because `transactions.reference` is UNIQUE.
 *
 * Requires: wallets.user_id UNIQUE; transactions.balance_type.
 */
class WalletService
{
    public const BALANCE_AVAILABLE = 'available';
    public const BALANCE_PENDING   = 'pending';

    // ---------------------------------------------------------------------
    // Reads
    // ---------------------------------------------------------------------

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
     * Advisory balance check only. Reads outside any lock; NOT an
     * authorization gate. The authoritative check lives in debit() and
     * debitPending(), which re-check under a row lock.
     */
    public function hasSufficientBalance(User $user, float|int|string $amount): bool
    {
        $amount = $this->normalizeAmount($amount);

        return (float) $this->getWallet($user)->balance >= $amount;
    }

    public function getBalance(User $user): float
    {
        return (float) $this->getWallet($user)->balance;
    }

    public function getPendingBalance(User $user): float
    {
        return (float) $this->getWallet($user)->pending_balance;
    }

    // ---------------------------------------------------------------------
    // Available balance
    // ---------------------------------------------------------------------

    public function credit(
        User $user,
        float|int|string $amount,
        string $type,
        ?string $reference = null,
        ?string $description = null
    ): Transaction {
        $amount    = $this->normalizeAmount($amount);
        $reference ??= (string) Str::uuid();

        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
            $wallet = $this->lockWallet((int) $user->id);

            $before = (float) $wallet->balance;
            $after  = $before + $amount;

            $wallet->balance = $after;

            if ($type === Transaction::TYPE_DEPOSIT) {
                $wallet->total_deposited = (float) $wallet->total_deposited + $amount;
            }

            $wallet->save();

            return $this->createTransaction(
                $user,
                $amount,
                $type,
                self::BALANCE_AVAILABLE,
                $before,
                $after,
                $reference,
                $description
            );
        });
    }

    /**
     * @throws InsufficientBalanceException
     */
    public function debit(
        User $user,
        float|int|string $amount,
        string $type,
        ?string $reference = null,
        ?string $description = null
    ): Transaction {
        $amount    = $this->normalizeAmount($amount);
        $reference ??= (string) Str::uuid();

        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
            $wallet = $this->lockWallet((int) $user->id);

            $before = (float) $wallet->balance;

            if ($before < $amount) {
                throw new InsufficientBalanceException('Insufficient available balance.');
            }

            $after = $before - $amount;
            $wallet->balance = $after;

            if ($type === Transaction::TYPE_WITHDRAWAL) {
                $wallet->total_withdrawn = (float) $wallet->total_withdrawn + $amount;
            }

            $wallet->save();

            return $this->createTransaction(
                $user,
                -$amount,
                $type,
                self::BALANCE_AVAILABLE,
                $before,
                $after,
                $reference,
                $description
            );
        });
    }

    // ---------------------------------------------------------------------
    // Pending balance
    // ---------------------------------------------------------------------

    public function creditPending(
        User $user,
        float|int|string $amount,
        ?string $reference = null,
        ?string $description = null
    ): Transaction {
        $amount    = $this->normalizeAmount($amount);
        $reference ??= (string) Str::uuid();

        return DB::transaction(function () use ($user, $amount, $reference, $description) {
            $wallet = $this->lockWallet((int) $user->id);

            $before = (float) $wallet->pending_balance;
            $after  = $before + $amount;

            $wallet->pending_balance = $after;
            $wallet->save();

            return $this->createTransaction(
                $user,
                $amount,
                Transaction::TYPE_PENDING_PAYOUT,
                self::BALANCE_PENDING,
                $before,
                $after,
                $reference,
                $description
            );
        });
    }

    /**
     * @throws InsufficientBalanceException
     */
    public function debitPending(
        User $user,
        float|int|string $amount,
        string $type = Transaction::TYPE_PENDING_RELEASE,
        ?string $reference = null,
        ?string $description = null
    ): Transaction {
        $amount    = $this->normalizeAmount($amount);
        $reference ??= (string) Str::uuid();

        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
            $wallet = $this->lockWallet((int) $user->id);

            $before = (float) $wallet->pending_balance;

            if ($before < $amount) {
                throw new InsufficientBalanceException('Insufficient pending balance.');
            }

            $after = $before - $amount;
            $wallet->pending_balance = $after;
            $wallet->save();

            return $this->createTransaction(
                $user,
                -$amount,
                $type,
                self::BALANCE_PENDING,
                $before,
                $after,
                $reference,
                $description
            );
        });
    }

    // ---------------------------------------------------------------------
    // Composed operations
    // ---------------------------------------------------------------------

    /**
     * Move funds from pending balance to available balance.
     *
     * @return Transaction[]  [pending leg, available leg]
     *
     * @throws InsufficientBalanceException
     */
    public function releasePendingToAvailable(
        User $user,
        float|int|string $amount,
        ?string $reference = null,
        ?string $description = null
    ): array {
        $amount = $this->normalizeAmount($amount);

        \Log::info("User");
        \Log::info($user);

        return true;


        return DB::transaction(function () use ($user, $amount, $description) {
            $wallet = $this->lockWallet((int) $user->id);

            $pendingBefore = (float) $wallet->pending_balance;

            \Log::info("Wallet");
            \Log::info($wallet);

            if ($pendingBefore < $amount) {
                throw new InsufficientBalanceException('Insufficient pending balance.');
            }

            $balanceBefore = (float) $wallet->balance;

            $pendingAfter = $pendingBefore - $amount;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->pending_balance = $pendingAfter;
            $wallet->balance         = $balanceAfter;
            $wallet->save();

            // Each row gets its own reference — transactions.reference is UNIQUE.
            return [
                $this->createTransaction(
                    $user,
                    -$amount,
                    Transaction::TYPE_PENDING_RELEASE,
                    self::BALANCE_PENDING,
                    $pendingBefore,
                    $pendingAfter,
                    null,
                    $description
                ),
                $this->createTransaction(
                    $user,
                    $amount,
                    Transaction::TYPE_PAYOUT,
                    self::BALANCE_AVAILABLE,
                    $balanceBefore,
                    $balanceAfter,
                    null,
                    $description
                ),
            ];
        });
    }

    /**
     * Charge a platform fee: debit the seller's pending balance and credit
     * the platform's available balance as one atomic operation.
     *
     * @return Transaction[]  [seller leg, platform leg]
     *
     * @throws InsufficientBalanceException
     */
    public function chargeFee(
        User $seller,
        User $platform,
        float|int|string $feeAmount,
        ?string $reference = null,
        ?string $description = null
    ): array {
        $feeAmount = $this->normalizeAmount($feeAmount);

        return DB::transaction(function () use ($seller, $platform, $feeAmount, $description) {
            $wallets = $this->lockWallets((int) $seller->id, (int) $platform->id);

            $sellerWallet   = $wallets[(int) $seller->id];
            $platformWallet = $wallets[(int) $platform->id];

            // 1. Deduct fee from seller's pending balance.
            $sellerBefore = (float) $sellerWallet->pending_balance;

            if ($sellerBefore < $feeAmount) {
                throw new InsufficientBalanceException('Insufficient pending balance.');
            }

            $sellerAfter = $sellerBefore - $feeAmount;
            $sellerWallet->pending_balance = $sellerAfter;
            $sellerWallet->save();

            // Each row gets its own reference — transactions.reference is UNIQUE.
            $sellerLeg = $this->createTransaction(
                $seller,
                -$feeAmount,
                Transaction::TYPE_FEE,
                self::BALANCE_PENDING,
                $sellerBefore,
                $sellerAfter,
                null,
                $description
            );

            // 2. Credit platform's available balance.
            $platformBefore = (float) $platformWallet->balance;
            $platformAfter  = $platformBefore + $feeAmount;

            $platformWallet->balance = $platformAfter;
            $platformWallet->save();

            $platformLeg = $this->createTransaction(
                $platform,
                $feeAmount,
                Transaction::TYPE_FEE,
                self::BALANCE_AVAILABLE,
                $platformBefore,
                $platformAfter,
                null,
                $description
            );

            return [$sellerLeg, $platformLeg];
        });
    }

    // ---------------------------------------------------------------------
    // Auditing / reconciliation
    // ---------------------------------------------------------------------

    public function findTransactionsByReference(string $reference): Collection
    {
        return Transaction::where('reference', $reference)
            ->orderBy('id')
            ->get();
    }

    /**
     * Recompute the denormalised lifetime counters from the ledger.
     */
    public function recalculateTotals(User $user): Wallet
    {
        return DB::transaction(function () use ($user) {
            $wallet = $this->lockWallet((int) $user->id);

            $deposited = (float) Transaction::where('user_id', $user->id)
                ->where('type', Transaction::TYPE_DEPOSIT)
                ->where('balance_type', self::BALANCE_AVAILABLE)
                ->where('amount', '>', 0)
                ->sum('amount');

            $withdrawn = (float) Transaction::where('user_id', $user->id)
                ->where('type', Transaction::TYPE_WITHDRAWAL)
                ->where('balance_type', self::BALANCE_AVAILABLE)
                ->where('amount', '<', 0)
                ->sum('amount');

            $wallet->total_deposited = $deposited;
            $wallet->total_withdrawn = abs($withdrawn);
            $wallet->save();

            return $wallet;
        });
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    private function lockWallet(int $userId): Wallet
    {
        $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

        if ($wallet) {
            return $wallet;
        }

        $now = now();

        Wallet::insertOrIgnore([
            'user_id'          => $userId,
            'balance'          => 0,
            'pending_balance'  => 0,
            'total_deposited'  => 0,
            'total_withdrawn'  => 0,
            'currency'         => 'KES',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        return Wallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();
    }

    /**
     * @return array<int, Wallet>  keyed by user id
     */
    private function lockWallets(int ...$userIds): array
    {
        $ids = array_values(array_unique($userIds));
        sort($ids, SORT_NUMERIC);

        $wallets = [];
        foreach ($ids as $id) {
            $wallets[$id] = $this->lockWallet($id);
        }

        return $wallets;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function normalizeAmount(float|int|string $amount): float
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Amount must be numeric.');
        }

        $amount = (float) $amount;

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        // Tolerate float representation noise (0.1 + 0.2 → 0.30000000000000004)
        // but reject anything genuinely over-precise.
        if (abs($amount - round($amount, 2)) > 1e-9) {
            throw new InvalidArgumentException('Amount cannot have more than 2 decimal places.');
        }

        return round($amount, 2);
    }

    private function createTransaction(
        User $user,
        float $amount,
        string $type,
        string $balanceType,
        float $balanceBefore,
        float $balanceAfter,
        ?string $reference = null,
        ?string $description = null
    ): Transaction {
        return Transaction::create([
            'user_id'        => $user->id,
            'type'           => $type,
            'amount'         => $amount,
            'balance_type'   => $balanceType,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
            'reference'      => $reference ?? (string) Str::uuid(),
            'description'    => $description,
            'status'         => Transaction::STATUS_COMPLETED,
            'completed_at'   => now(),
        ]);
    }
}