<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestPick;
use App\Models\SubLeague;
use App\Services\Matchday\MatchdayLeaderboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MatchdayDashboardController extends Controller
{
    /**
     * How many leaderboard rows to include per sub-league in the preview.
     * The full leaderboard lives on its own endpoint.
     */
    private const LEADERBOARD_PREVIEW_LIMIT = 5;

    public function __construct(
        protected MatchdayLeaderboardService $leaderboard,
    ) {
    }

    public function index(): Response
    {
        $user = Auth::user();
        $season = config('marketplace.matchday.seasons.0');

        $subLeagues = $this->subLeaguesFor($user, $season);

        $summary = $this->leaderboard->summaryForUser($user, $season);

        \Log::info($this->openMatchdaysFor($user, $season));
        return Inertia::render('Matchday/Dashboard', [
            'user' => $user,
            'notifications' => [ /* unchanged */],

            'season' => $season,
            'seasons' => config('marketplace.matchday.seasons', []),
            'league_options' => $this->leagueOptions(),

            'sub_leagues' => $subLeagues,
            'open_matchdays' => $this->openMatchdaysFor($user, $season),
            'summary' => $summary['matchdays_settled'] > 0 ? $summary : null,
            'unacknowledged_settlements' => $this->unacknowledgedSettlements($user, $season),
            'live_rows' => $this->leaderboard->liveForUser($user, $season),
            'form_guide' => $this->leaderboard->formGuideForUser($user, $season),
        ]);
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    private function leagueOptions(): array
    {
        $supported = (array) config('marketplace.matchday.supported_league_codes', []);
        $labels = (array) config('marketplace.matchday.league_labels', []);

        $out = [];
        foreach (array_keys($supported) as $code) {
            $out[] = [
                'code' => (string) $code,
                'label' => (string) ($labels[$code] ?? $code),
            ];
        }

        return $out;
    }

    /**
     * User's sub-leagues for the active season, each with a leaderboard
     * preview. Ordered by name for stable rendering.
     *
     * @return array<int, array<string, mixed>>
     */
    private function subLeaguesFor($user, string $season): array
    {
        $subLeagues = SubLeague::query()
            ->where('season', $season)
            ->whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->withCount('members')
            ->orderBy('name')
            ->get();

        return $subLeagues->map(function (SubLeague $sub) {
            $rows = $this->leaderboard->forSubLeague($sub);

            return [
                'id' => $sub->id,
                'name' => $sub->name,
                'invite_code' => $sub->invite_code,
                'member_count' => (int) $sub->members_count,
                'max_members' => (int) $sub->max_members,
                'is_owner' => (int) $sub->owner_id === (int) Auth::id(),
                'leaderboard_preview' => array_slice($rows, 0, self::LEADERBOARD_PREVIEW_LIMIT),
            ];
        })->values()->all();
    }
    /**
     * Every matchday contest in the season that hasn't settled or been
     * cancelled. Ordered by lock time so the soonest-locking contest is
     * first. Includes locked-but-not-settled contests (status 'locked' or
     * 'underway') so the user sees their live state.
     *
     * @return array<int, array<string, mixed>>
     */
    private function openMatchdaysFor($user, string $season): array
    {
        $contests = Contest::query()
            ->where('is_matchday', true)
            ->where('season', $season)
            ->whereNotIn('status', ['settled', 'cancelled'])
            ->orderBy('locks_at')
            ->with(['legs'])
            ->get();

        if ($contests->isEmpty()) {
            return [];
        }

        // One query for entries, one for pick counts, then assemble.
        $entries = ContestEntry::query()
            ->whereIn('contest_id', $contests->pluck('id'))
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->get()
            ->keyBy('contest_id');

        $pickCounts = ContestPick::query()
            ->whereIn('contest_entry_id', $entries->pluck('id'))
            ->selectRaw('contest_entry_id, COUNT(*) as c')
            ->groupBy('contest_entry_id')
            ->pluck('c', 'contest_entry_id');

        return $contests->map(function (Contest $contest) use ($entries, $pickCounts) {
            $entry = $entries->get($contest->id);
            $picksSubmitted = $entry ? (int) ($pickCounts[$entry->id] ?? 0) : 0;

            return [
                'uuid' => $contest->uuid,
                'name' => $contest->name,
                'matchday_number' => (int) $contest->matchday_number,
                'league_code' => $contest->league_code,
                'locks_at' => $contest->locks_at?->toISOString(),
                'is_locked' => $contest->locks_at !== null && now()->gte($contest->locks_at),
                'status' => $contest->status,
                'picks_required' => $contest->legs->count(),
                'picks_submitted' => $picksSubmitted,
                'is_entered' => $entry !== null,
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function unacknowledgedSettlements($user, string $season): array
    {
        $rows = \DB::table('contest_entries as ce')
            ->join('contests as c', 'c.id', '=', 'ce.contest_id')
            ->where('ce.user_id', $user->id)
            ->where('ce.status', 'accepted')
            ->whereNull('ce.dismissed_settlement_at')
            ->whereNotNull('ce.settled_at')
            ->where('c.is_matchday', true)
            ->where('c.season', $season)
            ->where('c.status', 'settled')
            ->orderByDesc('c.settled_at')
            ->limit(3)
            ->get([
                'ce.id as entry_id',
                'ce.score_correct',
                'ce.rank_current',
                'c.uuid as contest_uuid',
                'c.name as contest_name',
                'c.matchday_number',
                'c.league_code',
                'c.settled_at',
            ]);

        if ($rows->isEmpty()) {
            return [];
        }

        $contestUuids = $rows->pluck('contest_uuid')->all();

        // Total participants per contest — one query, plucked by uuid.
        $totals = \DB::table('contest_entries as ce')
            ->join('contests as c', 'c.id', '=', 'ce.contest_id')
            ->whereIn('c.uuid', $contestUuids)
            ->where('ce.status', 'accepted')
            ->selectRaw('c.uuid, COUNT(*) as total')
            ->groupBy('c.uuid')
            ->pluck('total', 'uuid');

        return $rows->map(fn($r) => [
            'entry_id' => (int) $r->entry_id,
            'uuid' => $r->contest_uuid,
            'name' => $r->contest_name,
            'matchday_number' => (int) $r->matchday_number,
            'league_code' => $r->league_code,
            'correct' => (int) $r->score_correct,
            'rank' => (int) $r->rank_current,
            'total_participants' => (int) ($totals[$r->contest_uuid] ?? 0),
            'settled_at' => \Carbon\Carbon::parse($r->settled_at)->toIso8601String(),
        ])->values()->all();
    }

    public function dismissSettlement(ContestEntry $entry): \Illuminate\Http\JsonResponse
    {
        abort_unless(
            (int) $entry->user_id === (int) auth()->id(),
            403,
            'This entry does not belong to you.'
        );

        abort_unless(
            $entry->settled_at !== null,
            422,
            'This entry has not settled yet.'
        );

        if ($entry->dismissed_settlement_at === null) {
            $entry->update(['dismissed_settlement_at' => now()]);
        }

        return response()->json(['data' => ['dismissed_settlement_at' => \Carbon\Carbon::parse($entry->dismissed_settlement_at)->toIso8601String()]]);
    }
}