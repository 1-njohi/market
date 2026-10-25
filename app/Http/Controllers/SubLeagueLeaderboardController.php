<?php

namespace App\Http\Controllers;

use App\Models\SubLeague;
use App\Models\Contest;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\SubLeagueMember;
use App\Services\Matchday\MatchdayLeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubLeagueLeaderboardController extends Controller
{
    public function __construct(
        protected MatchdayLeaderboardService $leaderboard,
    ) {
    }

    public function show(Request $request, SubLeague $subLeague): JsonResponse
    {
        $user = Auth::user();

        $isMember = SubLeagueMember::where('sub_league_id', $subLeague->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember, 403, 'You are not a member of this sub-league.');

        $data = $request->validate([
            'from_matchday' => ['sometimes', 'integer', 'min:1'],
        ]);

        $rows = $this->leaderboard->forSubLeague(
            $subLeague,
            (int) ($data['from_matchday'] ?? 1),
        );

        return response()->json([
            'data' => $rows,
            'season' => $subLeague->season,
        ]);
    }

    /**
     * Inertia-rendered leaderboard page. Same data as the JSON endpoint,
     * plus the global view and a max-matchday hint for the filter.
     *
     * The JSON `show()` method is untouched so nothing that consumes the
     * API breaks.
     */
    public function page(Request $request, SubLeague $subLeague): Response
    {
        $user = Auth::user();

        $isMember = SubLeagueMember::where('sub_league_id', $subLeague->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember, 403, 'You are not a member of this sub-league.');

        $data = $request->validate([
            'from_matchday' => ['sometimes', 'integer', 'min:1'],
        ]);

        $fromMatchday = (int) ($data['from_matchday'] ?? 1);

        $maxMatchday = (int) Contest::where('is_matchday', true)
            ->where('season', $subLeague->season)
            ->where('status', 'settled')
            ->max('matchday_number');

        return Inertia::render('Matchday/SubLeagueLeaderboard', [
            'sub_league' => [
                'id' => $subLeague->id,
                'name' => $subLeague->name,
                'season' => $subLeague->season,
            ],
            'from_matchday' => $fromMatchday,
            'max_matchday' => max(1, $maxMatchday),
            'sub_league_leaderboard' => $this->leaderboard->forSubLeague($subLeague, $fromMatchday),
            'global_leaderboard' => $this->leaderboard->global($subLeague->season, $fromMatchday),
        ]);
    }
    public function detail(Request $request, SubLeague $subLeague): Response
    {
        $user = Auth::user();

        $isMember = SubLeagueMember::query()
            ->where('sub_league_id', $subLeague->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember, 403, 'You are not a member of this sub-league.');

        $data = $request->validate([
            'from_matchday' => ['sometimes', 'integer', 'min:1'],
        ]);

        $fromMatchday = (int) ($data['from_matchday'] ?? 1);

        $maxMatchday = (int) Contest::where('is_matchday', true)
            ->where('season', $subLeague->season)
            ->where('status', 'settled')
            ->max('matchday_number');

        // Ordered members list: owner first, then by joined_at.
        $memberRows = SubLeagueMember::query()
            ->where('sub_league_id', $subLeague->id)
            ->with('user')
            ->orderByRaw("CASE WHEN user_id = ? THEN 0 ELSE 1 END", [$subLeague->owner_id])
            ->orderBy('joined_at')
            ->get()
            ->map(fn(SubLeagueMember $m) => [
                'user_id' => $m->user_id,
                'name' => $m->user?->name ?? '(deleted)',
                'code' => $m->user?->code,
                'is_owner' => (int) $m->user_id === (int) $subLeague->owner_id,
                'joined_at' => $m->joined_at?->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('Matchday/SubLeagueDetail', [
            'sub_league' => [
                'id' => $subLeague->id,
                'name' => $subLeague->name,
                'season' => $subLeague->season,
                'invite_code' => $subLeague->invite_code,
                'max_members' => (int) $subLeague->max_members,
                'member_count' => count($memberRows),
                'is_owner' => (int) $subLeague->owner_id === (int) $user->id,
                'league_codes' => $subLeague->league_codes ?? [],
            ],
            'from_matchday' => $fromMatchday,
            'max_matchday' => max(1, $maxMatchday),
            'members' => $memberRows,
            'matchdays' => $this->leaderboard->matchdaysForSubLeague($subLeague, $user),
            'sub_league_leaderboard' => $this->leaderboard->forSubLeague($subLeague, $fromMatchday),
            'global_leaderboard' => $this->leaderboard->global($subLeague->season, $fromMatchday),
            'activity' => app(\App\Services\Matchday\SubLeagueActivityService::class)->recent($subLeague),
        ]);
    }
}