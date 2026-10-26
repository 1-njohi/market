<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Seller eligibility rules. Pure computation — no writes, no side effects.
 * All thresholds come from config('marketplace.*').
 *
 * The sample is a user's betslip picks over the rolling window, deduplicated
 * by (fixture_id, market_id, selection). Duplicate picks across different
 * betslips count once. Voided picks are excluded from both numerator and
 * denominator.
 *
 * Contest picks are deliberately not part of this sample — selling and
 * contest play reward different behaviours and mixing them lets one
 * compensate for the other.
 */
class SellerEligibilityService
{
    /**
     * A user is qualified if they have enough settled picks in the window
     * AND their win rate meets the enter threshold.
     */
    public function isQualified(User $user): bool
    {
        $count = $this->settledPicksCount($user);
        if ($count < $this->minSettledPicks()) {
            return false;
        }

        $rate = $this->pickWinRate($user);

        return $rate !== null && $rate >= $this->minWinRateToSell();
    }
    /**
     * Whether the user may currently list betslips for sale.
     *
     * Status-based, not numeric — the sellable_status column is the
     * authoritative record of the recompute command's decision.
     *
     *   ineligible  → cannot sell.
     *   sellable    → can sell.
     *   warned      → can sell (grace period is a protection).
     *   delisted    → cannot sell.
     */
    public function canSell(User $user): bool
    {
        return in_array($user->sellable_status, ['sellable', 'warned'], true);
    }
    /**
     * Whether the user may host a contest.
     *
     * Numeric qualification only — delisted sellers can still host.
     * Hosting doesn't flood the marketplace with inventory, so the
     * hysteresis that protects buyers doesn't apply here.
     */
    public function canHost(User $user): bool
    {
        return $this->isQualified($user);
    }

    /**
     * Win rate over the rolling window, or null if there aren't enough
     * settled picks to make the number meaningful.
     *
     * Rounded to 4 decimals — matches the users.pick_win_rate_rolling
     * decimal(5,4) column.
     */
    public function pickWinRate(User $user): ?float
    {
        $sample = $this->deduplicatedSample($user);

        $won = $sample->where('status', 'won')->count();
        $lost = $sample->where('status', 'lost')->count();

        $decided = $won + $lost;
        if ($decided < $this->minSettledPicks()) {
            return null;
        }

        return round($won / 1, 4);
    }

    /**
     * Number of settled (non-void) unique picks in the rolling window.
     */
    public function settledPicksCount(User $user): int
    {
        return $this->deduplicatedSample($user)
            ->whereIn('status', ['won', 'lost'])
            ->count();
    }

    /**
     * Progress snapshot for UI ("3 more picks until qualified").
     *
     * current_win_rate is null when there aren't enough picks — the UI
     * should show a count-based message in that case rather than a rate.
     */
    public function progress(User $user): array
    {
        $count = $this->settledPicksCount($user);
        $rate = $this->pickWinRate($user);

        return [
            'current_picks' => $count,
            'required_picks' => $this->minSettledPicks(),
            'current_win_rate' => $rate,
            'required_win_rate' => $this->minWinRateToSell(),
            'qualified' => $this->isQualified($user),
        ];
    }

    /**
     * Deduplicated pick sample for the user in the rolling window.
     *
     * Dedup key is (fixture_id, market_id, selection). Same pick on
     * multiple betslips counts once.
     *
     * Voided picks are retained in the collection so the caller can choose
     * to include or exclude them. Rate and count both exclude them
     * explicitly.
     *
     * @return Collection<int, object>
     */
    private function deduplicatedSample(User $user): Collection
    {
        $windowStart = now()->subDays($this->rollingWindowDays());

        return DB::table('betslip_odd')
            ->join('betslips', 'betslips.id', '=', 'betslip_odd.betslip_id')
            ->join('odds', 'odds.id', '=', 'betslip_odd.odd_id')
            ->where('betslips.user_id', $user->id)
            ->whereIn('betslip_odd.status', ['won', 'lost', 'void'])
            ->where('betslip_odd.updated_at', '>=', $windowStart)
            ->select([
                'odds.fixture_id',
                'odds.market_id',
                'odds.value as selection',
                'betslip_odd.status',
            ])
            ->get()
            ->unique(fn($row) => "{$row->fixture_id}:{$row->market_id}:{$row->selection}")
            ->values();
    }

    // ────────────── Config accessors ────────────────────────────────────
    //
    // Every threshold is read through one of these so a grep for
    // config('marketplace. catches every read site.

    private function minSettledPicks(): int
    {
        return (int) config('marketplace.min_settled_picks');
    }

    private function minWinRateToSell(): float
    {
        return (float) config('marketplace.min_win_rate_to_sell');
    }

    private function rollingWindowDays(): int
    {
        return (int) config('marketplace.rolling_window_days');
    }
}