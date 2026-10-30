<?php

namespace App\Services;

use App\Models\Betslip;
use App\Models\User;
use Illuminate\Support\Collection;

class AnalystArchiveService
{
    private const MIN_SETTLED_FOR_RATES = 10;

    /**
     * Cumulative record. When $analystId is null, aggregates across all
     * analysts (useful at launch with a single analyst).
     *
     * @return array{
     *   settled_count:int, won:int, lost:int, voided:int, open:int,
     *   total_profit_units:float, roi:?float, win_rate:?float
     * }
     */
    public function record(?int $analystId = null): array
    {
        $settled = Betslip::publishedAnalystSlips()
            ->when($analystId !== null, fn($q) => $q->where('analyst_id', $analystId))
            ->whereIn('status', ['settled', 'voided'])
            ->get(['is_winner', 'total_odds', 'status']);

        $open = Betslip::publishedAnalystSlips()
            ->when($analystId !== null, fn($q) => $q->where('analyst_id', $analystId))
            ->whereIn('status', ['pending', 'underway'])
            ->count();

        return $this->aggregate($settled, $open);
    }

    public function recordForAnalyst(int $analystId): array
    {
        return $this->record($analystId);
    }

    public function rollingRecord(int $analystId, int $days = 30): array
    {
        $settled = Betslip::publishedAnalystSlips()
            ->where('analyst_id', $analystId)
            ->whereIn('status', ['settled', 'voided'])
            ->where('settled_at', '>=', now()->subDays($days))
            ->get(['is_winner', 'total_odds', 'status']);

        return $this->aggregate($settled, 0);
    }

    /**
     * The "last week" strip on the homepage: most recent settled picks,
     * aggregated. Capped at 10 by default so the strip stays one line.
     */
    public function lastSettledBatch(int $limit = 10): array
    {
        $settled = Betslip::publishedAnalystSlips()
            ->whereIn('status', ['settled', 'voided'])
            ->orderByDesc('settled_at')
            ->limit($limit)
            ->get(['is_winner', 'total_odds', 'status']);

        return $this->aggregate($settled, 0);
    }

    protected function aggregate(Collection $rows, int $open): array
    {
        $won = $rows->where('status', 'settled')->where('is_winner', true)->count();
        $lost = $rows->where('status', 'settled')->where('is_winner', false)->count();
        $voided = $rows->where('status', 'voided')->count();

        $settledCount = $won + $lost;

        $profit = $rows->where('status', 'settled')->sum(
            fn($b) => $b->is_winner ? ((float) $b->total_odds - 1.0) : -1.0
        );

        $roi = $settledCount >= self::MIN_SETTLED_FOR_RATES
            ? round(($profit / $settledCount) * 100, 1)
            : null;

        $winRate = $settledCount >= self::MIN_SETTLED_FOR_RATES
            ? round(($won / $settledCount) * 100, 1)
            : null;

        return [
            'settled_count' => $settledCount,
            'won' => $won,
            'lost' => $lost,
            'voided' => $voided,
            'open' => $open,
            'total_profit_units' => round($profit, 3),
            'roi' => $roi,
            'win_rate' => $winRate,
        ];
    }
    /**
     * Ranked list of analysts, sorted by ROI (descending) where ROI is
     * available, then by settled volume, then alphabetically.
     *
     * Analysts under the 10-settled threshold are included but sorted
     * below those with a valid ROI — they can't be ranked fairly yet.
     * Their `record.roi` is `null`, so the UI shows "—".
     *
     * @return array<int, array{user: \App\Models\User, record: array}>
     */
    public function leaderboard(int $limit = 10): array
    {
        // One query for all analyst rows. N is small (single digits at
        // launch, low tens at most), so PHP-side aggregation is fine and
        // avoids N+1 against `record()`.
        $rows = Betslip::publishedAnalystSlips()
            ->whereIn('status', ['settled', 'voided'])
            ->get(['analyst_id', 'is_winner', 'total_odds', 'status']);

        // One query for open counts per analyst.
        $openCounts = Betslip::publishedAnalystSlips()
            ->whereIn('status', ['pending', 'underway'])
            ->whereNotNull('analyst_id')
            ->selectRaw('analyst_id, COUNT(*) as c')
            ->groupBy('analyst_id')
            ->pluck('c', 'analyst_id');

        $byAnalyst = $rows->whereNotNull('analyst_id')->groupBy('analyst_id');

        $analysts = User::where('is_analyst', true)
            ->whereNotNull('analyst_slug')
            ->get()
            ->map(function (User $user) use ($byAnalyst, $openCounts) {
                $analystRows = $byAnalyst->get($user->id, collect());

                $won = $analystRows->where('status', 'settled')->where('is_winner', true)->count();
                $lost = $analystRows->where('status', 'settled')->where('is_winner', false)->count();
                $voided = $analystRows->where('status', 'voided')->count();

                $settledCount = $won + $lost;

                $profit = $analystRows->where('status', 'settled')->sum(
                    fn($b) => $b->is_winner ? ((float) $b->total_odds - 1.0) : -1.0
                );

                $roi = $settledCount >= self::MIN_SETTLED_FOR_RATES
                    ? round(($profit / $settledCount) * 100, 1)
                    : null;

                $winRate = $settledCount >= self::MIN_SETTLED_FOR_RATES
                    ? round(($won / $settledCount) * 100, 1)
                    : null;

                return [
                    'user' => $user,
                    'record' => [
                        'settled_count' => $settledCount,
                        'won' => $won,
                        'lost' => $lost,
                        'voided' => $voided,
                        'open' => (int) ($openCounts[$user->id] ?? 0),
                        'total_profit_units' => round($profit, 3),
                        'roi' => $roi,
                        'win_rate' => $winRate,
                    ],
                ];
            });

        // Sort: analysts with a real ROI first (higher ROI wins), then
        // analysts without enough settled picks (more volume wins), then
        // alphabetically for stability.
        $sorted = $analysts->sort(function ($a, $b) {
            $aHasRoi = $a['record']['roi'] !== null;
            $bHasRoi = $b['record']['roi'] !== null;

            if ($aHasRoi !== $bHasRoi) {
                return $aHasRoi ? -1 : 1;
            }

            if ($aHasRoi) {
                if ($a['record']['roi'] !== $b['record']['roi']) {
                    return $b['record']['roi'] <=> $a['record']['roi'];
                }
            } else {
                if ($a['record']['settled_count'] !== $b['record']['settled_count']) {
                    return $b['record']['settled_count'] <=> $a['record']['settled_count'];
                }
            }

            return strcmp($a['user']->byline(), $b['user']->byline());
        })->values();

        return $sorted->take($limit)->all();
    }

    public function combinedRecord(): array
    {
        return $this->record(null); // Existing method already handles null as "all analysts"
    }
}