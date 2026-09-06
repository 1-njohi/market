<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = [
        'name'
    ];

    public function Odds()
    {
        return $this->hasMany(Odd::class);
    }
}
