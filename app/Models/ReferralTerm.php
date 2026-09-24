<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralTerm extends Model
{
    protected $fillable = [
        'user_id',
        'reward_percentage',
        'max_transactions',
        'window_months',
        'referee_discount_pct',
        'referee_discount_cap',
        'notes',
        'granted_by',
        'granted_at',
        'expires_at',
    ];

    protected $casts = [
        'reward_percentage' => 'decimal:4',
        'max_transactions' => 'integer',
        'window_months' => 'integer',
        'referee_discount_pct' => 'decimal:4',
        'referee_discount_cap' => 'decimal:2',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}