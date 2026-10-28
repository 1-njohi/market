<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \App\Traits\TraitUuid;
use App\Observers\BetslipObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(BetslipObserver::class)]
class Betslip extends Model
{
    use TraitUuid;
    protected $fillable = [
        'id',
        'user_id',
        'total_odds',
        'price',
        'status',
        'remaining',
        'is_winner',
        'code',
        'priority_score',
        'break_even_stake',
        'caption',
        'is_sellable',
        'is_delisted',
        'settled_at',
        'is_analyst_slip',
        'analyst_id',
        'reasoning',
        'analyst_published_at',
        'is_edited',
        'revision_count',
        'analyst_unpublished_reason',
        'post_settlement_note',
        'post_settlement_note_at',
    ];

    protected $casts = [
        'total_odds' => 'float',
        'price' => 'float',
        'remaining' => 'integer',
        'is_winner' => 'boolean',
        'is_sellable' => 'boolean',
        'is_delisted' => 'boolean',
        'break_even_stake' => 'decimal:2',
        'settled_at' => 'datetime',
        'is_analyst_slip' => 'boolean',
        'analyst_published_at' => 'datetime',
        'is_edited' => 'boolean',
        'revision_count' => 'integer',
        'post_settlement_note_at' => 'datetime',
    ];

    // Relationship with the user (seller)
    public function Seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Many-to-many relationship with Odd model
    public function Odds()
    {
        return $this->belongsToMany(Odd::class, 'betslip_odd')
            ->withPivot('status', 'odd_value_at_time')
            ->withTimestamps();
    }

    // Get only pending odds
    public function PendingOdds()
    {
        return $this->belongsToMany(Odd::class, 'betslip_odd')
            ->withPivot('status', 'odd_value_at_time')
            ->wherePivot('status', 'pending');
    }

    // Get won odds
    public function WonOdds()
    {
        return $this->belongsToMany(Odd::class, 'betslip_odd')
            ->withPivot('status', 'odd_value_at_time')
            ->wherePivot('status', 'won');
    }

    // Get lost odds
    public function LostOdds()
    {
        return $this->belongsToMany(Odd::class, 'betslip_odd')
            ->withPivot('status', 'odd_value_at_time')
            ->wherePivot('status', 'lost');
    }

    // Helper method to calculate if betslip is a winner
    public function CalculateWinner()
    {
        $lostCount = $this->odds()->wherePivot('status', 'lost')->count();
        return $lostCount === 0 && $this->odds()->count() > 0;
    }

    // Helper method to update betslip status
    public function UpdateStatus()
    {
        $pendingCount = $this->pendingOdds()->count();
        $lostCount = $this->lostOdds()->count();

        if ($pendingCount === 0 && $lostCount === 0) {
            $this->update(['status' => 'settled', 'is_winner' => true]);
        } elseif ($lostCount > 0 && $pendingCount === 0) {
            $this->update(['status' => 'settled', 'is_winner' => false]);
        } elseif ($pendingCount > 0) {
            $this->update(['status' => 'underway']);
        }
    }


    // Buyers who purchased this betslip
    public function buyers()
    {
        return $this->belongsToMany(User::class, 'betslip_user_purchases', 'betslip_id', 'buyer_id')
            ->withPivot([
                'id',
                'seller_id',
                'purchase_price',
                'total_odds',
                'status',
                'payment_method',
                'payment_reference',
                'purchased_at',
                'created_at',
                'updated_at'
            ])
            ->withTimestamps();
    }

    /**
     * Users watching this betslip.
     */
    public function watchers()
    {
        return $this->belongsToMany(
            User::class,
            'betslip_watches',
            'betslip_id',
            'user_id'
        )->withPivot('watched_at');
    }

    // Get all purchases for this betslip
    public function purchases()
    {
        return $this->hasMany(BetslipUserPurchase::class);
    }

    // Get active purchases (pending or completed)
    public function activePurchases()
    {
        return $this->purchases()->where('status', 'pending');
    }

    // Check if a user has purchased this betslip
    public function isPurchasedBy(User $user): bool
    {
        return $this->buyers()->where('buyer_id', $user->id)->exists();
    }

    // Get the purchase record for a specific user
    public function getPurchaseForUser(User $user): ?BetslipUserPurchase
    {
        return $this->purchases()->where('buyer_id', $user->id)->first();
    }

    // Check if betslip is available for purchase
    public function isAvailableForPurchase(): bool
    {
        return $this->status === 'pending' && $this->remaining > 0;
    }

    protected static function booted(): void
    {
        static::creating(function (Betslip $betslip) {
            $user = User::find($betslip->user_id);
            if (!$user) {
                return;
            }

            $canSell = app(\App\Services\SellerEligibilityService::class)->canSell($user);

            // Explicit intent wins. Callers that pass `is_sellable`
            // (the controller does, based on the user's toggle) get
            // their choice respected. Callers that don't — seeders,
            // test fixtures — default to true, matching the UI's
            // "toggle is ON by default" contract.
            $requested = array_key_exists('is_sellable', $betslip->getAttributes())
                ? (bool) $betslip->is_sellable
                : true;

            $final = $canSell && $requested;
            $betslip->is_sellable = $final;

            if (!$final) {
                // Sentinel price so ROI aggregates stay coherent for
                // non-listed slips. The value never reaches the
                // marketplace (is_sellable is false), so it's a
                // bookkeeping device only.
                $betslip->price = 1;
            }
        });
    }
    public function analyst(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }

    public function revisions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BetslipRevision::class);
    }


    public function scopeAnalystSlips($q)
    {
        return $q->where('is_analyst_slip', true);
    }

    public function scopePublishedAnalystSlips($q)
    {
        return $q->where('is_analyst_slip', true)->whereNotNull('analyst_published_at');
    }

    public function scopeOpenAnalystSlips($q)
    {
        return $q->where('is_analyst_slip', true)
            ->whereNotNull('analyst_published_at')
            ->whereIn('status', ['pending', 'underway']);
    }

    public function scopeSettledAnalystSlips($q)
    {
        return $q->where('is_analyst_slip', true)
            ->whereNotNull('analyst_published_at')
            ->whereIn('status', ['settled', 'voided']);
    }


    public function isAnalystSlip(): bool
    {
        return $this->is_analyst_slip === true;
    }

    public function isAnalystPublished(): bool
    {
        return $this->is_analyst_slip && $this->analyst_published_at !== null;
    }

    public function isEditableAsAnalyst(): bool
    {
        return $this->is_analyst_slip
            && $this->analyst_published_at !== null
            && in_array($this->status, ['pending', 'underway'], true);
    }

    /**
     * Per-unit profit at slip level. Uses total_odds (the slip's combined
     * price), not the marketplace KES price. Pending/underway → null.
     */
    public function analystProfitUnits(): ?float
    {
        if (!$this->isAnalystSlip()) {
            return null;
        }

        return match ($this->status) {
            'settled' => $this->is_winner ? (float) $this->total_odds - 1.0 : -1.0,
            'voided' => 0.0,
            default => null,
        };
    }
}
