<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PredictionPointLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PredictionPortfolioController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // 1. Fetch the user's point history, paginated
        $logs = PredictionPointLog::where('user_id', $user->id)
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn($log) => [
                'id' => $log->id,
                'amount' => $log->amount,
                'balance_after' => $log->balance_after,
                'type' => $log->type,
                'description' => $log->description,
                'created_at' => $log->created_at->toISOString(),
                'created_ago' => $log->created_at->diffForHumans(),
            ]);

        // 2. Calculate Global Rank
        // Rank 1 is the highest prediction_points
        $rank = User::where('prediction_points', '>', $user->prediction_points)->count() + 1;
        $totalUsers = User::where('prediction_points', '>', 0)->count(); // Only rank users with positive points

        // 3. Simple stats
        $stats = [
            'current_points' => (int) $user->prediction_points,
            'global_rank' => $user->prediction_points > 0 ? $rank : null,
            'total_ranked' => $totalUsers,
            'accuracy' => $this->calculateAccuracy($user),
        ];

        return Inertia::render('Profile/Predictions', [
            'stats' => $stats,
            'logs' => $logs,
        ]);
    }

    /**
     * Calculate the lifetime prediction accuracy (correct / (correct + incorrect))
     */
    private function calculateAccuracy(User $user): ?float
    {
        $won = $user->predictions()->where('status', 'won')->count();
        $lost = $user->predictions()->where('status', 'lost')->count();
        $total = $won + $lost;

        if ($total === 0)
            return null;

        return round(($won / $total) * 100, 1);
    }
}