<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Services\ContestPickService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContestPickController extends Controller
{
    public function __construct(
        protected ContestPickService $picks,
    ) {}

    public function show(string $uuid): Response
    {
        $contest = Contest::with([
            'host:id,name,code',
            'legs.fixture.homeTeam',
            'legs.fixture.awayTeam',
            'legs.market',
        ])->where('uuid', $uuid)->firstOrFail();

        $entry = $this->resolveEntry($contest, Auth::user());

        $existing = ContestPick::where('contest_entry_id', $entry->id)
            ->get()
            ->keyBy('contest_leg_id');

        return Inertia::render('Contests/Picks', [
            'contest' => [
                'uuid'              => $contest->uuid,
                'name'              => $contest->name,
                'status'            => $contest->status,
                'entry_deadline_at' => $contest->entry_deadline_at->toISOString(),
                'is_locked'         => !$contest->isOpen(),
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
                        'market'    => $leg->market?->name ?? 'Unknown',
                        'options'   => $this->picks->optionsFor($leg),
                        'status'    => $leg->status,
                        'result'    => $leg->result_selection,
                    ];
                })->values()->all(),
            ],
            'picks' => $existing->map(fn (ContestPick $p) => [
                'leg_id'    => $p->contest_leg_id,
                'selection' => $p->selection,
            ])->values()->all(),
        ]);
    }

    public function store(Request $request, string $uuid): RedirectResponse
    {
        $contest = Contest::where('uuid', $uuid)->firstOrFail();
        $entry   = $this->resolveEntry($contest, Auth::user());

        if (!$contest->isOpen()) {
            throw ValidationException::withMessages([
                'picks' => 'This contest is no longer accepting picks.',
            ]);
        }

        $validated = $request->validate([
            'picks'                => ['required', 'array'],
            'picks.*.leg_id'       => ['required', 'integer'],
            'picks.*.selection'    => ['required', 'string', 'max:50'],
        ]);

        $this->picks->submit($contest, $entry, $validated['picks']);

        return redirect("/contests/{$contest->uuid}/picks");
    }

    /**
     * Resolve the authenticated user's entry for this contest, or fail.
     *
     * 403 if there's no accepted entry. Hosts have no entry — they get 403.
     */
    private function resolveEntry(Contest $contest, $user): ContestEntry
    {
        if (!$user) {
            abort(403);
        }
    
        $entry = ContestEntry::where('contest_id', $contest->id)
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->first();
    
        if (!$entry) {
            abort(403);
        }
    
        return $entry;
    }
}