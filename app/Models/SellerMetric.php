<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerMetric extends Model
{
    protected $fillable = [
        'user_id',
        'win_rate',
        'roi',
        'total_sold',
        'total_revenue',
        'avg_price',
        'avg_odds',
        'avg_legs',
        'follower_count',
        'profile_views',
        'weekly_performance',
        'monthly_performance',
        'calculated_at',
    ];

    // Optionally, add relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}