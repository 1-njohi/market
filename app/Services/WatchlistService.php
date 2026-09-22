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
}