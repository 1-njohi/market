<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContestResultsController extends Controller
{
    public function show(string $uuid): Response|RedirectResponse
    {
        $contest = Contest::with([
            'host:id,name,code',
            'legs.fixture.homeTeam',
            'legs.fixture.awayTeam',
            'legs.market',
        ])->where('uuid', $uuid)->firstOrFail();

        if ($contest->status !== 'settled') {
            return redirect("/contests/join/{$contest->uuid}");
        }

        $entries = ContestEntry::where('contest_id', $contest->id)
            ->where('status', 'accepted')
            ->with(['user:id,name,code,email_verified_at', 'picks.leg'])
            ->orderBy('rank_final')
            ->get();

        return Inertia::render('Contests/Results', [
            'contest' => [
                'uuid'       => $contest->uuid,
                'name'       => $contest->name,
                'host_name'  => $contest->host->name,
                'settled_at' => $contest->settled_at?->toISOString(),
                'legs' => $contest->legs->map(function (ContestLeg $leg) {
                    return [
                        'id' => $leg->id,
                        'fixture' => [
                            'home_team' => $leg->fixture?->homeTeam?->name ?? 'Unknown',
                            'away_team' => $leg->fixture?->awayTeam?->name ?? 'Unknown',
                            'kickoff'   => $leg->fixture?->date
                                ? \Carbon\Carbon::parse($leg->fixture->date)->toISOString()
                                : null,
                        ],
                        'market'           => $leg->market?->name ?? 'Unknown',
                        'status'           => $leg->status,
                        'result_selection' => $leg->result_selection,
                    ];
                })->values()->all(),
            ],
            'standings' => $entries->map(function (ContestEntry $entry) {
                return [
                    'user_id'     => $entry->user_id,
                    'rank'        => (int) $entry->rank_final,
                    'name'        => $entry->user->name,
                    'code'        => $entry->user->code,
                    'avatar'      => $entry->user->profile_picture_url
                        ?? 'https://api.dicebear.com/10.x/thumbs/svg?seed=' . urlencode($entry->user->name),
                    'correct'     => (int) $entry->score_correct,
                    'units'       => (float) $entry->score_units,
                    'is_verified' => !is_null($entry->user->email_verified_at),
                    'picks' => $entry->picks->map(function ($pick) {
                        return [
                            'leg_id'    => $pick->contest_leg_id,
                            'selection' => $pick->selection,
                            'result'    => $pick->status,   // 'correct' | 'incorrect' | 'void'
                            'is_winner' => $pick->status === 'correct',
                            'odds'      => (float) $pick->odds_at_pick,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ]);
    }
}