<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixtureLineup extends Model
{
    protected $fillable = [
        'fixture_id',
        'team_id',
        'coach_id',
        'formation',
        'startXI',
        'substitutes',
    ];

    protected $casts = [
        'startXI' => 'array',
        'substitutes' => 'array',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }
}