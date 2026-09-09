<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coach extends Model
{
    protected $fillable = [
        'id_on_api',
        'name',
        'photo',
    ];

    public function lineups()
    {
        return $this->hasMany(FixtureLineup::class);
    }
}