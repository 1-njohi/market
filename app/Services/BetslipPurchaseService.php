<?php

namespace App\Services;

use App\Models\Betslip;
use App\Models\User;
use App\Models\BetslipUserPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Cache;

class BetslipPurchaseService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Purchase a betslip (one buyer buys one betslip)
     */
    public function purchase(User $buyer, Betslip $betslip): BetslipUserPurchase
    {
        // Prevent seller from buying their own betslip
        if ($buyer->id === $betslip->user_id) {
            throw new \Exception('You cannot purchase your own betslip.');
        }

        // Check if betslip is available
        if ($betslip->status !== 'pending' || $betslip->remaining < 1) {
            throw new \Exception('This betslip is not available for purchase.');
        }

        $price = $betslip->price;
        $totalOdds = $betslip->total_odds;

        // Check buyer balance
        if (!$this->walletService->hasSufficientBalance($buyer, $price)) {
            throw new \Exception('Insufficient wallet balance.');
        }

        // Use a database transaction to prevent race conditions
        return DB::transaction(function () use ($buyer, $betslip, $price, $totalOdds) {
            // Lock the betslip row
            $betslip = Betslip::where('id', $betslip->id)
                ->lockForUpdate()
                ->first();

            // Double-check availability
            if ($betslip->remaining < 1) {
                throw new \Exception('This betslip is no longer available.');
            }

            $seller = $betslip->seller;
            $reference = 'PUR-' . strtoupper(Str::random(10));

            // 1. Debit buyer's wallet (immediate deduction)
            $this->walletService->debit(
                $buyer,
                $price,
                'purchase',
                $reference . "-" . $buyer->code,
                "Purchase of betslip #{$betslip->code}"
            );

            // 2. Credit seller's pending balance (not available yet)
            $this->walletService->creditPending(
                $seller,
                $price,
                $reference . "-" . $seller->code,
                "Pending payout for betslip #{$betslip->code}"
            );

            // 3. Create the purchase record
            $purchase = BetslipUserPurchase::create([
                'betslip_id' => $betslip->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'purchase_price' => $price,
                'total_odds' => $totalOdds,
                'status' => 'pending',
                'payment_method' => 'wallet',
                'payment_reference' => $reference,
                'purchased_at' => now(),
            ]);

            // 4. Decrement remaining shares
            // $betslip->decrement('remaining');

            // 5. If no shares left, update status
            // if ($betslip->remaining === 0) {
            //     $betslip->update(['status' => 'sold_out']);
            // }

            Cache::forget(DashboardController::cacheKey($buyer));
            return $purchase;
        });
    }

    /**
     * Check if a user can purchase a betslip
     */
    public function canPurchase(User $buyer, Betslip $betslip): bool
    {
        if ($buyer->id === $betslip->user_id) {
            return false;
        }
        if ($betslip->status !== 'pending' || $betslip->remaining < 1) {
            return false;
        }
        if (!$this->walletService->hasSufficientBalance($buyer, $betslip->price)) {
            return false;
        }
        return true;
    }

    /**
     * Get availability message
     */
    public function getPurchaseAvailabilityMessage(User $buyer, Betslip $betslip): ?string
    {
        if ($buyer->id === $betslip->user_id) {
            return 'You cannot purchase your own betslip.';
        }
        if ($betslip->status !== 'pending' || $betslip->remaining < 1) {
            return 'This betslip is no longer available.';
        }
        if (!$this->walletService->hasSufficientBalance($buyer, $betslip->price)) {
            return 'Insufficient wallet balance. Please deposit more funds.';
        }
        return null;
    }
}