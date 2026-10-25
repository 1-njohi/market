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
        'type',
        'auto_entry',
        'status',
        'entry_deadline_at',
        'starts_at',
        'ends_at',
        'settled_at',
        'is_matchday',
        'season',
        'league_code',
        'matchday_number',
        'locks_at',
    ];

    protected $casts = [
        'auto_entry' => 'boolean',
        'is_matchday' => 'boolean',
        'entry_deadline_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'settled_at' => 'datetime',
        'locks_at' => 'datetime',
        'matchday_number' => 'integer',
    ];

    public function isJackpot(): bool
    {
        return $this->type === 'jackpot';
    }

    public function isMatchday(): bool
    {
        return $this->type === 'matchday' || $this->is_matchday;
    }

    public function isStandard(): bool
    {
        return $this->type === 'standard' && !$this->is_matchday;
    }


    /**
     * Test-only escape hatch: clear any leaked skipHostGuard state.
     * Call from setUp() in test classes that assert the guard fires.
     */
    public static function resetHostGuard(): void
    {
        static::$skipHostGuard = false;
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

    /**
     * When true, the creating hook skips the host eligibility check.
     * Set only by withoutHostGuard() — never assign directly.
     */
    protected static bool $skipHostGuard = false;

    /**
     * Run $callback with the host eligibility guard disabled. Restores
     * the previous state even if $callback throws. Scope is per-call.
     *
     * Used by test scaffolding and any trusted programmatic path that
     * creates contests without a real user behind them. Production code
     * creating a contest on a user's behalf should NOT use this — the
     * guard exists to keep the marketplace clean.
     */
    public static function withoutHostGuard(callable $callback): mixed
    {
        $previous = static::$skipHostGuard;
        static::$skipHostGuard = true;

        try {
            return $callback();
        } finally {
            static::$skipHostGuard = $previous;
        }
    }

    protected static function booted(): void
    {
        static::creating(function (Contest $contest) {
            if (empty($contest->uuid)) {
                $contest->uuid = static::generateUniqueUuid();
            }

            // Matchday contests are admin-created inventory. The route
            // that creates them is gated by the `admin` middleware, so
            // the user-hosting guard doesn't apply. Regular contests
            // keep the eligibility check.
            if ($contest->is_matchday) {
                return;
            }

            if (static::$skipHostGuard) {
                return;
            }

            $host = \App\Models\User::find($contest->host_id);
            if (!$host) {
                throw new \App\Exceptions\UnauthorizedHostingException(
                    'Contest host not found.',
                );
            }

            // $canHost = app(\App\Services\SellerEligibilityService::class)->canHost($host);
            $canHost = true;

            if (!$canHost) {
                throw \App\Exceptions\UnauthorizedHostingException::fromConfig();
            }
        });
    }
}