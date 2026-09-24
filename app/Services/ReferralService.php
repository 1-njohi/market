<?php

namespace App\Services;

use App\Models\ReferralTerm;
use App\Models\Referral;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\User;
use App\Referral\ReferralTerms;
use App\Notifications\ReferralRewardNotification;
use App\Notifications\ReferralAttributedNotification;
use App\Notifications\ReferralSignupNotification;
use App\Models\Betslip;
class ReferralService
{
    public function __construct(
        protected WalletService $walletService,
    ) {
    }

    /**
     * Resolve the terms that apply to a referrer right now.
     *
     * Absence of a ReferralTerm row → defaults from config.
     * Row present with null expires_at → custom terms, never expire.
     * Row present with past expires_at → defaults.
     *
     * Read live, not snapshotted. An admin upgrading a referrer takes
     * effect on their next reward, even for referees who signed up long
     * ago.
     */
    public function termsFor(User $referrer): ReferralTerms
    {
        $override = ReferralTerm::where('user_id', $referrer->id)->first();

        if ($override === null || $override->isExpired()) {
            return ReferralTerms::default();
        }

        return ReferralTerms::fromModel($override);
    }
    /**
     * Attribute a referee to a referrer, given a referral code.
     *
     * Returns the Referral row on success, null if the code is invalid or
     * the attribution would be a self-referral. Idempotent: if the referee
     * is already attributed, returns the existing Referral row unchanged.
     *
     * The stored `code_used` is the canonical (uppercase) form, not the
     * input string. So a user submitting `denis-xk4` is attributed to the
     * referrer whose code is `DENIS-XK4`, and the audit trail is stable.
     */
    public function attribute(User $referee, string $code): ?Referral
    {
        // Idempotency: already attributed. Return the existing row.
        $existing = Referral::where('referee_id', $referee->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        $normalized = strtoupper(trim($code));
        if ($normalized === '') {
            return null;
        }

        $referrer = User::where('referral_code', $normalized)->first();
        if ($referrer === null) {
            return null;
        }

        if ((int) $referrer->id === (int) $referee->id) {
            return null;
        }

        $terms = $this->termsFor($referrer);

        return DB::transaction(function () use ($referee, $referrer, $terms) {
            $referral = Referral::create([
                'referrer_id' => $referrer->id,
                'referee_id' => $referee->id,
                'code_used' => $referrer->referral_code,
                'signed_up_at' => now(),
                'expires_at' => now()->addMonths($terms->window_months),
                'wins_counted' => 0,
                'total_earned' => 0,
            ]);

            $referee->update(['referred_by_id' => $referrer->id]);

            $referee->notify(new ReferralAttributedNotification(
                $referrer,
                (float) $terms->referee_discount_pct,
                (float) $terms->referee_discount_cap,
            ));

            $referrer->notify(new ReferralSignupNotification(
                $referee,
                (float) $terms->reward_percentage,
            ));

            return $referral;
        });
    }

    /**
     * The welcome discount for a buyer's next purchase, or 0.0 if none applies.
     *
     * A buyer qualifies when:
     *   - they were referred (referred_by_id is set), and
     *   - they haven't yet used the discount, and
     *   - the referrer still exists.
     *
     * The discount is `listed_price × referee_discount_pct`, capped at
     * `referee_discount_cap`, from the referrer's current terms.
     */
    public function welcomeDiscountFor(User $buyer, float $listedPrice): float
    {
        if ($buyer->welcome_discount_used) {
            return 0.0;
        }

        if ($buyer->referred_by_id === null) {
            return 0.0;
        }

        $referrer = User::find($buyer->referred_by_id);
        if ($referrer === null) {
            return 0.0;
        }

        $terms = $this->termsFor($referrer);

        $percentDiscount = $listedPrice * $terms->referee_discount_pct;

        return round(min($percentDiscount, $terms->referee_discount_cap), 2);
    }
    /**
     * If the given referee is owed a reward for a winning transaction, credit
     * their referrer and return the updated Referral row. Returns null when
     * no reward applies (no referral, expired, cap reached, non-positive
     * amount, or the referrer no longer exists).
     *
     * Must be called inside an outer DB transaction so that the wallet
     * credit and the counter increments roll back together on failure.
     */
    public function rewardFor(User $referee, float $listedPrice, Betslip $betslip): ?Referral
    {
        $referral = Referral::where('referee_id', $referee->id)->first();

        if ($referral === null || $referral->isExpired()) {
            return null;
        }

        $referrer = $referral->referrer;
        if ($referrer === null) {
            return null;
        }

        $terms = $this->termsFor($referrer);

        if ($referral->wins_counted >= $terms->max_transactions) {
            return null;
        }

        $amount = round($listedPrice * $terms->reward_percentage, 2);

        if ($amount <= 0) {
            return null;
        }

        $this->walletService->credit(
            $referrer,
            $amount,
            Transaction::TYPE_REFERRAL_REWARD,
            null,
            "Referral reward from @{$referee->name}",
        );

        $referral->update([
            'wins_counted' => $referral->wins_counted + 1,
            'total_earned' => round((float) $referral->total_earned + $amount, 2),
        ]);

        $referrer->notify(new ReferralRewardNotification($referee, $betslip, $amount));

        return $referral->fresh();
    }
    /**
     * Aggregate view of a referrer's performance for the /refer page.
     *
     * @return array{
     *     code: string,
     *     share_url: string,
     *     referee_count: int,
     *     total_earned: float,
     *     total_wins: int,
     *     referees: array<int, array{
     *         referee_id: int,
     *         referee_name: string,
     *         wins_counted: int,
     *         total_earned: float,
     *         expires_at: string,
     *         is_expired: bool,
     *     }>,
     * }
     */
    public function dashboardFor(User $referrer): array
    {
        $referrals = Referral::where('referrer_id', $referrer->id)
            ->with('referee:id,name')
            ->orderByDesc('total_earned')
            ->get();

        $referees = $referrals->map(function (Referral $r) {
            return [
                'referee_id' => $r->referee_id,
                'referee_name' => $r->referee->name ?? 'Unknown',
                'wins_counted' => $r->wins_counted,
                'total_earned' => (float) $r->total_earned,
                'expires_at' => $r->expires_at->toIso8601String(),
                'is_expired' => $r->isExpired(),
            ];
        })->all();

        $terms = $this->termsFor($referrer);

        return [
            'code' => $referrer->referral_code,
            'share_url' => url("/r/{$referrer->referral_code}"),
            'referee_count' => $referrals->count(),
            'total_earned' => round((float) $referrals->sum('total_earned'), 2),
            'total_wins' => (int) $referrals->sum('wins_counted'),
            'referees' => $referees,
            'terms' => [
                'reward_percentage' => $terms->reward_percentage,
                'max_transactions' => $terms->max_transactions,
                'window_months' => $terms->window_months,
                'referee_discount_pct' => $terms->referee_discount_pct,
                'referee_discount_cap' => $terms->referee_discount_cap,
                'is_override' => $terms->is_override,
            ],
        ];
    }

    /**
     * Compact referral summary for a dashboard card.
     *
     * Always returns a card — even for brand-new accounts with zero
     * referees and zero earnings. The empty state is itself the nudge:
     * a user who hasn't referred yet sees "0 referees, KES 0.00 earned"
     * and the pitch to share their code.
     *
     * @return array{code: string, share_url: string, referee_count: int, total_earned: float, total_wins: int}
     */
    public function cardFor(User $user): array
    {
        $referrals = Referral::where('referrer_id', $user->id)->get();

        return [
            'code' => $user->referral_code,
            'share_url' => url("/r/{$user->referral_code}"),
            'referee_count' => $referrals->count(),
            'total_earned' => round((float) $referrals->sum('total_earned'), 2),
            'total_wins' => (int) $referrals->sum('wins_counted'),
        ];
    }
}