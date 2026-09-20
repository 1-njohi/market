<?php

namespace App\Services;

use App\Models\BetslipUserPurchase;
use App\Models\User;

class PlatformFeeService
{
    /**
     * Get the fee percentage for a given seller.
     */
    public function getFeePercentageFor(User $seller): float
    {
        $totalSales = $this->getTotalSalesCount($seller);
        $tiers = config('services.betslip_pirates.fee_tiers', []);

        foreach ($tiers as $tier) {
            // No max → this is the highest tier, always applies if reached
            if ($tier['max'] === null || $totalSales <= $tier['max']) {
                return (float) $tier['percentage'];
            }
        }

        // Fallback if config is empty — safest is the highest percentage
        return (float) ($tiers[0]['percentage'] ?? 0.25);
    }

    /**
     * Count how many betslips the user has sold (won or lost).
     */
    public function getTotalSalesCount(User $seller): int
    {
        return BetslipUserPurchase::where('seller_id', $seller->id)
            ->whereIn('status', ['voided', 'won', 'lost', 'refunded'])
            ->count();
    }

    /**
     * Split a gross amount into fee + net amounts.
     *
     * @return array{fee: float, net: float, percentage: float}
     */
    public function split(User $seller, float $grossAmount): array
    {
        $percentage = $this->getFeePercentageFor($seller);
        $fee = round($grossAmount * $percentage, 2);
        $net = round($grossAmount - $fee, 2);

        return [
            'fee'        => $fee,
            'net'        => $net,
            'percentage' => $percentage,
        ];
    }
}