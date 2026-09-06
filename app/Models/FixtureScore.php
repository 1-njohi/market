<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixtureScore extends Model
{
    protected $fillable = [
        'fixture_id',
        'halftime_home',
        'halftime_away',
        'fulltime_home',
        'fulltime_away',
        'extratime_home',
        'extratime_away',
        'penalty_home',
        'penalty_away'
    ];

    public function Fixture()
    {
        return $this->belongsTo(Fixture::class);
    }
}
