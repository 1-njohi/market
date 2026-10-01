<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    private const WINDOWS = ['30d', '90d', 'all'];
    private const DEFAULT_WINDOW = '90d';

    public function __construct(
        protected LeaderboardService $leaderboard,
    ) {
    }

    public function index(Request $request)
    {
        $window = $request->query('window', self::DEFAULT_WINDOW);

        if (!in_array($window, self::WINDOWS, true)) {
            $window = self::DEFAULT_WINDOW;
        }

        $page = max(1, (int) $request->query('page', 1));

        $leaders = $this->leaderboard->ranked(
            window: $window,
            page: $page,
        );

        // Preserve the window on pagination links.
        $leaders->appends(['window' => $window]);

        return Inertia::render('Leaderboard', [
            'leaders' => $leaders,
            'window' => $window,
        ]);
    }
}