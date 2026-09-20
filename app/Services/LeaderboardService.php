<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LeaderboardService
{
    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Get the top-performing sellers for the leaderboard.
     */
    public function getTopSellers(int $limit = 10): array
    {
        return Cache::remember(
            "leaderboard_top_{$limit}",
            self::CACHE_TTL,
            fn () => $this->buildLeaderboard($limit),
        );
    }

    /**
     * Forget the cached leaderboard. Call this after any betslip settles.
     */
    public static function forget(int $limit = 10): void
    {
        Cache::forget("leaderboard_top_{$limit}");
    }

    private function buildLeaderboard(int $limit): array
    {
        // Only consider sellers who have at least one settled betslip
        $sellers = User::query()
            ->whereHas('betslips', function ($q) {
                $q->whereIn('status', ['settled']);
            })
            ->with('sellerMetric')
            ->withCount([
                'betslips as active_tips_count' => function ($q) {
                    $q->whereIn('status', ['pending', 'underway']);
                },
            ])
            ->get();

        if ($sellers->isEmpty()) {
            return [];
        }

        $leaders = $sellers->map(function (User $seller) {
            // Pull the seller's last 9 settled betslips for recent form + streak
            $settled = $seller->betslips()
                ->whereIn('status', ['settled'])
                ->orderByDesc('updated_at')
                ->take(9)
                ->get();

            $recentForm = $settled
                ->map(fn ($b) => $b->is_winner ? 'W' : 'L')
                ->values()
                ->toArray();

            return [
                'id' => $seller->id,
                'name' => $seller->name,
                'code' => $seller->code,
                'avatar' => $seller->profile_picture_url
                    ?? "https://api.dicebear.com/10.x/thumbs/svg?seed=" . urlencode($seller->name),
                'roi' => round((float) ($seller->sellerMetric->roi ?? 0), 1),
                'win_rate' => round((float) ($seller->sellerMetric->win_rate ?? 0), 1),
                'streak' => $this->calculateCurrentStreak($settled),
                'recent_form' => $recentForm,
                'active_tips' => (int) $seller->active_tips_count,
                'total_sold' => (int) ($seller->sellerMetric->total_sold ?? 0),
                'badges' => $this->deriveBadges($seller),
            ];
        });

        // Rank by ROI desc, win_rate as tiebreaker
        return $leaders
            ->sortByDesc(fn ($s) => [$s['roi'], $s['win_rate']])
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Count consecutive wins from the most recent settled betslip backwards.
     */
    private function calculateCurrentStreak(Collection $betslips): int
    {
        $streak = 0;
        foreach ($betslips as $betslip) {
            if ($betslip->is_winner) {
                $streak++;
            } else {
                break;
            }
        }
        return $streak;
    }

    /**
     * Derive display badges from metrics.
     */
    private function deriveBadges(User $seller): array
    {
        $badges = [];
        $metric = $seller->sellerMetric;

        if ($metric && $metric->roi >= 20) {
            $badges[] = ['name' => 'Top 1%', 'avatar' => ''];
        }
        if ($metric && $metric->total_sold >= 50) {
            $badges[] = ['name' => 'BigMan', 'avatar' => ''];
        }
        if ($metric && $metric->win_rate >= 70) {
            $badges[] = ['name' => 'Sharp', 'avatar' => ''];
        }

        return $badges;
    }
}