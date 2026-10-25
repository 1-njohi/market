<?php

namespace App\Services\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\SubLeagueMember;
use App\Models\User;
use App\Notifications\Matchday\MatchdayCreatedNotification;
use Illuminate\Support\Facades\DB;

class MatchdayEntryService
{
    /**
     * Enter every distinct user who is a member of a sub-league in the
     * contest's season AND whose sub-league covers the contest's league.
     * Idempotent via firstOrCreate — rerunning creates nothing. Returns
     * the count of entries actually created, not the total number of
     * eligible users.
     */
    public function enterAllEligibleUsers(Contest $contest): int
    {
        if (! $contest->is_matchday) {
            return 0;
        }

        // Sub-leagues in the same season whose league_codes include (or
        // are null, meaning "all supported") this contest's league.
        // league_codes is stored as JSON via Eloquent's array cast, so a
        // substring match on the quoted code is portable across SQLite
        // and MySQL without needing JSON_CONTAINS / json_each.
        $userIds = SubLeagueMember::query()
            ->join('sub_leagues', 'sub_leagues.id', '=', 'sub_league_members.sub_league_id')
            ->where('sub_leagues.season', $contest->season)
            ->where(function ($q) use ($contest) {
                $q->whereNull('sub_leagues.league_codes')
                    ->orWhere('sub_leagues.league_codes', 'like', '%"' . $contest->league_code . '"%');
            })
            ->distinct()
            ->pluck('sub_league_members.user_id')
            ->all();

        if (empty($userIds)) {
            return 0;
        }

        $created = 0;

        DB::transaction(function () use ($userIds, $contest, &$created) {
            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                $entry = $this->createEntryFor($user, $contest);
                if ($entry->wasRecentlyCreated) {
                    $created++;
                }
            }
        });

        return $created;
    }

    /**
     * Called from the sub-league join action. Ensures the user has an
     * entry in the given contest if it's matchday, in the same season,
     * hasn't locked yet, and the sub-league covers the contest's league.
     * No-op otherwise.
     */
    public function enterUserIfEligible(User $user, Contest $contest): ?ContestEntry
    {
        if (! $contest->is_matchday) {
            return null;
        }

        if ($contest->locks_at === null || now()->gte($contest->locks_at)) {
            return null;
        }

        // The caller filters by sub-league via coversLeague(), but we
        // re-check here so a direct service call cannot bypass that.
        if (! $this->userCoversContest($user, $contest)) {
            return null;
        }

        return DB::transaction(
            fn () => $this->createEntryFor($user, $contest)
        );
    }

    /**
     * Protected seam — lets tests force failures or inspect call counts.
     */
    protected function createEntryFor(User $user, Contest $contest): ContestEntry
    {
        $entry = ContestEntry::firstOrCreate(
            [
                'contest_id' => $contest->id,
                'user_id'    => $user->id,
            ],
            [
                'status'    => 'accepted',
                'joined_at' => now(),
            ],
        );

        if ($entry->wasRecentlyCreated) {
            $user->notify(new MatchdayCreatedNotification($contest));
        }

        return $entry;
    }

    /**
     * True when at least one of the user's sub-leagues in this contest's
     * season covers the contest's league. Used by enterUserIfEligible to
     * guard the join path independently of the caller.
     */
    private function userCoversContest(User $user, Contest $contest): bool
    {
        $subLeagues = \App\Models\SubLeague::query()
            ->where('season', $contest->season)
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->get();

        foreach ($subLeagues as $sub) {
            if ($sub->coversLeague((string) $contest->league_code)) {
                return true;
            }
        }

        return false;
    }
}
