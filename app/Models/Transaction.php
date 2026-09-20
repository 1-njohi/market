<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transactionable_type',
        'transactionable_id',
        'type',
        'balance_type',
        'amount',
        'balance_before',
        'balance_after',
        'status',
        'reference',
        'description',
        'metadata',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Transaction types
     */
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_PURCHASE = 'purchase';
    const TYPE_PAYOUT = 'payout';
    const TYPE_PENDING_PAYOUT = 'pending_payout';
    const TYPE_PENDING_RELEASE = 'pending_release';
    const TYPE_REFUND = 'refund';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_FEE = 'fee';
    const TYPE_WINNINGS = 'winnings';

    /**
     * Transaction statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Get the user who owns this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent transactionable model (betslip, payout, etc.)
     */
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for completed transactions
     */

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if transaction is a credit (positive amount)
     */
    public function isCredit(): bool
    {
        return (float) $this->amount > 0;
    }
    /**
     * Check if transaction is a debit (negative amount)
     */
    public function isDebit(): bool
    {
        return (float) $this->amount < 0;
    }
    /**
     * Get absolute amount (always positive)
     */
    public function getAbsoluteAmountAttribute(): string
    {
        return number_format(abs((float) $this->amount), 2, '.', '');
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}