<?php

namespace App\Services;

use App\Models\Prediction;

class PredictionRecorderService
{
    public function record(
        int $userId,
        int $fixtureId,
        int $marketId,
        string $selection,
        float $odds,
        int $confidence,
        string $sourceType,
        ?string $sourceId = null,
    ): Prediction {
        return Prediction::updateOrCreate(
            [
                'user_id'    => $userId,
                'fixture_id' => $fixtureId,
                'market_id'  => $marketId,
            ],
            [
                'selection'     => $selection,
                'odds_at_pick'  => $odds,
                'confidence'    => $confidence,
                'source_type'   => $sourceType,
                'source_id'     => $sourceId,
                'status'        => 'pending',
                'points_impact' => null,
                'scored_at'     => null,
            ],
        );
    }
}