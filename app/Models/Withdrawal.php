<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference',
        'mpesa_conversation_id',
        'mpesa_originator_conversation_id',
        'mpesa_receipt',
        'amount',
        'currency',
        'payment_method',
        'destination',
        'status',
        'failure_reason',
        'paystack_response',
        'mpesa_response',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'destination' => 'array',
        'paystack_response' => 'array',
        'mpesa_response' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Withdrawal statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the user who made this withdrawal
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for pending withdrawals
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for processing withdrawals
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope for completed withdrawals
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed withdrawals
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Check if withdrawal is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if withdrawal is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if withdrawal is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if withdrawal failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get formatted bank details
     */
    public function getBankDetailsAttribute(): ?array
    {
        return $this->destination;
    }

    /**
     * Get bank name
     */
    public function getBankNameAttribute(): ?string
    {
        return $this->destination['bank_name'] ?? $this->destination['bank'] ?? null;
    }

    /**
     * Get account number
     */
    public function getAccountNumberAttribute(): ?string
    {
        return $this->destination['account_number'] ?? null;
    }

    /**
     * Get account holder name
     */
    public function getAccountNameAttribute(): ?string
    {
        return $this->destination['account_name'] ?? $this->destination['name'] ?? null;
    }
}