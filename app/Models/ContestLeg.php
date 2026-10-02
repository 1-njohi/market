<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContestLeg extends Model
{
    protected $fillable = [
        'contest_id',
        'fixture_id',
        'market_id',
        'status',
        'result_selection',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function picks(): HasMany
    {
        return $this->hasMany(ContestPick::class);
    }

    public function isResolved(): bool
    {
        return $this->status !== 'pending';
    }
}