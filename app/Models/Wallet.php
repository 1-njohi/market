<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'pending_balance',
        'total_deposited',
        'total_withdrawn',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
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

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Add amount to balance
     */
    public function credit(float $amount): self
    {
        $this->balance += $amount;
        $this->save();
        return $this;
    }

    /**
     * Deduct amount from balance
     */
    public function debit(float $amount): self
    {
        if (!$this->hasSufficientBalance($amount)) {
            throw new \Exception('Insufficient balance');
        }

        $this->balance -= $amount;
        $this->save();
        return $this;
    }
}