<?php

namespace App\Services;

use App\Models\Betslip;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BusinessMetricsService
{
    /** How long a computed summary stays fresh, in seconds. */
    public const CACHE_TTL = 3600;

    /** Windows the admin dashboard exposes. */
    public const WINDOWS = [7, 30, 90];

    /**
     * A purchase is "real" if any of these statuses is set.
     * pending   — money has moved into escrow, awaiting settlement
     * won       — settled, seller paid
     * refunded  — settled, buyer made whole
     * completed — legacy status, still appears in historical data
     */
    public const PURCHASE_STATUSES = ['pending', 'won', 'refunded', 'completed'];

    /** Seller activation window, in hours. */
    public const ACTIVATION_WINDOW_HOURS = 48;

    // ─────────────────────────────────────────────────────────────
    //  Public API
    // ─────────────────────────────────────────────────────────────

    /**
     * All four leading indicators for the given rolling window,
     * each compared against the immediately-preceding window of
     * the same length.
     */
    public function summary(int $days = 7): array
    {
        return Cache::remember(
            "business_metrics:summary:{$days}",
            self::CACHE_TTL,
            fn () => $this->computeSummary($days)
        );
    }

    /**
     * Compute fresh values and write them to the cache, bypassing any
     * existing entry. Used by the scheduled warmer so we don't have to
     * forget() first and risk leaving the cache empty if the compute
     * fails partway through.
     */
    public function refresh(int $days = 7): array
    {
        $summary = $this->computeSummary($days);
        Cache::put("business_metrics:summary:{$days}", $summary, self::CACHE_TTL);

        return $summary;
    }

    /**
     * Drop every cached window. Retained for manual cache busting
     * (e.g. an admin "refresh now" button).
     */
    public function forget(): void
    {
        foreach (self::WINDOWS as $days) {
            Cache::forget("business_metrics:summary:{$days}");
        }
    }

    /**
     * The shared payload builder — called by both summary() and refresh().
     */
    protected function computeSummary(int $days): array
    {
        return [
            'window_days' => $days,
            'generated_at' => now()->toIso8601String(),
            'unlock_rate' => $this->unlockRate($days),
            'buyer_repeat_rate' => $this->buyerRepeatRate($days),
            'seller_activation' => $this->sellerActivation($days),
            'median_time_to_first_sale' => $this->medianTimeToFirstSale($days),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  1. Slip unlock rate
    //  Of the slips listed in the window, what share received at
    //  least one buyer?
    // ─────────────────────────────────────────────────────────────

    public function unlockRate(int $days): array
    {
        [$currentFrom, $currentTo, $previousFrom, $previousTo] = $this->windows($days);

        $current = $this->unlockRateFor($currentFrom, $currentTo);
        $previous = $this->unlockRateFor($previousFrom, $previousTo);

        return $this->withComparison($current, $previous);
    }

    protected function unlockRateFor(CarbonInterface $from, CarbonInterface $to): array
    {
        $listed = Betslip::whereBetween('created_at', [$from, $to])->count();

        if ($listed === 0) {
            return ['listed' => 0, 'unlocked' => 0, 'value' => 0.0];
        }

        $unlocked = Betslip::whereBetween('created_at', [$from, $to])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('betslip_user_purchases')
                    ->whereColumn('betslip_user_purchases.betslip_id', 'betslips.id')
                    ->whereIn('betslip_user_purchases.status', self::PURCHASE_STATUSES);
            })
            ->count();

        return [
            'listed' => $listed,
            'unlocked' => $unlocked,
            'value' => round(($unlocked / $listed) * 100, 1),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  2. Buyer repeat rate (window-local)
    //  Of the buyers who bought at least once in the window, what
    //  share bought two or more times in that same window?
    // ─────────────────────────────────────────────────────────────

    public function buyerRepeatRate(int $days): array
    {
        [$currentFrom, $currentTo, $previousFrom, $previousTo] = $this->windows($days);

        $current = $this->buyerRepeatRateFor($currentFrom, $currentTo);
        $previous = $this->buyerRepeatRateFor($previousFrom, $previousTo);

        return $this->withComparison($current, $previous);
    }

    protected function buyerRepeatRateFor(CarbonInterface $from, CarbonInterface $to): array
    {
        // Bucket purchases per buyer, then count buckets with ≥ 2.
        $rows = DB::table('betslip_user_purchases')
            ->select('buyer_id', DB::raw('COUNT(*) as purchase_count'))
            ->whereIn('status', self::PURCHASE_STATUSES)
            ->whereBetween('purchased_at', [$from, $to])
            ->groupBy('buyer_id')
            ->get();

        $totalBuyers = $rows->count();
        $repeatBuyers = $rows->where('purchase_count', '>=', 2)->count();

        return [
            'total_buyers' => $totalBuyers,
            'repeat_buyers' => $repeatBuyers,
            'value' => $totalBuyers > 0 ? round(($repeatBuyers / $totalBuyers) * 100, 1) : 0.0,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  3. Seller activation
    //  Of the users who signed up in the window (and whose 48h
    //  activation window has fully elapsed), what share listed
    //  their first betslip within 48 hours?
    //
    //  Only counts users whose full activation window has passed.
    //  Including fresh signups whose 48h hasn't elapsed yet would
    //  artificially depress the rate.
    // ─────────────────────────────────────────────────────────────

    public function sellerActivation(int $days): array
    {
        [$currentFrom, $currentTo, $previousFrom, $previousTo] = $this->windows($days);

        $current = $this->sellerActivationFor($currentFrom, $currentTo);
        $previous = $this->sellerActivationFor($previousFrom, $previousTo);

        return $this->withComparison($current, $previous);
    }

    protected function sellerActivationFor(CarbonInterface $from, CarbonInterface $to): array
    {
        // Cut off the cohort before users whose 48h hasn't elapsed.
        $cohortEnd = $to->copy()->min(
            now()->subHours(self::ACTIVATION_WINDOW_HOURS)
        );

        if ($cohortEnd->lte($from)) {
            return [
                'signups' => 0,
                'activated' => 0,
                'window_hours' => self::ACTIVATION_WINDOW_HOURS,
                'value' => 0.0,
            ];
        }

        // Signups in the cohort window.
        $signups = DB::table('users')
            ->whereBetween('created_at', [$from, $cohortEnd])
            ->get(['id', 'created_at']);

        $totalSignups = $signups->count();

        if ($totalSignups === 0) {
            return [
                'signups' => 0,
                'activated' => 0,
                'window_hours' => self::ACTIVATION_WINDOW_HOURS,
                'value' => 0.0,
            ];
        }

        // First betslip listing per user, for just the cohort.
        $firstListings = DB::table('betslips')
            ->whereIn('user_id', $signups->pluck('id'))
            ->groupBy('user_id')
            ->select('user_id', DB::raw('MIN(created_at) as first_listing_at'))
            ->pluck('first_listing_at', 'user_id');

        $activated = $signups->filter(function ($user) use ($firstListings) {
            $firstListing = $firstListings->get($user->id);

            if (! $firstListing) {
                return false;
            }

            return Carbon::parse($user->created_at)
                ->diffInHours(Carbon::parse($firstListing)) <= self::ACTIVATION_WINDOW_HOURS;
        })->count();

        return [
            'signups' => $totalSignups,
            'activated' => $activated,
            'window_hours' => self::ACTIVATION_WINDOW_HOURS,
            'value' => round(($activated / $totalSignups) * 100, 1),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  4. Median time to first sale
    //  For slips whose first purchase landed in the window, the
    //  median number of hours between listing and first sale.
    // ─────────────────────────────────────────────────────────────

    public function medianTimeToFirstSale(int $days): array
    {
        [$currentFrom, $currentTo, $previousFrom, $previousTo] = $this->windows($days);

        $current = $this->medianTimeToFirstSaleFor($currentFrom, $currentTo);
        $previous = $this->medianTimeToFirstSaleFor($previousFrom, $previousTo);

        return $this->withComparison($current, $previous);
    }

    protected function medianTimeToFirstSaleFor(CarbonInterface $from, CarbonInterface $to): array
    {
        // First purchase per betslip, filtered to those whose first
        // purchase landed in the window.
        $rows = DB::table('betslips')
            ->join('betslip_user_purchases', 'betslip_user_purchases.betslip_id', '=', 'betslips.id')
            ->whereIn('betslip_user_purchases.status', self::PURCHASE_STATUSES)
            ->groupBy('betslips.id', 'betslips.created_at')
            ->select(
                'betslips.created_at as listed_at',
                DB::raw('MIN(betslip_user_purchases.purchased_at) as first_purchase_at'),
            )
            ->havingRaw('MIN(betslip_user_purchases.purchased_at) BETWEEN ? AND ?', [$from, $to])
            ->get();

        if ($rows->isEmpty()) {
            return ['value' => null, 'sample_size' => 0];
        }

        $hours = $rows
            ->map(fn ($row) => Carbon::parse($row->listed_at)
                ->diffInMinutes(Carbon::parse($row->first_purchase_at)) / 60)
            ->filter(fn ($h) => $h >= 0)
            ->sort()
            ->values();

        $count = $hours->count();

        if ($count === 0) {
            return ['value' => null, 'sample_size' => 0];
        }

        $median = $count % 2 === 0
            ? ($hours[$count / 2 - 1] + $hours[$count / 2]) / 2
            : $hours[intdiv($count, 2)];

        return [
            'value' => round($median, 1),
            'sample_size' => $count,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Returns [$currentFrom, $currentTo, $previousFrom, $previousTo].
     */
    protected function windows(int $days): array
    {
        $now = now();
        $currentFrom = $now->copy()->subDays($days);
        $previousFrom = $now->copy()->subDays($days * 2);
        $previousTo = $currentFrom->copy();

        return [$currentFrom, $now, $previousFrom, $previousTo];
    }

    /**
     * Merge the current and previous values into one payload with a
     * computed delta. `value` is always the current window's headline.
     */
    protected function withComparison(array $current, array $previous): array
    {
        $currentValue = $current['value'] ?? null;
        $previousValue = $previous['value'] ?? null;

        $delta = null;
        if ($currentValue !== null && $previousValue !== null) {
            $delta = round($currentValue - $previousValue, 1);
        }

        return [
            ...$current,
            'previous' => $previousValue,
            'delta' => $delta,
        ];
    }
}