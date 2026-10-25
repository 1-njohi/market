<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContestResultsController extends Controller
{
    private const TOP_LIMIT = 10;

    public function show(string $uuid): Response|RedirectResponse
    {
        $contest = Contest::with([
            'host:id,name,code',
            'legs.fixture.homeTeam',
            'legs.fixture.awayTeam',
            'legs.market',
            'legs.fixture',
        ])->where('uuid', $uuid)->firstOrFail();

        // Results are visible from underway onward. open/locked/cancelled
        // bounce back to the join page so users see the contest context.
        if (!in_array($contest->status, ['underway', 'settled'], true)) {
            return redirect("/contests/join/{$contest->uuid}");
        }

        $legStats = $this->legStats($contest->id);

        $topEntries = $this->rankedEntries($contest->id)
            ->limit(self::TOP_LIMIT)
            ->get();

        $viewer = auth()->user();
        $viewerInTop = false;
        $viewerEntry = null;

        if ($viewer) {
            $viewerInTop = $topEntries->contains(
                fn($e) => (int) $e->user_id === (int) $viewer->id
            );

            // Only fetch the viewer's row separately when they're not
            // already in the top slice — avoids a duplicate entry in the
            // frontend list.
            if (!$viewerInTop) {
                $viewerEntry = $this->rankedEntries($contest->id)
                    ->where('user_id', $viewer->id)
                    ->first();
            }
        }

        $totalEntries = ContestEntry::where('contest_id', $contest->id)
            ->where('status', 'accepted')
            ->count();

        return Inertia::render('Contests/Results', [
            'contest' => $this->contestPayload($contest, $legStats),
            'standings' => $topEntries->map(fn($e) => $this->entryPayload($e))->values()->all(),
            'viewer_entry' => $viewerEntry ? $this->entryPayload($viewerEntry) : null,
            'viewer_in_top' => $viewerInTop,
            'total_entries' => $totalEntries,
        ]);
    }

    public function search(Request $request, string $uuid)
    {
        $contest = Contest::where('uuid', $uuid)->firstOrFail();

        $code = $request->query('code');

        $entry = ContestEntry::where('contest_id', $contest->id)
            ->where('status', 'accepted')
            ->whereHas('user', fn($q) => $q->where('code', $code))
            ->first();

        if (!$entry) {
            return response()->json(['entry' => null]);
        }

        return response()->json([
            'entry' => $this->entryPayload($entry)
        ]);
    }

    /**
     * Map a single contest entry to the frontend payload.
     * Includes reputation points and global rank for the viewer badges.
     */
    private function entryPayload(ContestEntry $entry): array
    {
        $user = $entry->user;

        return [
            'user_id' => (int) $entry->user_id,
            'name' => $user->name,
            'code' => $user->code,
            'avatar' => $user->profile_picture_url
                ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
            'is_verified' => !is_null($user->email_verified_at),
            'rank' => $entry->rank_current,
            'rank_previous' => $entry->rank_previous,
            'correct' => (int) $entry->score_correct,
            'units' => (float) $entry->score_units,
            'joined_at' => $entry->joined_at->toISOString(),
            'reputation_points' => (int) $user->prediction_points,
            'global_rank' => $user->prediction_points > 0
                ? User::where('prediction_points', '>', $user->prediction_points)->count() + 1
                : null,
            'picks' => $entry->picks->map(fn($p) => [
                'leg_id' => $p->contest_leg_id,
                'selection' => $p->selection,
                'odds' => (float) $p->odds_at_pick,
                'result' => match ($p->status) {
                    'correct' => 'correct',
                    'incorrect' => 'incorrect',
                    'void' => 'void',
                    default => 'pending',
                },
            ])->values()->all(),
        ];
    }

    private function contestPayload(Contest $contest, array $legStats): array
    {
        return [
            'uuid' => $contest->uuid,
            'name' => $contest->name,
            'status' => $contest->status,
            'settled_at' => $contest->settled_at?->toISOString(),
            'host_name' => $contest->host?->name ?? 'Unknown',
            'legs' => $contest->legs->map(fn($leg) => [
                'id' => $leg->id,
                'market' => $leg->market?->name ?? 'Unknown',
                'status' => $leg->status,
                'result_selection' => $leg->result_selections ? implode(', ', (array) $leg->result_selections) : null,
                'stats' => $legStats[$leg->id] ?? ['total_picks' => 0, 'correct_picks' => 0],
                'fixture' => [
                    'id' => $leg->fixture?->id,
                    'home_team' => $leg->fixture?->homeTeam?->name ?? 'Unknown',
                    'away_team' => $leg->fixture?->awayTeam?->name ?? 'Unknown',
                    'status' => $leg->fixture?->status_short ?? 'NS',
                ],
            ])->values()->all(),
        ];
    }

    private function rankedEntries(int $contestId)
    {
        return ContestEntry::where('contest_id', $contestId)
            ->where('status', 'accepted')
            ->with(['user', 'picks'])
            ->orderByDesc('score_correct')
            ->orderByDesc('score_units')
            ->orderBy('joined_at');
    }

    private function legStats(int $contestId): array
    {
        // Simplified version for the payload. Replace with your actual query.
        $stats = ContestEntry::where('contest_id', $contestId)->get();
        return [];
    }
}