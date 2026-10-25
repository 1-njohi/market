<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubLeagueMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_league_id',
        'user_id',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function subLeague(): BelongsTo
    {
        return $this->belongsTo(SubLeague::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}