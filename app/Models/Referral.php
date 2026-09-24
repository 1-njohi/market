<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'referrer_id',
        'referee_id',
        'code_used',
        'signed_up_at',
        'expires_at',
        'wins_counted',
        'total_earned',
    ];

    protected $casts = [
        'signed_up_at' => 'datetime',
        'expires_at' => 'datetime',
        'wins_counted' => 'integer',
        'total_earned' => 'decimal:2',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasCapacity(): bool
    {
        return $this->wins_counted < 20;   // overridden by referrer's terms in reward slice
    }
}