<?php

namespace App\Observers;

use App\Exceptions\BetslipImmutableException;
use App\Models\Betslip;

/**
 * Enforces the write-once rule for betslips.
 *
 * Once created, the fields listed in IMMUTABLE cannot be changed and
 * the row cannot be deleted. The system-managed lifecycle fields
 * (status, remaining, is_winner, priority_score) are deliberately
 * excluded — the settlement and ranking pipelines need to write them.
 *
 * Delete is blocked because betslip_user_purchases cascades on
 * betslip delete; removing a betslip would silently destroy purchase
 * history for every buyer.
 */
class BetslipObserver
{
    private const IMMUTABLE = [
        'user_id',
        'code',
        'price',
        'total_odds',
        'caption',
    ];

    public function updating(Betslip $betslip): void
    {
        foreach (self::IMMUTABLE as $field) {
            if ($betslip->isDirty($field)) {
                throw new BetslipImmutableException(
                    "Betslip field '{$field}' cannot be changed after creation."
                );
            }
        }
    }

    public function deleting(Betslip $betslip): void
    {
        throw new BetslipImmutableException(
            'Betslips cannot be deleted — cascading would destroy purchase history.'
        );
    }
}