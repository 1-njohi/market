<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pure data model. All balance mutations MUST go through
 * App\Services\WalletService so that every change writes a matching
 * Transaction row under a row lock. Do not add credit/debit/hold
 * helpers here — they will not be locked and will not be audited.
 */

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'escrow_balance',
        'total_deposited',
        'total_withdrawn',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'escrow_balance' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
    ];

    /**
     * Get the user that owns this wallet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
