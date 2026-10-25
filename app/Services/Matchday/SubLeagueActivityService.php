<?php

namespace App\Services\Matchday;

use App\Models\SubLeague;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubLeagueActivityService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function recent(SubLeague $subLeague, int $limit = 10): array
    {
        $memberIds = DB::table('sub_league_members')
            ->where('sub_league_id', $subLeague->id)
            ->pluck('user_id')
            ->all();

        if (empty($memberIds)) {
            return [];
        }

        $events = [];

        // ─── Joins ───
        $joins = DB::table('sub_league_members as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('m.sub_league_id', $subLeague->id)
            ->where('m.user_id', '!=', $subLeague->owner_id)   // owner joins at creation, noisy
            ->select('u.id as user_id', 'u.name', 'm.joined_at as at')
            ->get();

        foreach ($joins as $j) {
            $events[] = [
                'type' => 'join',
                'at' => Carbon::parse($j->at)->toISOString(),
                'actor_id' => (int) $j->user_id,
                'actor_name' => $j->name,
                'message' => "{$j->name} joined the crew",
            ];
        }

        // ─── Picks (only after their contest locked) ───
        // ─── Picks (only after their contest locked) ───
        $picks = DB::table('contest_picks as cp')
            ->join('contest_entries as ce', 'ce.id', '=', 'cp.contest_entry_id')
            ->join('contests as c', 'c.id', '=', 'ce.contest_id')
            ->join('users as u', 'u.id', '=', 'ce.user_id')
            ->join('contest_legs as cl', 'cl.id', '=', 'cp.contest_leg_id')
            ->join('fixtures as f', 'f.id', '=', 'cl.fixture_id')
            ->leftJoin('teams as ht', 'ht.id', '=', 'f.home_team_id')
            ->leftJoin('teams as at', 'at.id', '=', 'f.away_team_id')
            ->whereIn('ce.user_id', $memberIds)
            ->where('c.is_matchday', true)
            ->where('c.season', $subLeague->season)
            ->whereNotNull('c.locks_at')
            ->where('c.locks_at', '<', now())
            ->select(
                'u.id as user_id',
                'u.name',
                'c.name as contest_name',
                'c.matchday_number',
                'cp.created_at as at',
                'cp.selection',
                'ht.name as home_team',
                'at.name as away_team',
            )
            ->orderByDesc('cp.created_at')
            ->limit(50)
            ->get();

        foreach ($picks as $p) {
            $home = $p->home_team ?? 'Home';
            $away = $p->away_team ?? 'Away';

            $events[] = [
                'type' => 'pick',
                'at' => Carbon::parse($p->at)->toISOString(),
                'actor_id' => (int) $p->user_id,
                'actor_name' => $p->name,
                'matchday_number' => (int) $p->matchday_number,
                'contest_name' => $p->contest_name,
                'fixture' => "{$home} vs {$away}",
                'selection' => $p->selection,
                // Use contest name instead of MD1
                'message' => "{$p->name} picked {$p->selection} on {$home} vs {$away} for {$p->contest_name}",
            ];
        }

        // ─── Settlements (per-member, per-contest) ───
        // Find all settled contests in this season where at least one member participated.
        $settledContests = DB::table('contests as c')
            ->join('contest_entries as ce', 'c.id', '=', 'ce.contest_id')
            ->whereIn('ce.user_id', $memberIds)
            ->where('c.is_matchday', true)
            ->where('c.season', $subLeague->season)
            ->where('c.status', 'settled')
            ->select('c.id', 'c.name', 'c.settled_at')
            ->distinct()
            ->get();

        foreach ($settledContests as $sc) {
            // Find the top performer in this sub-league for this specific contest.
            $topMember = DB::table('contest_entries as ce')
                ->join('users as u', 'u.id', '=', 'ce.user_id')
                ->where('ce.contest_id', $sc->id)
                ->whereIn('ce.user_id', $memberIds)
                ->where('ce.status', 'accepted')
                ->orderByDesc('ce.score_correct')
                ->first();

            // Find the viewer's entry to show their specific rank in the message.
            $viewerEntry = DB::table('contest_entries as ce')
                ->where('ce.contest_id', $sc->id)
                ->where('ce.user_id', auth() -> user() -> id)
                ->first();

            $events[] = [
                'type' => 'settlement',
                'at' => Carbon::parse($sc->settled_at)->toISOString(),
                'contest_name' => $sc->name,
                'top_name' => $topMember?->name,
                'top_score' => (int) ($topMember?->score_correct ?? 0),
                'viewer_rank' => $viewerEntry ? (int) $viewerEntry->rank_current : null,
                'viewer_score' => $viewerEntry ? (int) $viewerEntry->score_correct : null,
                // Message format depending on if viewer participated
                'message' => $viewerEntry
                    ? "{$sc->name} settled · You finished #{$viewerEntry->rank_current} · Top: {$topMember?->name} ({$topMember?->score_correct} correct)"
                    : "{$sc->name} settled · Top: {$topMember?->name} ({$topMember?->score_correct} correct)",
            ];
        }

        usort($events, fn($a, $b) => strcmp($b['at'], $a['at']));

        return array_slice($events, 0, $limit);
    }
}