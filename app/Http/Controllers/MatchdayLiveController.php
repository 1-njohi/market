<?php

namespace App\Http\Controllers;

use App\Services\Matchday\MatchdayLeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchdayLiveController extends Controller
{
    public function __construct(
        protected MatchdayLeaderboardService $leaderboard,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user   = Auth::user();
        $season = config('marketplace.matchday.seasons.0');

        $rows = $this->leaderboard->liveForUser($user, $season);

        // ETag from the tuple of (uuid, legs_resolved, correct, incorrect,
        // pending, rank_current). If nothing changed since the last poll,
        // return 304 with no body — saves bandwidth for a 30-second poll.
        $signature = collect($rows)
            ->map(fn ($r) => "{$r['uuid']}:{$r['legs_resolved']}:{$r['correct']}:{$r['incorrect']}:{$r['pending']}:{$r['rank_current']}")
            ->implode('|');

        $etag = '"' . md5($signature) . '"';

        if ($request->header('If-None-Match') === $etag) {
            return response()->json([], 304)->setEtag($etag);
        }

        return response()
            ->json(['data' => $rows])
            ->setEtag($etag);
    }
}