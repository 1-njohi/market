<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixtureTeamStatistic extends Model
{
    protected $fillable = [
        'fixture_id',
        'team_id',
        'statistics',
    ];

    protected $casts = [
        'statistics' => 'array',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // Helper to get specific stat
    public function getStat($key)
    {
        return $this->statistics[$key] ?? null;
    }

    // Helper to get possession as integer (remove %)
    public function getPossessionAttribute()
    {
        $possession = $this->getStat('Ball Possession');
        if ($possession) {
            return (int) filter_var($possession, FILTER_SANITIZE_NUMBER_INT);
        }
        return null;
    }
}