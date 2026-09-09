<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
        'id_on_api',
        'name',
        'photo',
    ];

    public function events()
    {
        return $this->hasMany(FixtureEvent::class, 'player_id');
    }

    public function assists()
    {
        return $this->hasMany(FixtureEvent::class, 'assist_player_id');
    }

    public function matchStatistics()
    {
        return $this->hasMany(PlayerMatchStatistic::class);
    }

    public function lineups()
    {
        return $this->belongsToMany(Fixture::class, 'fixture_lineups', 'player_id', 'fixture_id')
                    ->withPivot('number', 'pos', 'grid');
    }
}