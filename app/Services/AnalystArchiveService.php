<?php

namespace App\Services;

use App\Models\Betslip;
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
        $won    = $rows->where('status', 'settled')->where('is_winner', true)->count();
        $lost   = $rows->where('status', 'settled')->where('is_winner', false)->count();
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
            'settled_count'      => $settledCount,
            'won'                => $won,
            'lost'               => $lost,
            'voided'             => $voided,
            'open'               => $open,
            'total_profit_units' => round($profit, 3),
            'roi'                => $roi,
            'win_rate'           => $winRate,
        ];
    }
}