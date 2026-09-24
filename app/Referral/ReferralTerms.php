<?php

namespace App\Referral;

use App\Models\ReferralTerm;

/**
 * Immutable snapshot of a referrer's terms.
 *
 * Returned by ReferralService::termsFor(). The caller never touches the
 * underlying model or config, so an override row changing mid-request
 * can't surprise a reward calculation.
 *
 * `is_override` is true only when a live (non-expired) ReferralTerm row
 * backs these values. It drives the admin UI's "custom terms active"
 * badge and lets reporting distinguish default-rate earnings from
 * negotiated-rate earnings.
 */
class ReferralTerms
{
    public function __construct(
        public readonly float $reward_percentage,
        public readonly int $max_transactions,
        public readonly int $window_months,
        public readonly float $referee_discount_pct,
        public readonly float $referee_discount_cap,
        public readonly bool $is_override,
    ) {
    }

    public static function default(): self
    {
        $defaults = config('services.betslip_pirates.referral_defaults');

        return new self(
            reward_percentage: (float) $defaults['reward_percentage'],
            max_transactions: (int) $defaults['max_transactions'],
            window_months: (int) $defaults['window_months'],
            referee_discount_pct: (float) $defaults['referee_discount_pct'],
            referee_discount_cap: (float) $defaults['referee_discount_cap'],
            is_override: false,
        );
    }

    public static function fromModel(ReferralTerm $model): self
    {
        return new self(
            reward_percentage: (float) $model->reward_percentage,
            max_transactions: (int) $model->max_transactions,
            window_months: (int) $model->window_months,
            referee_discount_pct: (float) $model->referee_discount_pct,
            referee_discount_cap: (float) $model->referee_discount_cap,
            is_override: true,
        );
    }
}