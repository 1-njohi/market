<?php

namespace App\Services;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use Illuminate\Support\Collection;

class ContestService
{
    private const DASHBOARD_LIMIT = 5;

    /**
     * Every active contest the user is attached to, either as host or
     * player. Not used by the dashboards any more — each dashboard has a
     * role-specific method — but kept for callers that want the union.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeForUser(User $user, int $limit = self::DASHBOARD_LIMIT): array
    {
        return collect()
            ->concat($this->hostedForUser($user, $limit))
            ->concat($this->enteredForUser($user, $limit))
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Contests the user is hosting, in open or locked state.
     *
     * Each row carries `role => 'host'` and entry-count summaries.
     *
     * @return array<int, array<string, mixed>>
     */
    public function hostedForUser(User $user, int $limit = self::DASHBOARD_LIMIT): array
    {
        return Contest::where('host_id', $user->id)
            ->whereIn('status', ['open', 'locked'])
            ->withCount([
                'entries as pending_requests'  => fn ($q) => $q->where('status', 'pending'),
                'entries as accepted_entries'  => fn ($q) => $q->where('status', 'accepted'),
                'legs as legs_count',
            ])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(fn (Contest $c) => $this->presentHosted($c))
            ->values()
            ->all();
    }

    /**
     * Contests the user has entered as an accepted player. Excludes any
     * contest where the user is also the host (defensive — host-play in
     * their own contest isn't a supported scenario today).
     *
     * @return array<int, array<string, mixed>>
     */
    public function enteredForUser(User $user, int $limit = self::DASHBOARD_LIMIT): array
    {
        return Contest::whereHas('entries', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('status', 'accepted');
        })
            ->where('host_id', '!=', $user->id)
            ->whereIn('status', ['open', 'locked'])
            ->withCount(['legs as legs_count'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(fn (Contest $c) => $this->presentPlayed($c, $user))
            ->values()
            ->all();
    }

    private function presentHosted(Contest $c): array
    {
        return [
            'id'                 => $c->id,
            'uuid'               => $c->uuid,
            'name'               => $c->name,
            'status'             => $c->status,
            'role'               => 'host',
            'legs_count'         => (int) $c->legs_count,
            'accepted_entries'   => (int) $c->accepted_entries,
            'pending_requests'   => (int) $c->pending_requests,
            'picks_submitted'    => null,
            'entry_deadline_at'  => $c->entry_deadline_at->toISOString(),
            'created_at'         => $c->created_at->toISOString(),
        ];
    }

    private function presentPlayed(Contest $c, User $user): array
    {
        $entry = ContestEntry::where('contest_id', $c->id)
            ->where('user_id', $user->id)
            ->first();

        $picksSubmitted = $entry
            ? $entry->picks()->count()
            : 0;

        return [
            'id'                 => $c->id,
            'uuid'               => $c->uuid,
            'name'               => $c->name,
            'status'             => $c->status,
            'role'               => 'player',
            'legs_count'         => (int) $c->legs_count,
            'accepted_entries'   => null,
            'pending_requests'   => null,
            'picks_submitted'    => $picksSubmitted,
            'entry_deadline_at'  => $c->entry_deadline_at->toISOString(),
            'created_at'         => $c->created_at->toISOString(),
        ];
    }
}