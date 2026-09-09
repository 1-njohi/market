<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixtureEvent extends Model
{
    protected $fillable = [
        'fixture_id',
        'team_id',
        'player_id',
        'assist_player_id',
        'type',
        'detail',
        'comments',
        'time_elapsed',
        'time_extra',
    ];

    protected $casts = [
        'time_elapsed' => 'integer',
        'time_extra' => 'integer',
    ];

    // Event types constants
    const TYPE_GOAL = 'Goal';
    const TYPE_CARD = 'Card';
    const TYPE_SUBSTITUTION = 'subst';
    const TYPE_VAR = 'VAR';

    // Relationships
    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function assistPlayer()
    {
        return $this->belongsTo(Player::class, 'assist_player_id');
    }

    // Scope for goals
    public function scopeGoals($query)
    {
        return $query->where('type', self::TYPE_GOAL);
    }

    // Scope for cards
    public function scopeCards($query)
    {
        return $query->where('type', self::TYPE_CARD);
    }
}