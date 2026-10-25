<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\User;
use App\Exceptions\InvalidInviteCodeException;
use App\Exceptions\SubLeagueFullException;
use App\Services\Matchday\SubLeagueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\Matchday\SubLeagueJoinedNotification;
use Illuminate\Validation\Rule;

class SubLeagueController extends Controller
{
    public function __construct(
        protected SubLeagueService $service,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $supported = array_keys(
            (array) config('marketplace.matchday.supported_league_codes', [])
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'season' => [
                'required',
                'string',
                Rule::in(config('marketplace.matchday.seasons', [])),
            ],
            'league_codes' => ['required', 'array', 'min:1'],
            'league_codes.*' => ['string', Rule::in($supported)],
        ]);

        $exists = \App\Models\SubLeague::where('season', $data['season'])
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => 'A sub-league with that name already exists for this season.',
            ]);
        }

        $sub = $this->service->create(
            Auth::user(),
            $data['name'],
            $data['season'],
            $data['league_codes'],
        );

        return response()->json(['data' => $sub], 201);
    }

    public function join(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invite_code' => ['required', 'string', 'max:12'],
        ]);

        try {
            $sub = $this->service->join(Auth::user(), $data['invite_code']);
        } catch (InvalidInviteCodeException $e) {
            abort(404, $e->getMessage());
        } catch (SubLeagueFullException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'invite_code' => $e->getMessage(),
            ]);
        }

        // Notify the owner that someone joined. Skip when the owner joins
        // their own sub-league — that can't happen today (owner is added at
        // creation, not via join) but the guard costs nothing.
        $joiner = Auth::user();
        if ((int) $sub->owner_id !== (int) $joiner->id) {
            $sub->owner?->notify(new \App\Notifications\Matchday\SubLeagueJoinedNotification($sub, $joiner));
        }

        $this->enterUserIntoUnlockedMatchdayContests($joiner, $sub->season);

        return response()->json(['data' => $sub]);
    }

    /**
     * After a successful join, ensure the user has an entry in every
     * matchday contest for the sub-league's season that hasn't locked yet.
     */
    private function enterUserIntoUnlockedMatchdayContests(User $user, string $season): void
    {
        $contests = Contest::where('is_matchday', true)
            ->where('season', $season)
            ->where('locks_at', '>', now())
            ->get();

        $entryService = app(\App\Services\Matchday\MatchdayEntryService::class);

        foreach ($contests as $contest) {
            $entryService->enterUserIfEligible($user, $contest);
        }
    }
}