<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    public $incrementing = false;
    protected $fillable = [
        'id',
        'name'
    ];

    public function Odds()
    {
        return $this->hasMany(Odd::class);
    }
}
