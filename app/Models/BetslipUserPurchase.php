<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetslipUserPurchase extends Model
{
    protected $table = 'betslip_user_purchases';

    protected $fillable = [
        'betslip_id',
        'buyer_id',
        'seller_id',
        'purchase_price',
        'total_odds',
        'status',
        'payment_method',
        'payment_reference',
        'purchased_at'
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'total_odds' => 'decimal:2',
        'purchased_at' => 'datetime',
    ];

    // Relationship to betslip
    public function betslip(): BelongsTo
    {
        return $this->belongsTo(Betslip::class);
    }

    // Relationship to buyer
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // Relationship to seller
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Check if purchase is active
    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'completed']);
    }

    // Scope for active purchases
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'completed']);
    }

    // Scope for completed purchases
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}