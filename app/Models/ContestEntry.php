<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContestEntry extends Model
{
    protected $fillable = [
        'contest_id',
        'user_id',
        'status',
        'score_correct',
        'score_units',
        'rank_final',
        'joined_at',
        'settled_at',
    ];

    protected $casts = [
        'score_correct' => 'integer',
        'score_units'   => 'decimal:2',
        'rank_final'    => 'integer',
        'joined_at'     => 'datetime',
        'settled_at'    => 'datetime',
    ];

    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function picks(): HasMany
    {
        return $this->hasMany(ContestPick::class);
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}