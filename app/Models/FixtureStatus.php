<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixtureStatus extends Model
{
    protected $fillable = [
        'fixture_id',
        'long',
        'short',
        'elapsed'
    ];

    public function Fixture()
    {
        return $this->belongsTo(Fixture::class);
    }
}
