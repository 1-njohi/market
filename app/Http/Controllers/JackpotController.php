<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Services\Matchday\MatchdayLockCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class JackpotController extends Controller
{
    public function index(): Response
    {
        $open = Contest::query()
            ->where('type', 'jackpot')
            ->whereIn('status', ['open', 'locked', 'underway'])
            ->orderBy('locks_at')
            ->withCount(['entries as participant_count' => fn($q) => $q->where('status', 'accepted')])
            ->withCount('legs')
            ->get();

        $recent = Contest::query()
            ->where('type', 'jackpot')
            ->where('status', 'settled')
            ->orderByDesc('settled_at')
            ->limit(10)
            ->withCount(['entries as participant_count' => fn($q) => $q->where('status', 'accepted')])
            ->withCount('legs')
            ->get();

        return Inertia::render('Jackpots/Index', [
            'jackpots' => [
                'open' => $this->mapListRows($open),
                'recent' => $this->mapListRows($recent),
            ],
        ]);
    }

    public function show(Request $request, string $uuid): Response
    {
        $jackpot = Contest::where('type', 'jackpot')
            ->where('uuid', $uuid)
            ->with(['legs.fixture.homeTeam', 'legs.fixture.awayTeam', 'legs.market', 'legs.fixture'])
            ->firstOrFail();

        $user = Auth::user();

        $entry = $user
            ? ContestEntry::where('contest_id', $jackpot->id)
                ->where('user_id', $user->id)
                ->where('status', 'accepted')
                ->first()
            : null;

        $picksByLeg = $entry
            ? ContestPick::where('contest_entry_id', $entry->id)
                ->get()
                ->keyBy('contest_leg_id')
            : collect();

        $digest = null;
        $liveNarrative = null;

        if ($jackpot->locks_at && now()->gte($jackpot->locks_at) && $user) {
            $service = app(\App\Services\Jackpot\JackpotDigestService::class);

            if ($jackpot->status === 'settled') {
                $digest = $service->forUser($jackpot, $user);
                if ($digest) {
                    $digest['narrative'] = $service->narrativeFor($digest, $jackpot->name);
                }
            } else {
                $liveNarrative = $service->liveNarrativeFor($jackpot, $user);
            }
        }

        return Inertia::render('Jackpots/Show', [
            'jackpot' => [
                'uuid' => $jackpot->uuid,
                'name' => $jackpot->name,
                'description' => $jackpot->description,
                'status' => $jackpot->status,
                'locks_at' => $jackpot->locks_at?->toISOString(),
                'is_locked' => $jackpot->locks_at !== null && now()->gte($jackpot->locks_at),
                'is_entered' => $entry !== null,
                'participant_count' => ContestEntry::where('contest_id', $jackpot->id)
                    ->where('status', 'accepted')
                    ->count(),
                'legs' => $jackpot->legs->map(fn(ContestLeg $leg) => [
                    'id' => $leg->id,
                    'fixture' => [
                        'id' => $leg->fixture?->id,
                        'home_team' => $leg->fixture?->homeTeam?->name ?? 'TBD',
                        'away_team' => $leg->fixture?->awayTeam?->name ?? 'TBD',
                        'kickoff' => $leg->fixture?->date?->toIso8601String(),
                        'status' => $leg->fixture?->status_short ?? 'NS',
                    ],
                    'market' => $leg->market?->name ?? 'Market',
                ])->values()->all(),
            ],
            'picks' => $picksByLeg->map(fn(ContestPick $p) => [
                'leg_id' => $p->contest_leg_id,
                'selection' => $p->selection,
            ])->values()->all(),
            'digest' => $digest,
            'live_narrative' => $liveNarrative,
        ]);
    }

    public function enter(string $uuid): RedirectResponse
    {
        $jackpot = Contest::where('type', 'jackpot')
            ->where('uuid', $uuid)
            ->firstOrFail();

        if ($jackpot->locks_at !== null && now()->gte($jackpot->locks_at)) {
            throw ValidationException::withMessages([
                'jackpot' => 'This jackpot has locked. Picks are closed.',
            ]);
        }

        ContestEntry::firstOrCreate(
            ['contest_id' => $jackpot->id, 'user_id' => Auth::id()],
            ['status' => 'accepted', 'joined_at' => now()],
        );

        return redirect()->route('jackpots.show', $jackpot->uuid);
    }

    /** @return array<int, array<string, mixed>> */
    private function mapListRows($rows): array
    {
        return $rows->map(fn(Contest $c) => [
            'uuid' => $c->uuid,
            'name' => $c->name,
            'status' => $c->status,
            'locks_at' => $c->locks_at?->toISOString(),
            'settled_at' => $c->settled_at?->toISOString(),
            'is_locked' => $c->locks_at !== null && now()->gte($c->locks_at),
            'legs_count' => $c->legs_count ?? 0,
            'participant_count' => $c->participant_count ?? 0,
        ])->values()->all();
    }
}