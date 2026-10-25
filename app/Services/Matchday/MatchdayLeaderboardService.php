<?php

namespace App\Services\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestPick;
use App\Models\SubLeague;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchdayLeaderboardService
{
    /**
     * @return array<int, array{
     *     user_id: int,
     *     name: string,
     *     points: int,
     *     matchdays_played: int,
     *     rank: int,
     * }>
     */
    public function forSubLeague(SubLeague $subLeague, int $fromMatchday = 1): array
    {
        $memberIds = DB::table('sub_league_members')
            ->where('sub_league_id', $subLeague->id)
            ->pluck('user_id')
            ->all();

        if (empty($memberIds)) {
            return [];
        }

        $query = $this->baseQuery($subLeague->season, $fromMatchday)
            ->whereIn('ce.user_id', $memberIds);

        // Scope to the sub-league's own league_codes. Empty means "all
        // supported" — backward compat with pre-league_codes rows.
        if (!empty($subLeague->league_codes)) {
            $query->whereIn('c.league_code', $subLeague->league_codes);
        }

        $rows = $query
            ->selectRaw('u.id as user_id, u.name as name, SUM(ce.score_correct) as points, COUNT(c.id) as matchdays_played')
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('points')
            ->orderBy('u.id')
            ->get();

        $joinedAt = DB::table('sub_league_members')
            ->where('sub_league_id', $subLeague->id)
            ->pluck('joined_at', 'user_id');

        $sorted = $rows->sortBy([
            fn($a, $b) => $b->points <=> $a->points,
            fn($a, $b) => strcmp(
                (string) ($joinedAt[$a->user_id] ?? ''),
                (string) ($joinedAt[$b->user_id] ?? ''),
            ),
        ])->values();

        return $this->withRanks($sorted->all());
    }
    /**
     * @return array<int, array{
     *     user_id: int,
     *     name: string,
     *     points: int,
     *     matchdays_played: int,
     *     rank: int,
     * }>
     */
    public function global(string $season, int $fromMatchday = 1): array
    {
        $rows = $this->baseQuery($season, $fromMatchday)
            ->selectRaw('u.id as user_id, u.name as name, SUM(ce.score_correct) as points, COUNT(c.id) as matchdays_played')
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('points')
            ->orderBy('u.id')
            ->get();

        return $this->withRanks($rows->all());
    }

    /**
     * @return array<int, int>  map of matchday_number => points earned
     */
    public function pointsByMatchdayForUser(User $user, string $season): array
    {
        $rows = DB::table('contest_entries as ce')
            ->join('contests as c', 'c.id', '=', 'ce.contest_id')
            ->where('ce.user_id', $user->id)
            ->where('c.is_matchday', true)
            ->where('c.season', $season)
            ->where('c.status', 'settled')
            ->where('ce.status', 'accepted')
            ->selectRaw('c.matchday_number as md, SUM(ce.score_correct) as points')
            ->groupBy('c.matchday_number')
            ->orderBy('c.matchday_number')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->md] = (int) $r->points;
        }

        return $out;
    }

    /**
     * Hook for a future ContestSettled listener. Currently a no-op because
     * MVP ships uncached. Kept so wiring a cache later is a one-line change.
     */
    public static function forget(?SubLeague $subLeague = null, ?string $season = null): void
    {
        // no-op until caching is enabled
    }

    /**
     * Shared query builder for the settled-matchday rows.
     */
    private function baseQuery(string $season, int $fromMatchday)
    {
        return DB::table('contest_entries as ce')
            ->join('contests as c', 'c.id', '=', 'ce.contest_id')
            ->join('users as u', 'u.id', '=', 'ce.user_id')
            ->where('c.is_matchday', true)
            ->where('c.season', $season)
            ->where('c.status', 'settled')
            ->where('c.matchday_number', '>=', $fromMatchday)
            ->where('ce.status', 'accepted');
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function withRanks(array $rows): array
    {
        $out = [];
        $rank = 1;

        foreach ($rows as $r) {
            $out[] = [
                'user_id' => (int) $r->user_id,
                'name' => (string) $r->name,
                'points' => (int) $r->points,
                'matchdays_played' => (int) $r->matchdays_played,
                'rank' => $rank++,
            ];
        }

        return $out;
    }
    /**
     * Aggregate season summary for the dashboard card. Null-coalesced fields
     * mean "no data yet, don't show the tile" — the UI renders the seller-style
     * SampleMetricCard placeholder in that case.
     *
     * @return array{
     *     pick_accuracy: float|null,
     *     points_per_matchday: float|null,
     *     matchdays_played: int,
     *     picks_submitted: int,
     *     avg_picks_per_matchday: float|null,
     *     completion_pct: float|null,
     *     current_streak: int,
     *     matchdays_settled: int,
     * }
     */
    public function summaryForUser(User $user, string $season): array
    {
        // Every settled matchday entry this user has in the season, with
        // the required leg count alongside it. One row per (contest, entry).
        $rows = DB::table('contest_entries as ce')
            ->join('contests as c', 'c.id', '=', 'ce.contest_id')
            ->where('ce.user_id', $user->id)
            ->where('c.is_matchday', true)
            ->where('c.season', $season)
            ->where('c.status', 'settled')
            ->where('ce.status', 'accepted')
            ->orderBy('c.matchday_number')
            ->get([
                'ce.id as entry_id',
                'c.id as contest_id',
                'c.matchday_number',
                'ce.score_correct',
            ]);

        $matchdaysSettled = $rows->count();
        $matchdaysPlayed = $matchdaysSettled;   // only settled contests count

        if ($matchdaysSettled === 0) {
            return [
                'pick_accuracy' => null,
                'points_per_matchday' => null,
                'matchdays_played' => 0,
                'picks_submitted' => 0,
                'avg_picks_per_matchday' => null,
                'completion_pct' => null,
                'current_streak' => 0,
                'matchdays_settled' => 0,
            ];
        }

        // Total picks and total correct picks across those entries.
        $entryIds = $rows->pluck('entry_id')->all();

        $pickStats = DB::table('contest_picks')
            ->whereIn('contest_entry_id', $entryIds)
            ->selectRaw('COUNT(*) as submitted, SUM(CASE WHEN status = \'correct\' THEN 1 ELSE 0 END) as correct')
            ->first();

        $picksSubmitted = (int) ($pickStats->submitted ?? 0);
        $picksCorrect = (int) ($pickStats->correct ?? 0);

        // Required leg count per contest, keyed by contest_id, so
        // completion_pct has a denominator across the season.
        $contestIds = $rows->pluck('contest_id')->all();
        $legsRequired = DB::table('contest_legs')
            ->whereIn('contest_id', $contestIds)
            ->groupBy('contest_id')
            ->pluck(DB::raw('COUNT(*)'), 'contest_id');
        $totalLegsRequired = (int) $legsRequired->sum();

        // Points per matchday: sum of score_correct ÷ matchdays settled.
        $totalPoints = (int) $rows->sum('score_correct');

        // Current streak: walk settled matchdays in descending order and
        // count consecutive ones with score_correct > 0.
        $streak = 0;
        foreach ($rows->sortByDesc('matchday_number') as $row) {
            if ((int) $row->score_correct > 0) {
                $streak++;
            } else {
                break;
            }
        }

        return [
            'pick_accuracy' => $picksSubmitted > 0
                ? round($picksCorrect / $picksSubmitted * 100, 1)
                : null,
            'points_per_matchday' => round($totalPoints / $matchdaysSettled, 2),
            'matchdays_played' => $matchdaysPlayed,
            'picks_submitted' => $picksSubmitted,
            'avg_picks_per_matchday' => round($picksSubmitted / $matchdaysSettled, 2),
            'completion_pct' => $totalLegsRequired > 0
                ? round($picksSubmitted / $totalLegsRequired * 100, 1)
                : null,
            'current_streak' => $streak,
            'matchdays_settled' => $matchdaysSettled,
        ];
    }

    /**
     * Every matchday contest relevant to a sub-league: upcoming (open,
     * locking soon) first, then recently settled. Filtered by the
     * sub-league's league_codes. Ordered by is_upcoming DESC, then
     * locks_at ASC for upcoming, settled_at DESC for settled.
     *
     * @return array<int, array<string, mixed>>
     */
    public function matchdaysForSubLeague(SubLeague $subLeague, User $viewer): array
    {
        $query = Contest::query()
            ->where('is_matchday', true)
            ->where('season', $subLeague->season)
            ->whereIn('status', ['open', 'locked', 'underway', 'settled'])
            ->with(['legs']);

        if (!empty($subLeague->league_codes)) {
            $query->whereIn('league_code', $subLeague->league_codes);
        }

        $contests = $query->get();

        // Entry count lookup for the viewer.
        $entries = ContestEntry::query()
            ->whereIn('contest_id', $contests->pluck('id'))
            ->where('user_id', $viewer->id)
            ->where('status', 'accepted')
            ->get()
            ->keyBy('contest_id');

        $pickCounts = ContestPick::query()
            ->whereIn('contest_entry_id', $entries->pluck('id'))
            ->selectRaw('contest_entry_id, COUNT(*) as c')
            ->groupBy('contest_entry_id')
            ->pluck('c', 'contest_entry_id');

        $rows = $contests->map(function (Contest $contest) use ($entries, $pickCounts) {
            $entry = $entries->get($contest->id);
            $picksSubmitted = $entry ? (int) ($pickCounts[$entry->id] ?? 0) : 0;

            return [
                'uuid' => $contest->uuid,
                'name' => $contest->name,
                'matchday_number' => (int) $contest->matchday_number,
                'league_code' => $contest->league_code,
                'status' => $contest->status,
                'locks_at' => $contest->locks_at?->toISOString(),
                'settled_at' => $contest->settled_at?->toISOString(),
                'is_locked' => $contest->locks_at !== null && now()->gte($contest->locks_at),
                'is_upcoming' => in_array($contest->status, ['open', 'locked', 'underway'], true),
                'is_entered' => $entry !== null,
                'picks_required' => $contest->legs->count(),
                'picks_submitted' => $picksSubmitted,
            ];
        });

        return $rows
            ->sortBy([
                fn($a, $b) => $a['is_upcoming'] === $b['is_upcoming']
                    ? 0
                    : ($a['is_upcoming'] ? -1 : 1),
                fn($a, $b) => $a['is_upcoming']
                    ? strcmp((string) $a['locks_at'], (string) $b['locks_at'])
                    : strcmp((string) $b['settled_at'], (string) $a['settled_at']),
            ])
            ->values()
            ->all();
    }
    /**
     * Per-active-matchday live tally for a user. Active means the contest
     * is open or underway (not settled, cancelled, or void) and the user
     * has an accepted entry.
     *
     * @return array<int, array<string, mixed>>
     */
    public function liveForUser(User $user, string $season): array
    {
        $entries = DB::table('contest_entries as ce')
            ->join('contests as c', 'c.id', '=', 'ce.contest_id')
            ->where('ce.user_id', $user->id)
            ->where('ce.status', 'accepted')
            ->where('c.is_matchday', true)
            ->where('c.season', $season)
            ->whereIn('c.status', ['open', 'locked', 'underway'])
            ->orderBy('c.locks_at')
            ->get([
                'ce.id as entry_id',
                'ce.rank_current',
                'ce.rank_previous',
                'c.id as contest_id',
                'c.uuid as contest_uuid',
                'c.name as contest_name',
                'c.matchday_number',
                'c.league_code',
                'c.status as contest_status',
                'c.locks_at',
            ]);

        if ($entries->isEmpty()) {
            return [];
        }

        $contestIds = $entries->pluck('contest_id')->all();

        // Leg counts grouped by contest.
        $legTotals = DB::table('contest_legs')
            ->whereIn('contest_id', $contestIds)
            ->selectRaw('contest_id, COUNT(*) as total, SUM(CASE WHEN status <> \'pending\' THEN 1 ELSE 0 END) as resolved')
            ->groupBy('contest_id')
            ->get()
            ->keyBy('contest_id');

        // Pick status counts grouped by entry.
        $pickCounts = DB::table('contest_picks')
            ->whereIn('contest_entry_id', $entries->pluck('entry_id'))
            ->selectRaw("
            contest_entry_id,
            SUM(CASE WHEN status = 'correct'   THEN 1 ELSE 0 END) as correct,
            SUM(CASE WHEN status = 'incorrect' THEN 1 ELSE 0 END) as incorrect,
            SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending
        ")
            ->groupBy('contest_entry_id')
            ->get()
            ->keyBy('contest_entry_id');

        return $entries->map(function ($e) use ($legTotals, $pickCounts) {
            $legs = $legTotals->get($e->contest_id);
            $picks = $pickCounts->get($e->entry_id);

            $correct = (int) ($picks->correct ?? 0);
            $incorrect = (int) ($picks->incorrect ?? 0);
            $pending = (int) ($picks->pending ?? 0);

            // Positive delta = moved up. rank_previous might be null on the
            // first scoring pass; treat that as "no change" for the UI.
            $current = (int) ($e->rank_current ?? 0);
            $previous = (int) ($e->rank_previous ?? 0);
            $delta = ($current > 0 && $previous > 0) ? ($previous - $current) : 0;

            $locksAt = \Carbon\Carbon::parse($e->locks_at);
            $isLocked = now()->gte($locksAt);

            return [
                'uuid' => $e->contest_uuid,
                'name' => $e->contest_name,
                'matchday_number' => (int) $e->matchday_number,
                'league_code' => $e->league_code,
                'locks_at' => $locksAt->toISOString(),
                'is_locked' => $isLocked,
                'status' => $e->contest_status,
                'legs_total' => (int) ($legs->total ?? 0),
                'legs_resolved' => (int) ($legs->resolved ?? 0),
                'correct' => $correct,
                'incorrect' => $incorrect,
                'pending' => $pending,
                'rank_current' => $current ?: null,
                'rank_previous' => $previous ?: null,
                'rank_delta' => $delta,
            ];
        })->values()->all();
    }
    /**
     * @return array<int, array{
     *     matchday_number: int,
     *     score_correct: int,
     *     quartile: string,
     *     entered: bool
     * }>
     */
    public function formGuideForUser(User $user, string $season): array
    {
        // Get the last 5 settled matchdays for the season.
        $contests = Contest::where('is_matchday', true)
            ->where('season', $season)
            ->where('status', 'settled')
            ->orderByDesc('matchday_number')
            ->limit(5)
            ->get();

        if ($contests->isEmpty()) {
            return [];
        }

        $contestIds = $contests->pluck('id')->all();

        // Get every entry for these contests to calculate quartiles.
        // We need all entries, not just the user's, to find the quartile rank.
        $allEntries = DB::table('contest_entries')
            ->whereIn('contest_id', $contestIds)
            ->where('status', 'accepted')
            ->select('contest_id', 'user_id', 'score_correct')
            ->get()
            ->groupBy('contest_id');

        // Get the user's entries for these contests.
        $userEntries = DB::table('contest_entries')
            ->whereIn('contest_id', $contestIds)
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->pluck('score_correct', 'contest_id');

        $guide = [];

        foreach ($contests as $contest) {
            $entries = $allEntries->get($contest->id, collect());
            $score = $userEntries->get($contest->id);

            if ($score === null) {
                $guide[] = [
                    'matchday_number' => (int) $contest->matchday_number,
                    'score_correct' => 0,
                    'quartile' => 'none',
                    'entered' => false,
                ];
                continue;
            }

            $scores = $entries->pluck('score_correct')->sortDesc()->values()->all();
            $count = count($scores);

            // Find the user's score in the descending array. Index 0 is highest,
            $index = array_search((int) $score, $scores);

            if ($index === false)
                $index = 0;

            $percentile = $count > 1 ? ($index / ($count - 1)) : 0;

            if ($percentile <= 0.25) {
                $quartile = 'green';
            } elseif ($percentile >= 0.75) {
                $quartile = 'red';
            } else {
                $quartile = 'yellow';
            }

            $guide[] = [
                'matchday_number' => (int) $contest->matchday_number,
                'score_correct' => (int) $score,
                'quartile' => $quartile,
                'entered' => true,
            ];
        }

        return $guide;
    }
}