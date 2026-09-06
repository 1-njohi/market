<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixtureGoals extends Model
{
    protected $fillable = [
        'fixture_id',
        'home',
        'away',
    ];

    public function Fixture() {
        return $this -> belongsTo(Fixture::class);
    }
}
