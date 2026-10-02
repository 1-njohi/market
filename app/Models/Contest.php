<?php

namespace App\Models;

use App\Services\ReferralCodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Contest extends Model
{
    protected $fillable = [
        'uuid',
        'host_id',
        'name',
        'description',
        'visibility',
        'status',
        'entry_deadline_at',
        'starts_at',
        'ends_at',
        'settled_at',
    ];

    protected $casts = [
        'entry_deadline_at' => 'datetime',
        'starts_at'         => 'datetime',
        'ends_at'           => 'datetime',
        'settled_at'        => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Contest $contest) {
            if (empty($contest->uuid)) {
                $contest->uuid = static::generateUniqueUuid();
            }
        });
    }

    public static function generateUniqueUuid(): string
    {
        do {
            $candidate = 'CNT-' . strtoupper(Str::random(6));
        } while (static::where('uuid', $candidate)->exists());

        return $candidate;
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ContestEntry::class);
    }

    public function legs(): HasMany
    {
        return $this->hasMany(ContestLeg::class)->orderBy('id');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['locked', 'settled', 'cancelled'], true);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && $this->entry_deadline_at->isFuture();
    }
}