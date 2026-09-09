<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    protected $fillable = [
        'id_on_api',
        'referee',
        'timezone',
        'date',
        'timestamp',
        'period_first',
        'period_second',
        'venue_id',
        'league_id',
        'home_team_id',
        'away_team_id',
        'status_long',
        'status_short',
        'status_elapsed',
        'status_extra',
        'goals_home',
        'goals_away',
        'halftime_home',
        'halftime_away',
        'fulltime_home',
        'fulltime_away',
        'extratime_home',
        'extratime_away',
        'penalty_home',
        'penalty_away',
        'home_winner',
        'settled',
    ];

    protected $casts = [
        'date' => 'datetime',
        'home_winner' => 'boolean',
        'settled' => 'boolean',
        'goals_home' => 'integer',
        'goals_away' => 'integer',
        'halftime_home' => 'integer',
        'halftime_away' => 'integer',
        'fulltime_home' => 'integer',
        'fulltime_away' => 'integer',
        'extratime_home' => 'integer',
        'extratime_away' => 'integer',
        'penalty_home' => 'integer',
        'penalty_away' => 'integer',
        'timestamp' => 'integer',
    ];

    public function Venue()
    {
        return $this->belongsTo(Venue::class);
    }


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


    public function Lineups()
    {
        return $this->hasMany(FixtureLineup::class);
    }

    public function TeamStatistics()
    {
        return $this->hasMany(FixtureTeamStatistic::class);
    }

    public function PlayerStatistics()
    {
        return $this->hasMany(PlayerMatchStatistic::class);
    }

    // Scopes
    public function ScopeFinished($query)
    {
        return $query->where('status_short', 'FT');
    }

    public function ScopeNotSettled($query)
    {
        return $query->where('settled', false);
    }

    // Get total goals
    public function getTotalGoalsAttribute()
    {
        return ($this->goals_home ?? 0) + ($this->goals_away ?? 0);
    }
}
