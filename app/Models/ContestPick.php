<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Observers\ContestPickObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(ContestPickObserver::class)]
class ContestPick extends Model
{
    protected $fillable = [
        'contest_entry_id',
        'contest_leg_id',
        'selection',
        'odds_at_pick',
        'status',
        'points',
        'revealed_at',
    ];

    protected $casts = [
        'odds_at_pick' => 'decimal:2',
        'points' => 'decimal:2',
        'revealed_at' => 'datetime',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(ContestEntry::class, 'contest_entry_id');
    }

    public function leg(): BelongsTo
    {
        return $this->belongsTo(ContestLeg::class, 'contest_leg_id');
    }

    protected static bool $skipMatchdayLock = false;

    /**
     * Run $callback with the matchday lock disabled. Restores the
     * previous state even on exception. Used by test scaffolding and
     * seeders that legitimately write picks against a contest that has
     * already locked or settled.
     */
    public static function withoutMatchdayLock(callable $callback): mixed
    {
        $previous = static::$skipMatchdayLock;
        static::$skipMatchdayLock = true;

        try {
            return $callback();
        } finally {
            static::$skipMatchdayLock = $previous;
        }
    }

    public static function isMatchdayLockSkipped(): bool
    {
        return static::$skipMatchdayLock;
    }
}