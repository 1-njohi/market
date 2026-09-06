<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    protected $fillable = [
        'id_on_api',
        'date',
        'timestamp',
        'league_id',
        'home_team_id',
        'away_team_id'
    ];

    public function League()
    {
        return $this->belongsTo(League::class);
    }

    public function Goals()
    {
        return $this->hasOne(FixtureGoals::class);
    }

    public function Score()
    {
        return $this->hasOne(FixtureScore::class);
    }

    public function Status()
    {
        return $this->hasOne(FixtureStatus::class);
    }

    public function HomeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id', 'id_on_api');
    }

    public function AwayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id', 'id_on_api');
    }

    public function Odds()
    {
        return $this->hasMany(Odd::class);
    }
}
