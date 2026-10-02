<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContestPick extends Model
{
    protected $fillable = [
        'contest_entry_id',
        'contest_leg_id',
        'selection',
        'odds_at_pick',
        'status',
        'points',
    ];

    protected $casts = [
        'odds_at_pick' => 'decimal:2',
        'points'       => 'decimal:2',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(ContestEntry::class, 'contest_entry_id');
    }

    public function leg(): BelongsTo
    {
        return $this->belongsTo(ContestLeg::class, 'contest_leg_id');
    }
}