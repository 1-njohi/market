<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetslipRevision extends Model
{
    protected $fillable = [
        'betslip_id', 'edited_by', 'caption', 'reasoning', 'legs', 'edit_reason',
    ];

    protected $casts = [
        'legs' => 'array',
    ];

    public function betslip(): BelongsTo
    {
        return $this->belongsTo(Betslip::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}