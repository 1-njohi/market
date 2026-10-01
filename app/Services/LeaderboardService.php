<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Betslip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
            fn() => $this->buildLeaderboard($limit),
        );
    }

    /**
     * Ranked leaderboard of every seller with at least one settled slip.
     *
     * Ranking metric: units won at a flat 1u stake per slip.
     *   - Won  → +(total_odds - 1)
     *   - Lost → -1
     *   - Void → 0
     *
     * Windows: '30d', '90d', 'all'.
     * Sorted by units desc, then settled_count desc, then name asc.
     *
     * Every seller with ≥1 settled slip in the window appears. No minimum
     * threshold. Pagination carries a global rank per row.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function ranked(string $window = '90d', int $page = 1, int $perPage = 50): LengthAwarePaginator
    {
        $since = $this->windowStart($window)->toDateTimeString();

        $query = User::query()
            ->select('users.*')
            ->selectRaw($this->unitsSubquery($since))
            ->selectRaw($this->settledCountSubquery($since))
            ->selectRaw($this->wonCountSubquery($since))
            ->whereExists(function ($q) use ($since) {
                $q->select(DB::raw(1))
                    ->from('betslips')
                    ->whereColumn('betslips.user_id', 'users.id')
                    ->whereIn('betslips.status', ['settled', 'voided'])
                    ->where('betslips.updated_at', '>=', $since);
            })
            ->orderByRaw('units_won DESC')
            ->orderByRaw('settled_count DESC')
            ->orderBy('users.name', 'asc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $offset = ($paginator->currentPage() - 1) * $paginator->perPage();

        $paginator->getCollection()->transform(function ($user, $index) use ($offset) {
            return $this->presentRow($user, $offset + $index + 1);
        });

        return $paginator;
    }

    /**
     * Forget the cached leaderboard. Call this after any betslip settles.
     */
    public static function forget(int $limit = 10): void
    {
        // Keep the old signature for backwards-compat with callers that
        // pass a limit; now clears every window since the leaderboard is
        // window-parameterised.
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
                ->map(fn($b) => $b->is_winner ? 'W' : 'L')
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
            ->sortByDesc(fn($s) => [$s['roi'], $s['win_rate']])
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

    private function windowStart(string $window): \Carbon\CarbonInterface
    {
        return match ($window) {
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            'all' => now()->subYears(50),
            default => now()->subDays(90),
        };
    }

    private function unitsSubquery(string $since): string
    {
        return "(
        SELECT COALESCE(SUM(CASE
            WHEN betslips.status = 'voided' THEN 0
            WHEN betslips.is_winner = 1 THEN betslips.total_odds - 1
            ELSE -1
        END), 0)
        FROM betslips
        WHERE betslips.user_id = users.id
          AND betslips.status IN ('settled', 'voided')
          AND betslips.updated_at >= '{$since}'
    ) AS units_won";
    }

    private function settledCountSubquery(string $since): string
    {
        return "(
        SELECT COUNT(*)
        FROM betslips
        WHERE betslips.user_id = users.id
          AND betslips.status IN ('settled', 'voided')
          AND betslips.updated_at >= '{$since}'
    ) AS settled_count";
    }

    private function wonCountSubquery(string $since): string
    {
        return "(
        SELECT COUNT(*)
        FROM betslips
        WHERE betslips.user_id = users.id
          AND betslips.status = 'settled'
          AND betslips.is_winner = 1
          AND betslips.updated_at >= '{$since}'
    ) AS won_count";
    }
    private function presentRow(User $user, int $rank): array
    {
        $units = (float) ($user->units_won ?? 0);
        $settledCount = (int) ($user->settled_count ?? 0);
        $wonCount = (int) ($user->won_count ?? 0);

        $winRate = $settledCount > 0
            ? round(($wonCount / $settledCount) * 100, 1)
            : 0.0;

        // Last 6 settled slips for the recent form strip.
        $recent = Betslip::where('user_id', $user->id)
            ->whereIn('status', ['settled', 'voided'])
            ->orderByDesc('updated_at')
            ->take(6)
            ->get()
            ->map(function (Betslip $b) {
                if ($b->status === 'voided')
                    return 'V';
                return $b->is_winner ? 'W' : 'L';
            })
            ->values()
            ->all();

        return [
            'user_id' => $user->id,
            'rank' => $rank,
            'name' => $user->name,
            'code' => $user->code,
            'avatar' => $user->profile_picture_url
                ?? 'https://api.dicebear.com/10.x/thumbs/svg?seed=' . urlencode($user->name),
            'units' => round($units, 2),
            'win_rate' => $winRate,
            'settled_count' => $settledCount,
            'won_count' => $wonCount,
            'recent_form' => $recent,
            'is_verified' => !is_null($user->email_verified_at),
        ];
    }
}