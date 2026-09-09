<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'id_on_api',
        'name',
        'logo',
        'colors',
    ];

    protected $casts = [
        'colors' => 'array',
    ];

    // Home fixtures
    public function homeFixtures()
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    // Away fixtures
    public function awayFixtures()
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }

    // Lineups
    public function lineups()
    {
        return $this->hasMany(FixtureLineup::class);
    }

    // Player statistics
    public function playerStatistics()
    {
        return $this->hasMany(PlayerMatchStatistic::class);
    }

    // Team statistics
    public function fixtureTeamStatistics()
    {
        return $this->hasMany(FixtureTeamStatistic::class);
    }

    // Events (goals, cards, etc.)
    public function events()
    {
        return $this->hasMany(FixtureEvent::class);
    }
}