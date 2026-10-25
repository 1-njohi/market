<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Prediction extends Model
{
    protected $fillable = [
        'user_id',
        'fixture_id',
        'market_id',
        'selection',
        'odds_at_pick',
        'confidence',
        'source_id',
        'source_type',
        'source_id',
        'status',
        'points_impact',
        'scored_at',
    ];

    protected $casts = [
        'odds_at_pick' => 'decimal:2',
        'confidence' => 'integer',
        'points_impact' => 'integer',
        'scored_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Score is the raw points this prediction would earn if it wins.
     * Formula: confidence × (odds - 1) × 10.
     *
     * Used by the crowd wisdom weighting and by the UI to preview a
     * pick's "at stake" value before it's submitted.
     */
    public function potentialPoints(): int
    {
        return (int) round($this->confidence * ((float) $this->odds_at_pick - 1) * 10);
    }

    /**
     * Penalty is what a loss would cost. Same formula × 1.5 as the
     * reward, so a wrong high-confidence longshot hurts more than a
     * wrong low-confidence favourite.
     */
    public function potentialPenalty(): int
    {
        return (int) round($this->confidence * ((float) $this->odds_at_pick - 1) * 15);
    }
}