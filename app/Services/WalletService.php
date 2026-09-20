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
 *  - Each ledger row gets its own UUID in `transactions.reference`
 *    (the column is UNIQUE). Rows belonging to the same logical operation
 *    are correlated by (user_id, created_at) proximity and by the
 *    `description`, not by a shared reference.
 *
 * Requires: wallets.user_id UNIQUE; transactions.balance_type.
 */
class WalletService
{
    public const BALANCE_AVAILABLE = 'available';

    public const BALANCE_ESCROW = 'escrow';

    // ---------------------------------------------------------------------
    // Reads
    // ---------------------------------------------------------------------

    public function getWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'escrow_balance' => 0,
                'total_deposited' => 0,
                'total_withdrawn' => 0,
                'currency' => 'KES',
            ]
        );
    }

    /**
     * Advisory balance check only. Reads outside any lock; NOT an
     * authorization gate. The authoritative check lives in debit(), hold(), and refundEscrow(),
     * which re-check under a row lock and throw InsufficientBalanceException.
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

    public function getEscrowBalance(User $user): float
    {
        return (float) $this->getWallet($user)->escrow_balance;
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
        $amount = $this->normalizeAmount($amount);
        $reference ??= (string) Str::uuid();

        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
            $wallet = $this->lockWallet((int) $user->id);

            $before = (float) $wallet->balance;
            $after = $before + $amount;

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
        $amount = $this->normalizeAmount($amount);
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
            'user_id' => $userId,
            'balance' => 0,
            'escrow_balance' => 0,
            'total_deposited' => 0,
            'total_withdrawn' => 0,
            'currency' => 'KES',
            'created_at' => $now,
            'updated_at' => $now,
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
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_type' => $balanceType,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => $reference ?? (string) Str::uuid(),
            'description' => $description,
            'status' => Transaction::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Move funds from available into escrow.
     *
     * @return Transaction[]  [available leg, escrow leg]
     *
     * @throws InsufficientBalanceException
     */
    public function hold(
        User $user,
        float|int|string $amount,
        string $type = Transaction::TYPE_PURCHASE,
        ?string $description = null
    ): array {
        $amount = $this->normalizeAmount($amount);

        return DB::transaction(function () use ($user, $amount, $type, $description) {
            $wallet = $this->lockWallet((int) $user->id);

            $availBefore = (float) $wallet->balance;
            if ($availBefore < $amount) {
                throw new InsufficientBalanceException('Insufficient available balance.');
            }

            $escrowBefore = (float) $wallet->escrow_balance;

            $availAfter = $availBefore - $amount;
            $escrowAfter = $escrowBefore + $amount;

            $wallet->balance = $availAfter;
            $wallet->escrow_balance = $escrowAfter;
            $wallet->save();

            // Each row gets its own reference — transactions.reference is UNIQUE.
            return [
                $this->createTransaction(
                    $user,
                    -$amount,
                    $type,
                    self::BALANCE_AVAILABLE,
                    $availBefore,
                    $availAfter,
                    null,
                    $description
                ),
                $this->createTransaction(
                    $user,
                    $amount,
                    $type,
                    self::BALANCE_ESCROW,
                    $escrowBefore,
                    $escrowAfter,
                    null,
                    $description
                ),
            ];
        });
    }

    /**
     * Move funds from escrow back into available.
     *
     * The inverse of hold(). Used on loss and void settlements to return the
     * buyer's held funds.
     *
     * Writes two ledger rows against the same wallet:
     *   - escrow leg    (internal bookkeeping; filtered from user-facing lists)
     *   - available leg (what the user sees)
     *
     * @param  string|null  $context  Short noun phrase identifying the event,
     *                                e.g. "Betslip #KUS-3P58-IJQ". Prefixed into
     *                                each leg's description.
     *
     * @return Transaction[]  [escrow leg, available leg]
     *
     * @throws InsufficientBalanceException
     */
    public function refundEscrow(
        User $user,
        float|int|string $amount,
        string $type = Transaction::TYPE_REFUND,
        ?string $context = null,
    ): array {
        $amount = $this->normalizeAmount($amount);
        $ctx = $context !== null && $context !== '' ? $context : 'refund';

        $escrowDesc = "Escrow released for {$ctx}";
        $availableDesc = "Refund for {$ctx}";

        return DB::transaction(function () use ($user, $amount, $type, $escrowDesc, $availableDesc) {
            $wallet = $this->lockWallet((int) $user->id);

            $escrowBefore = (float) $wallet->escrow_balance;
            if ($escrowBefore < $amount) {
                throw new InsufficientBalanceException('Insufficient escrow balance.');
            }

            $availBefore = (float) $wallet->balance;

            $escrowAfter = $escrowBefore - $amount;
            $availAfter = $availBefore + $amount;

            $wallet->escrow_balance = $escrowAfter;
            $wallet->balance = $availAfter;
            $wallet->save();

            return [
                $this->createTransaction(
                    $user,
                    -$amount,
                    $type,
                    self::BALANCE_ESCROW,
                    $escrowBefore,
                    $escrowAfter,
                    null,
                    $escrowDesc
                ),
                $this->createTransaction(
                    $user,
                    $amount,
                    $type,
                    self::BALANCE_AVAILABLE,
                    $availBefore,
                    $availAfter,
                    null,
                    $availableDesc
                ),
            ];
        });
    }
    /**
     * Atomic settlement primitive for a winning purchase.
     *
     * Releases the buyer's escrow, credits the seller with the gross, then
     * debits the seller's fee and credits it to the platform. Writes four
     * ledger rows under a single transaction with the three wallets locked
     * in ascending user_id order.
     *
     * Each ledger leg gets a distinct description so the audit trail reads
     * correctly per wallet — a buyer never sees a "payout" line, a seller
     * never sees an "escrow released" line, etc.
     *
     * @param  string|null  $context  Short noun phrase identifying the event,
     *                                e.g. "Betslip #CBG-NAFR-DRF". Prefixed into
     *                                each leg's description.
     *
     * @return Transaction[]  [buyer escrow debit, seller gross credit,
     *                         seller fee debit, platform fee credit]
     *
     * @throws InsufficientBalanceException
     */
    public function settleEscrowToSeller(
        User $buyer,
        User $seller,
        User $platform,
        float|int|string $gross,
        float|int|string $fee = 0.0,
        ?string $context = null,
    ): array {
        $gross = $this->normalizeAmount($gross);

        $feeFloat = (float) $fee;
        if ($feeFloat < 0) {
            throw new InvalidArgumentException('Fee cannot be negative.');
        }
        $fee = $feeFloat > 0 ? $this->normalizeAmount($fee) : 0.0;

        $ctx = $context !== null && $context !== '' ? $context : 'settlement';

        // Build once, use per leg. Keeps wording consistent across the codebase.
        $buyerDesc = "Escrow released for {$ctx}";
        $sellerGrossDesc = "Payout for {$ctx}";
        $sellerFeeDesc = "Platform fee for {$ctx}";
        $platformDesc = "Platform fee from {$ctx}";

        return DB::transaction(function () use ($buyer, $seller, $platform, $gross, $fee, $buyerDesc, $sellerGrossDesc, $sellerFeeDesc, $platformDesc, ) {
            $wallets = $this->lockWallets(
                (int) $buyer->id,
                (int) $seller->id,
                (int) $platform->id,
            );

            $buyerWallet = $wallets[(int) $buyer->id];
            $sellerWallet = $wallets[(int) $seller->id];
            $platformWallet = $wallets[(int) $platform->id];

            // 1. Buyer's escrow releases the gross.
            $buyerEscrowBefore = (float) $buyerWallet->escrow_balance;
            if ($buyerEscrowBefore < $gross) {
                throw new InsufficientBalanceException('Insufficient escrow balance.');
            }
            $buyerEscrowAfter = $buyerEscrowBefore - $gross;
            $buyerWallet->escrow_balance = $buyerEscrowAfter;
            $buyerWallet->save();

            $buyerRow = $this->createTransaction(
                $buyer,
                -$gross,
                Transaction::TYPE_PURCHASE,
                self::BALANCE_ESCROW,
                $buyerEscrowBefore,
                $buyerEscrowAfter,
                null,
                $buyerDesc,
            );

            // 2. Seller receives the gross into available.
            $sellerAvailBefore = (float) $sellerWallet->balance;
            $sellerAvailAfter = $sellerAvailBefore + $gross;
            $sellerFinalAfter = $sellerAvailAfter - $fee;

            $sellerWallet->balance = $sellerFinalAfter;
            $sellerWallet->save();

            $sellerGrossRow = $this->createTransaction(
                $seller,
                $gross,
                Transaction::TYPE_PAYOUT,
                self::BALANCE_AVAILABLE,
                $sellerAvailBefore,
                $sellerAvailAfter,
                null,
                $sellerGrossDesc,
            );

            // 3. Seller pays the fee (separate row so the statement reads
            //    gross-in then fee-out).
            $sellerFeeRow = null;
            if ($fee > 0) {
                $sellerFeeRow = $this->createTransaction(
                    $seller,
                    -$fee,
                    Transaction::TYPE_FEE,
                    self::BALANCE_AVAILABLE,
                    $sellerAvailAfter,
                    $sellerFinalAfter,
                    null,
                    $sellerFeeDesc,
                );
            }

            // 4. Platform receives the fee.
            $platformRow = null;
            if ($fee > 0) {
                $platformAvailBefore = (float) $platformWallet->balance;
                $platformAvailAfter = $platformAvailBefore + $fee;
                $platformWallet->balance = $platformAvailAfter;
                $platformWallet->save();

                $platformRow = $this->createTransaction(
                    $platform,
                    $fee,
                    Transaction::TYPE_FEE,
                    self::BALANCE_AVAILABLE,
                    $platformAvailBefore,
                    $platformAvailAfter,
                    null,
                    $platformDesc,
                );
            }

            return array_values(array_filter([
                $buyerRow,
                $sellerGrossRow,
                $sellerFeeRow,
                $platformRow,
            ]));
        });
    }
}