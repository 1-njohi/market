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

    public function __construct(WalletService $walletService, protected ReferralService $referralService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Purchase a betslip (one buyer buys one betslip)
     */
    public function purchase(User $buyer, Betslip $betslip): BetslipUserPurchase
    {
        if ($buyer->id === $betslip->user_id) {
            throw new \Exception('You cannot purchase your own betslip.');
        }

        if ($betslip->status !== 'pending' || $betslip->remaining < 1) {
            throw new \Exception('This betslip is not available for purchase.');
        }

        $listedPrice = (float) $betslip->price;
        $discount = $this->referralService->welcomeDiscountFor($buyer, $listedPrice);
        $paidPrice = round($listedPrice - $discount, 2);
        $totalOdds = $betslip->total_odds;

        if (!$this->walletService->hasSufficientBalance($buyer, $paidPrice)) {
            throw new \Exception('Insufficient wallet balance.');
        }

        return DB::transaction(function () use ($buyer, $betslip, $listedPrice, $paidPrice, $discount, $totalOdds) {
            $betslip = Betslip::where('id', $betslip->id)
                ->lockForUpdate()
                ->first();

            if ($betslip->remaining < 1) {
                throw new \Exception('This betslip is no longer available.');
            }

            $seller = $betslip->seller;
            $reference = 'PUR-' . strtoupper(Str::random(10));

            $purchase = BetslipUserPurchase::create([
                'betslip_id' => $betslip->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'purchase_price' => $paidPrice,
                'total_odds' => $totalOdds,
                'status' => 'pending',
                'payment_method' => 'wallet',
                'payment_reference' => $reference,
                'purchased_at' => now(),
            ]);

            $txs = $this->walletService->hold(
                $buyer,
                $paidPrice,
                \App\Models\Transaction::TYPE_PURCHASE,
                "Purchase of betslip #{$betslip->code}"
            );

            $txs[0]->update([
                'transactionable_type' => BetslipUserPurchase::class,
                'transactionable_id' => $purchase->id,
            ]);

            // Consume the welcome discount if one was applied.
            if ($discount > 0) {
                $buyer->forceFill(['welcome_discount_used' => true])->save();
            }

            Cache::forget(DashboardController::cacheKey($buyer));

            return $purchase;
        });
    }
    public function canPurchase(User $buyer, Betslip $betslip): bool
    {
        if ($buyer->id === $betslip->user_id) {
            return false;
        }
        if ($betslip->status !== 'pending' || $betslip->remaining < 1) {
            return false;
        }

        $paid = $this->effectivePrice($buyer, $betslip);

        return $this->walletService->hasSufficientBalance($buyer, $paid);
    }

    public function getPurchaseAvailabilityMessage(User $buyer, Betslip $betslip): ?string
    {
        if ($buyer->id === $betslip->user_id) {
            return 'You cannot purchase your own betslip.';
        }
        if ($betslip->status !== 'pending' || $betslip->remaining < 1) {
            return 'This betslip is no longer available.';
        }

        $paid = $this->effectivePrice($buyer, $betslip);

        if (!$this->walletService->hasSufficientBalance($buyer, $paid)) {
            return 'Insufficient wallet balance. Please deposit more funds.';
        }

        return null;
    }

    private function effectivePrice(User $buyer, Betslip $betslip): float
    {
        $listed = (float) $betslip->price;
        $discount = $this->referralService->welcomeDiscountFor($buyer, $listed);

        return round($listed - $discount, 2);
    }
}