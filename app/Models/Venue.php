<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $fillable = [
        'id_on_api',
        'name',
        'city',
    ];

    public function fixtures()
    {
        return $this->hasMany(Fixture::class);
    }
}