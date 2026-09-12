<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerMatchStatistic extends Model
{
    protected $fillable = [
        'fixture_id',
        'player_id',
        'team_id',
        'minutes',
        'number',
        'position',
        'rating',
        'seller',
        'substitute',
        'goals',
        'assists',
        'shots_total',
        'shots_on_target',
        'passes_total',
        'passes_accuracy',
        'tackles',
        'blocks',
        'interceptions',
        'duels_total',
        'duels_won',
        'dribbles_attempts',
        'dribbles_success',
        'fouls_drawn',
        'fouls_committed',
        'yellow_cards',
        'red_cards',
        'penalty_scored',
        'penalty_missed',
        'penalty_saved',
    ];

    protected $casts = [
        'seller' => 'boolean',
        'substitute' => 'boolean',
        'rating' => 'decimal:1',
        'minutes' => 'integer',
        'goals' => 'integer',
        'assists' => 'integer',
        'shots_total' => 'integer',
        'shots_on_target' => 'integer',
        'passes_total' => 'integer',
        'passes_accuracy' => 'integer',
        'tackles' => 'integer',
        'blocks' => 'integer',
        'interceptions' => 'integer',
        'duels_total' => 'integer',
        'duels_won' => 'integer',
        'dribbles_attempts' => 'integer',
        'dribbles_success' => 'integer',
        'fouls_drawn' => 'integer',
        'fouls_committed' => 'integer',
        'yellow_cards' => 'integer',
        'red_cards' => 'integer',
        'penalty_scored' => 'integer',
        'penalty_missed' => 'integer',
        'penalty_saved' => 'integer',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // Scopes
    public function scopeForPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeForFixture($query, $fixtureId)
    {
        return $query->where('fixture_id', $fixtureId);
    }

    // Get player contribution (goals + assists)
    public function getGoalContributionAttribute()
    {
        return ($this->goals ?? 0) + ($this->assists ?? 0);
    }

    // Get rating or a default if null
    public function getRatingFormattedAttribute()
    {
        return $this->rating ? number_format($this->rating, 1) : 'N/A';
    }
}