<?php

namespace App\Services;

use App\Models\Odd;
use App\Models\Prediction;
use App\Services\PredictionPointService;
use Illuminate\Support\Facades\Log;

class PredictionScoringService
{
    public function __construct(
        protected PredictionPointService $points,
    ) {
    }

    /**
     * Score every pending prediction on a fixture after it settles.
     *
     * Called from MarketSettlementService after odds are resolved.
     * Groups by (fixture, market), determines the winning selections,
     * and awards/deducts points accordingly.
     */
    public function scoreFixture(int $fixtureId): void
    {
        // Every prediction on this fixture that hasn't been scored yet.
        $predictions = Prediction::where('fixture_id', $fixtureId)
            ->where('status', 'pending')
            ->get();

        if ($predictions->isEmpty()) {
            return;
        }

        // Group by market so we can look up winners per market at once.
        $byMarket = $predictions->groupBy('market_id');

        foreach ($byMarket as $marketId => $marketPredictions) {
            // The winning selections for this market are any odds with
            // status = 'won'. Voided legs produce empty winners — those
            // predictions score as void.
            $winningSelections = Odd::where('fixture_id', $fixtureId)
                ->where('market_id', $marketId)
                ->where('status', 'won')
                ->pluck('value')
                ->all();

            $isVoidMarket = Odd::where('fixture_id', $fixtureId)
                ->where('market_id', $marketId)
                ->where('status', '!=', 'void')
                ->doesntExist();

            foreach ($marketPredictions as $prediction) {
                $isCorrect = in_array($prediction->selection, $winningSelections, true);

                $scored = $this->points->scorePrediction(
                    confidence: $prediction->confidence,
                    odds: (float) $prediction->odds_at_pick,
                    isCorrect: $isCorrect,
                    isVoid: $isVoidMarket,
                );

                $prediction->update([
                    'status' => $scored['status'],
                    'points_impact' => $scored['points'],
                    'scored_at' => now(),
                ]);

                if ($scored['points'] !== 0) {
                    $desc = match ($scored['status']) {
                        'won' => "Won Pick: {$prediction->selection} @ {$prediction->odds_at_pick}",
                        'lost' => "Lost Pick: {$prediction->selection} @ {$prediction->odds_at_pick}",
                        default => "Void Pick: {$prediction->selection}",
                    };

                    $this->points->adjust(
                        user: $prediction->user,
                        amount: $scored['points'],
                        type: "pick_{$scored['status']}",
                        description: $desc,
                        source: $prediction,
                    );
                }
            }
        }
    }
}