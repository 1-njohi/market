<?php

namespace App\Services;

use App\Exceptions\WatchlistException;
use App\Models\Betslip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WatchlistService
{
    /**
     * Maximum number of *active* watches a user can hold at once.
     * Settled and voided betslips remain in the watchlist as history
     * but don't occupy a slot.
     */
    public const MAX_ACTIVE_WATCHES = 20;

    /**
     * Watch a betslip on behalf of a user.
     *
     * Idempotent — calling twice for the same (user, betslip) pair is a
     * no-op the second time. Returns true on success, throws on any
     * guardrail rejection.
     *
     * @throws WatchlistException
     */
    public function watch(User $user, Betslip $betslip): bool
    {
        if ((int) $user->id === (int) $betslip->user_id) {
            throw new WatchlistException('You cannot watch your own betslip.');
        }

        if (in_array($betslip->status, ['settled', 'voided'], true)) {
            throw new WatchlistException('This betslip has already settled.');
        }

        if ($betslip->isPurchasedBy($user)) {
            throw new WatchlistException('You already own this betslip.');
        }

        // Idempotent path: already watching is not a rejection, and it
        // skips the cap check so a retried call can't accidentally fail.
        if ($this->isWatching($user, $betslip)) {
            return true;
        }

        return DB::transaction(function () use ($user, $betslip) {
            if ($this->activeWatchCount($user) >= self::MAX_ACTIVE_WATCHES) {
                throw new WatchlistException(
                    'Your watchlist is full. Remove a few slips to make room.'
                );
            }

            // syncWithoutDetaching is idempotent — if the row already
            // exists (race against another request), it's left alone.
            $user->watchedBetslips()->syncWithoutDetaching([
                $betslip->id => ['watched_at' => now()],
            ]);

            return true;
        });
    }

    /**
     * Unwatch a betslip on behalf of a user. Idempotent.
     */
    public function unwatch(User $user, Betslip $betslip): bool
    {
        $user->watchedBetslips()->detach($betslip->id);

        return true;
    }

    /**
     * Is this user watching this betslip?
     */
    public function isWatching(User $user, Betslip $betslip): bool
    {
        return $user->watchedBetslips()
            ->where('betslips.id', $betslip->id)
            ->exists();
    }

    /**
     * How many active (unresolved) betslips is this user watching?
     */
    public function activeWatchCount(User $user): int
    {
        return $user->watchedBetslips()
            ->whereIn('betslips.status', ['pending', 'underway'])
            ->count();
    }

    /**
     * Non-throwing check: can this user watch this betslip?
     *
     * The same rules that watch() enforces, but as a boolean. Used by
     * controllers that render payloads so the UI can hide the button
     * without needing to attempt the request.
     */
    public function canWatch(User $user, Betslip $betslip): bool
    {
        if ((int) $user->id === (int) $betslip->user_id) {
            return false;
        }

        if (in_array($betslip->status, ['settled', 'voided'], true)) {
            return false;
        }

        if ($betslip->isPurchasedBy($user)) {
            return false;
        }

        return true;
    }

    /**
     * The user's personal paper-trade record for watched betslips.
     *
     * Watched slips that have reached a terminal state contribute to the
     * record. Each slip counts as a flat 1u stake at its listed total_odds:
     *   - won     → +(total_odds - 1)
     *   - lost    → -1
     *   - voided  →  0
     *
     * The per-seller breakdown only includes sellers with at least 2 settled
     * watched slips, sorted by units descending. Sellers with a single
     * settled slip are shown in the aggregate but not broken out — one data
     * point isn't a pattern.
     */
    public function getWatchRecord(User $user): array
    {
        $settled = $user->watchedBetslips()
            ->whereIn('betslips.status', ['settled', 'voided'])
            ->with('seller:id,name,code')
            ->get();

        $won = 0;
        $lost = 0;
        $voided = 0;
        $units = 0.0;
        $bySeller = [];

        foreach ($settled as $betslip) {
            $outcome = $betslip->status === 'voided'
                ? 'voided'
                : ($betslip->is_winner ? 'won' : 'lost');

            $odds = (float) $betslip->total_odds;
            $delta = match ($outcome) {
                'won' => $odds - 1.0,
                'lost' => -1.0,
                'voided' => 0.0,
            };

            $units += $delta;

            if ($outcome === 'won') {
                $won++;
            } elseif ($outcome === 'lost') {
                $lost++;
            } else {
                $voided++;
            }

            $sellerId = (int) $betslip->user_id;

            if (!isset($bySeller[$sellerId])) {
                $bySeller[$sellerId] = [
                    'seller_id' => $sellerId,
                    'seller_name' => $betslip->seller->name ?? 'Unknown',
                    'seller_code' => $betslip->seller->code ?? null,
                    'settled_count' => 0,
                    'won_count' => 0,
                    'lost_count' => 0,
                    'voided_count' => 0,
                    'units' => 0.0,
                ];
            }

            $bySeller[$sellerId]['settled_count']++;
            $bySeller[$sellerId]['units'] += $delta;

            if ($outcome === 'won') {
                $bySeller[$sellerId]['won_count']++;
            } elseif ($outcome === 'lost') {
                $bySeller[$sellerId]['lost_count']++;
            } else {
                $bySeller[$sellerId]['voided_count']++;
            }
        }

        $sellers = collect($bySeller)
            ->filter(fn($s) => $s['settled_count'] >= 2)
            ->sortByDesc('units')
            ->values()
            ->map(function ($s) {
                $s['units'] = round($s['units'], 2);
                return $s;
            })
            ->all();

        return [
            'settled_count' => $settled->count(),
            'won_count' => $won,
            'lost_count' => $lost,
            'voided_count' => $voided,
            'units' => round($units, 2),
            'has_enough_data' => $settled->count() >= 3,
            'sellers' => $sellers,
        ];
    }
}