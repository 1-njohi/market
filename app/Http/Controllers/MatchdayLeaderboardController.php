<?php

namespace App\Http\Controllers;

use App\Services\Matchday\MatchdayLeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MatchdayLeaderboardController extends Controller
{
    public function __construct(
        protected MatchdayLeaderboardService $leaderboard,
    ) {}

    public function global(Request $request): JsonResponse
    {
        $data = $request->validate([
            'season' => [
                'required',
                'string',
                Rule::in(config('marketplace.matchday.seasons', [])),
            ],
            'from_matchday' => ['sometimes', 'integer', 'min:1'],
        ]);

        $rows = $this->leaderboard->global(
            $data['season'],
            (int) ($data['from_matchday'] ?? 1),
        );

        return response()->json([
            'data'   => $rows,
            'season' => $data['season'],
        ]);
    }
}