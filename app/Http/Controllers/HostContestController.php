<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HostContestController extends Controller
{
    public function index(): Response
    {
        $contests = Contest::where('host_id', Auth::id())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->withCount([
                'entries as pending_entries' => fn ($q) => $q->where('status', 'pending'),
                'entries as accepted_entries' => fn ($q) => $q->where('status', 'accepted'),
            ])
            ->get()
            ->map(fn (Contest $c) => [
                'id'                 => $c->id,
                'uuid'               => $c->uuid,
                'name'               => $c->name,
                'status'             => $c->status,
                'visibility'         => $c->visibility,
                'entry_deadline_at'  => $c->entry_deadline_at->toISOString(),
                'starts_at'          => $c->starts_at->toISOString(),
                'ends_at'            => $c->ends_at->toISOString(),
                'pending_entries'    => (int) $c->pending_entries,
                'accepted_entries'   => (int) $c->accepted_entries,
                'created_at'         => $c->created_at->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('Contests/Mine', [
            'contests' => $contests,
        ]);
    }

    public function manage(Contest $contest): Response
    {
        if ((int) $contest->host_id !== (int) Auth::id()) {
            abort(403);
        }

        $contest->load([
            'legs.fixture.homeTeam',
            'legs.fixture.awayTeam',
            'legs.market',
        ]);

        $entries = ContestEntry::where('contest_id', $contest->id)
            ->where('user_id', '!=', $contest->host_id)
            ->with('user:id,name,code,email_verified_at')
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'accepted' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END")
            ->orderByDesc('joined_at')
            ->get()
            ->map(fn (ContestEntry $e) => [
                'id'         => $e->id,
                'status'     => $e->status,
                'joined_at'  => $e->joined_at->toISOString(),
                'user' => [
                    'id'          => $e->user->id,
                    'name'        => $e->user->name,
                    'code'        => $e->user->code,
                    'avatar'      => $e->user->profile_picture_url
                        ?? 'https://api.dicebear.com/10.x/thumbs/svg?seed=' . urlencode($e->user->name),
                    'is_verified' => !is_null($e->user->email_verified_at),
                ],
                'picks_count' => $e->picks()->count(),
            ])
            ->values()
            ->all();

        $hostEntry = ContestEntry::where('contest_id', $contest->id)
            ->where('user_id', $contest->host_id)
            ->with('picks')
            ->first();

        $hostPicksByLeg = $hostEntry
            ? $hostEntry->picks->keyBy('contest_leg_id')
            : collect();

        $hostPicks = $contest->legs->map(function ($leg) use ($hostPicksByLeg) {
            $pick = $hostPicksByLeg->get($leg->id);

            return [
                'leg_id'    => $leg->id,
                'market'    => $leg->market?->name ?? 'Unknown Market',
                'home_team' => $leg->fixture?->homeTeam?->name ?? 'Unknown',
                'away_team' => $leg->fixture?->awayTeam?->name ?? 'Unknown',
                'kickoff'   => $leg->fixture?->date
                    ? \Carbon\Carbon::parse($leg->fixture->date)->toISOString()
                    : null,
                'selection' => $pick?->selection,
                'odds'      => $pick ? (float) $pick->odds_at_pick : null,
            ];
        })->values()->all();

        return Inertia::render('Contests/Manage', [
            'contest' => [
                'id'                => $contest->id,
                'uuid'              => $contest->uuid,
                'name'              => $contest->name,
                'status'            => $contest->status,
                'entry_deadline_at' => $contest->entry_deadline_at->toISOString(),
                'legs_count'        => $contest->legs->count(),
                'entries'           => $entries,
                'host_picks'        => $hostPicks,
            ],
        ]);
    }
}