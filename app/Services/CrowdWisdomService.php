<?php

namespace App\Services;

use App\Models\Betslip;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Support\Collection;

class CrowdWisdomService
{
    /**
     * Per-request caches. Prevents N+1 when a page renders many
     * distributions (e.g. a betslip with 6 legs).
     */
    private ?array $sellerIds = null;          // user_id => true
    private ?array $topPercentileUserIds = null; // percentile => [user_id, ...]

    /**
     * Whether the current total qualifies for display.
     */
    public function isVisible(int $rawCount): bool
    {
        return $rawCount >= (int) config('marketplace.crowd_wisdom.min_threshold', 50);
    }

    /**
     * Return the full payload for a single (fixture, market).
     *
     * Shape:
     * [
     *   'is_visible'      => bool,
     *   'raw_count'       => int,
     *   'threshold'       => int,
     *   'all'             => ['Home' => 62, 'Draw' => 23, 'Away' => 15],
     *   'sellers'         => [...] | null,
     *   'top_percentiles' => [5 => [...], 10 => [...], 25 => [...], 50 => [...]],
     * ]
     *
     * When is_visible is false, `all`, `sellers`, and `top_percentiles`
     * are null — no distribution data leaks before threshold.
     */
    public function forFixture(int $fixtureId, int $marketId): array
    {
        $threshold = (int) config('marketplace.crowd_wisdom.min_threshold', 50);

        /** @var Collection<int, Prediction> $predictions */
        $predictions = Prediction::query()
            ->where('fixture_id', $fixtureId)
            ->where('market_id', $marketId)
            ->with('user:id,prediction_points')
            ->get()
            // One row per user. The unique index already enforces this,
            // but be defensive.
            ->unique('user_id')
            ->values();

        $rawCount = $predictions->count();

        if (!$this->isVisible($rawCount)) {
            return [
                'is_visible' => false,
                'raw_count' => $rawCount,
                'threshold' => $threshold,
                'all' => null,
                'sellers' => null,
                'top_percentiles' => null,
            ];
        }

        $distributions = [
            'all' => $this->buildConsensus($predictions),
            'sellers' => $this->buildSellerConsensus($predictions),
            'top_percentiles' => $this->buildTopPercentileConsensus($predictions),
        ];

        return [
            'is_visible' => true,
            'raw_count' => $rawCount,
            'threshold' => $threshold,
            'all' => $distributions['all'],
            'sellers' => $distributions['sellers'],
            'top_percentiles' => $distributions['top_percentiles'],
        ];
    }

    /**
     * Confidence-weighted plurality for the given prediction set.
     *
     * @param  Collection<int, Prediction>  $predictions
     * @return array<string, int>  selection => percentage
     */
    private function buildConsensus(Collection $predictions): array
    {
        if ($predictions->isEmpty())
            return [];

        $weighted = [];
        $total = 0.0;

        foreach ($predictions as $p) {
            $confidence = max(1, min(5, (int) $p->confidence));
            $reputation = $p->user ? $p->user->reputationWeight() : 1.0;
            $weight = $confidence * $reputation;

            $weighted[$p->selection] = ($weighted[$p->selection] ?? 0) + $weight;
            $total += $weight;
        }

        arsort($weighted);

        $out = [];
        foreach ($weighted as $sel => $w) {
            $out[$sel] = (int) round(($w / $total) * 100);
        }

        return $out;
    }

    /**
     * @param  Collection<int, Prediction>  $predictions
     * @return array<string, int>|null
     */
    private function buildSellerConsensus(Collection $predictions): ?array
    {
        $sellerIds = $this->getSellerIds();

        $filtered = $predictions->filter(
            fn(Prediction $p) => isset($sellerIds[$p->user_id])
        );

        if ($filtered->isEmpty()) {
            return null;
        }

        return $this->buildConsensus($filtered);
    }

    /**
     * @param  Collection<int, Prediction>  $predictions
     * @return array<int, array<string, int>|null>
     */
    private function buildTopPercentileConsensus(Collection $predictions): array
    {
        $percentiles = (array) config('marketplace.crowd_wisdom.top_percentiles', []);
        $topIds = $this->getTopPercentileUserIds($percentiles);

        $out = [];

        foreach ($percentiles as $pct) {
            $allowed = $topIds[$pct] ?? [];

            if (empty($allowed)) {
                $out[$pct] = null;
                continue;
            }

            $allowedSet = array_flip($allowed);

            $filtered = $predictions->filter(
                fn(Prediction $p) => isset($allowedSet[$p->user_id])
            );

            $out[$pct] = $filtered->isEmpty() ? null : $this->buildConsensus($filtered);
        }

        return $out;
    }

    /**
     * Users who have created at least one listed (sellable) betslip.
     *
     * @return array<int, true>  user_id => true
     */
    private function getSellerIds(): array
    {
        if ($this->sellerIds !== null) {
            return $this->sellerIds;
        }

        $ids = Betslip::query()
            ->where('is_sellable', true)
            ->distinct()
            ->pluck('user_id')
            ->all();

        return $this->sellerIds = array_flip($ids);
    }

    /**
     * Users in the top N% by prediction_points, computed once per request
     * for the largest requested percentile. Smaller percentiles are a
     * subset of that.
     *
     * @param  array<int>  $percentiles
     * @return array<int, array<int>>  percentile => [user_id, ...]
     */
    private function getTopPercentileUserIds(array $percentiles): array
    {
        if ($this->topPercentileUserIds !== null) {
            return $this->topPercentileUserIds;
        }

        if (empty($percentiles)) {
            return $this->topPercentileUserIds = [];
        }

        $totalUsers = User::where('prediction_points', '>', 0)->count();

        if ($totalUsers === 0) {
            return $this->topPercentileUserIds = [];
        }

        $out = [];

        foreach ($percentiles as $pct) {
            $take = max(1, (int) ceil($totalUsers * ($pct / 100)));

            $out[$pct] = User::query()
                ->where('prediction_points', '>', 0)
                ->orderByDesc('prediction_points')
                ->limit($take)
                ->pluck('id')
                ->all();
        }

        return $this->topPercentileUserIds = $out;
    }
}