<?php

namespace App\Observers;

use App\Exceptions\MatchdayLockedException;
use App\Models\ContestPick;

class ContestPickObserver
{
    /**
     * Fields a user can set when submitting or editing a pick. Anything
     * outside this set (`status`, `points`) is written by the scoring
     * service and must not be gated by the pick-lock policy.
     */
    private const USER_MANAGED_FIELDS = ['selection', 'odds_at_pick'];

    /**
     * Enforce the matchday lock on first write. Defense in depth: the
     * HTTP path checks first in ContestPickController@store, but a direct
     * service call that reaches ContestPick::create() must not slip past.
     */
    public function creating(ContestPick $pick): void
    {
        $this->enforceMatchdayLock($pick);

        // Jackpot picks: setting revealed_at is the atomic "this is now locked"
        // marker. Once set, the pick cannot be changed (see updating()).
        $entry = $pick->entry;
        if ($entry) {
            $contest = $entry->contest;
            if ($contest && $contest->type === 'jackpot' && $pick->revealed_at === null) {
                $pick->revealed_at = now();
            }
        }
    }
    /**
     * Host picks are locked the moment the contest is published. Nobody,
     * including the host, can edit them afterwards. Non-host picks remain
     * editable until the deadline.
     *
     * Only enforced when a user-managed field is actually changing.
     * Internal writes from ContestScoringService (status, points) skip
     * both this guard and the matchday lock check — otherwise the
     * scorer's own update would throw on a contest that has already
     * moved past 'open'.
     */
    public function updating(ContestPick $pick): void
    {
        if (!$pick->isDirty(self::USER_MANAGED_FIELDS)) {
            return;
        }

        $this->enforceMatchdayLock($pick);

        // Jackpot picks cannot be changed once revealed.
        if ($pick->revealed_at !== null) {
            $entry = $pick->entry;
            if ($entry && $entry->contest && $entry->contest->type === 'jackpot') {
                throw new \RuntimeException(
                    'Jackpot picks are locked once revealed. You cannot change your pick.'
                );
            }
        }

        $entry = $pick->entry;
        if (!$entry)
            return;

        $contest = $entry->contest;
        if (!$contest)
            return;

        if ((int) $entry->user_id === (int) $contest->host_id) {
            throw new \RuntimeException(
                'Host picks are locked once the contest is published.'
            );
        }
    }

    private function enforceMatchdayLock(ContestPick $pick): void
    {
        if (ContestPick::isMatchdayLockSkipped()) {
            return;
        }

        $entry = $pick->entry;
        if (!$entry) {
            return;
        }

        $contest = $entry->contest;
        if (!$contest || !$contest->is_matchday) {
            return;
        }

        if ($contest->locks_at === null) {
            return;
        }

        if (now()->lt($contest->locks_at)) {
            return;
        }

        throw new MatchdayLockedException($contest->locks_at);
    }
}