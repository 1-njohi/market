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
        'status', //pending, underway, settled, voided
        'remaining',
        'is_winner',
        'code',
        'priority_score',
        'caption'
    ];

    protected $casts = [
        'total_odds' => 'float',
        'price' => 'float',
        'remaining' => 'integer',
        'is_winner' => 'boolean',
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
}
