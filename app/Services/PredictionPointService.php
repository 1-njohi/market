<?php

namespace App\Services;

use App\Models\PredictionPointLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PredictionPointService
{
    /**
     * Adjust a user's prediction points and log the transaction.
     * Runs inside a single DB transaction so the balance and the log
     * entry are always in sync.
     *
     * A $amount of 0 is a no-op — no log entry, no update. This is
     * common when a pick is worth 0 points (e.g. a 1-star pick at even
     * odds, or a void selection).
     */
    public function adjust(
        User $user,
        int $amount,
        string $type,
        string $description,
        ?Model $source = null,
    ): void {
        if ($amount === 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount, $type, $description, $source) {
            $user->increment('prediction_points', $amount);
            $user->refresh();

            PredictionPointLog::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $user->prediction_points,
                'type' => $type,
                'description' => $description,
                'source_type' => $source ? get_class($source) : null,
                'source_id' => $source ? $source->getKey() : null,
            ]);
        });
    }

    /**
     * Compute the point impact of a single prediction.
     *
     * @return array{points: int, status: string}   status is 'won'|'lost'|'void'
     */
    public function scorePrediction(int $confidence, float $odds, bool $isCorrect, bool $isVoid): array
    {
        if ($isVoid) {
            return ['points' => 0, 'status' => 'void'];
        }

        $multiplier = $isCorrect ? 10 : -15;
        $points = (int) round(($confidence * ($odds - 1)) * $multiplier);

        return [
            'points' => $points,
            'status' => $isCorrect ? 'won' : 'lost',
        ];
    }
}