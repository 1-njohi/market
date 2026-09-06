<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'flag'
    ];

    public function leagues() {
        return $this -> hasMany(League::class);
    }
}
