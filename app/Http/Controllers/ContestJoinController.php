<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContestJoinController extends Controller
{
    public function show(string $uuid): Response
    {
        $contest = Contest::with(['host:id,name,code', 'legs.fixture.homeTeam', 'legs.fixture.awayTeam', 'legs.market'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $user = Auth::user();

        $entry = $user
            ? ContestEntry::where('contest_id', $contest->id)
                ->where('user_id', $user->id)
                ->first()
            : null;

        $canJoin = $user
            && $user->id !== $contest->host_id
            && $contest->isOpen()
            && $entry === null;

        return Inertia::render('Contests/Join', [
            'contest' => [
                'uuid'        => $contest->uuid,
                'name'        => $contest->name,
                'description' => $contest->description,
                'status'      => $contest->status,
                'host_name'   => $contest->host->name,
                'host_code'   => $contest->host->code,
                'entry_deadline_at' => $contest->entry_deadline_at->toISOString(),
                'starts_at'   => $contest->starts_at->toISOString(),
                'ends_at'     => $contest->ends_at->toISOString(),
                'legs' => $contest->legs->map(function ($leg) {
                    return [
                        'id'       => $leg->id,
                        'fixture'  => [
                            'home_team' => $leg->fixture?->homeTeam?->name ?? 'Unknown',
                            'away_team' => $leg->fixture?->awayTeam?->name ?? 'Unknown',
                            'kickoff'   => $leg->fixture?->date
                                ? \Carbon\Carbon::parse($leg->fixture->date)->toISOString()
                                : null,
                        ],
                        'market'   => $leg->market?->name ?? 'Unknown Market',
                    ];
                })->values()->all(),
            ],
            'entry'    => $entry ? [
                'id'     => $entry->id,
                'status' => $entry->status,
            ] : null,
            'can_join' => $canJoin,
        ]);
    }

    public function store(string $uuid): RedirectResponse
    {
        $contest = Contest::where('uuid', $uuid)->firstOrFail();
        $user    = Auth::user();

        if ($user->id === $contest->host_id) {
            throw ValidationException::withMessages([
                'join' => 'You cannot join your own contest.',
            ]);
        }

        if (!$contest->isOpen()) {
            throw ValidationException::withMessages([
                'join' => 'This contest is no longer accepting entries.',
            ]);
        }

        $existing = ContestEntry::where('contest_id', $contest->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return redirect("/contests/join/{$contest->uuid}");
        }

        ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'pending',
            'joined_at'  => now(),
        ]);

        return redirect("/contests/join/{$contest->uuid}");
    }
}