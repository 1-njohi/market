<?php

namespace App\Services\Matchday;

use App\Exceptions\InvalidInviteCodeException;
use App\Exceptions\SubLeagueFullException;
use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubLeagueService
{
    /**
     * Create a sub-league and add the owner as its first member.
     */
    public function create(
        User $owner,
        string $name,
        string $season,
        array $leagueCodes = [],
    ): SubLeague {
        $cap = (int) config('marketplace.matchday.max_sub_league_members');

        // If the caller didn't pick leagues, default to all supported
        // ones. This preserves the pre-league_codes behaviour for tests
        // and callers that don't care.
        if (empty($leagueCodes)) {
            $leagueCodes = array_keys(
                (array) config('marketplace.matchday.supported_league_codes', [])
            );
        }

        return DB::transaction(function () use ($owner, $name, $season, $cap, $leagueCodes) {
            $sub = SubLeague::create([
                'season' => $season,
                'league_codes' => array_values(array_unique($leagueCodes)),
                'name' => $name,
                'owner_id' => $owner->id,
                'invite_code' => $this->generateInviteCode(),
                'max_members' => $cap,
            ]);

            SubLeagueMember::create([
                'sub_league_id' => $sub->id,
                'user_id' => $owner->id,
                'joined_at' => now(),
            ]);

            return $sub;
        });
    }

    /**
     * Join a sub-league by invite code.
     *
     * @throws InvalidInviteCodeException  when the code doesn't match a row
     * @throws SubLeagueFullException      when the sub-league is at cap
     * @throws ValidationException         when the user is already a member
     */
    public function join(User $user, string $inviteCode): SubLeague
    {
        return DB::transaction(function () use ($user, $inviteCode) {
            $sub = SubLeague::where('invite_code', $inviteCode)->lockForUpdate()->first();

            if (!$sub) {
                throw new InvalidInviteCodeException('No sub-league matches that invite code.');
            }

            $alreadyMember = SubLeagueMember::where('sub_league_id', $sub->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyMember) {
                throw ValidationException::withMessages([
                    'invite_code' => 'You are already a member of this sub-league.',
                ]);
            }

            if ($this->isFull($sub)) {
                throw new SubLeagueFullException('This sub-league has reached its member cap.');
            }

            SubLeagueMember::create([
                'sub_league_id' => $sub->id,
                'user_id' => $user->id,
                'joined_at' => now(),
            ]);

            return $sub;
        });
    }

    public function isFull(SubLeague $subLeague): bool
    {
        return $subLeague->members()->count() >= $subLeague->max_members;
    }

    protected function generateInviteCode(): string
    {
        do {
            $candidate = $this->randomString(12);
        } while (SubLeague::where('invite_code', $candidate)->exists());

        return $candidate;
    }

    /**
     * Overridable seam so tests can force a specific candidate.
     */
    protected function randomString(int $length): string
    {
        return strtoupper(\Illuminate\Support\Str::random($length));
    }
}