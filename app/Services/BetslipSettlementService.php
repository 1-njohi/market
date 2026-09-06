<?php

namespace App\Services;

use App\Models\Betslip;
use Illuminate\Support\Facades\DB;

class BetslipSettlementService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Settle a betslip based on its outcome
     *
     * @param Betslip $betslip
     * @param bool $isWinner - Whether the betslip won or lost
     */
    public function settle(Betslip $betslip, bool $isWinner): void
    {
        DB::transaction(function () use ($betslip, $isWinner) {
            $purchases = $betslip->purchases()->where('status', 'pending')->get();

            foreach ($purchases as $purchase) {
                $buyer = $purchase->buyer;
                $seller = $purchase->seller;
                $price = $purchase->purchase_price;

                if ($isWinner) {
                    // ✅ BETSLIP WON – Seller gets paid

                    // 1. Release seller's pending balance → available balance
                    $this->walletService->releasePendingToAvailable(
                        $seller,
                        $price,
                        $betslip->code,
                        "Payout for winning betslip #{$betslip->code}"
                    );

                    // 2. Update purchase status
                    $purchase->update([
                        'status' => 'completed',
                    ]);

                } else {
                    // ❌ BETSLIP LOST – Buyer gets refunded

                    // 1. Refund the buyer
                    $this->walletService->credit(
                        $buyer,
                        $price,
                        'refund',
                        $betslip->code,
                        "Refund for losing betslip #{$betslip->code}"
                    );

                    // 2. Reverse seller's pending balance
                    $this->walletService->debitPending(
                        $seller,
                        $price,
                        $betslip->code,
                        "Pending payout reversed for losing betslip #{$betslip->code}"
                    );

                    // 3. Update purchase status
                    $purchase->update([
                        'status' => 'refunded',
                    ]);
                }
            }

            // Mark betslip as settled
            $betslip->update([
                'status' => 'settled',
                'is_winner' => $isWinner,
            ]);
        });
    }
}