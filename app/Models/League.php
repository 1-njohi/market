<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    protected $fillable = [
        'country_id',
        'sport_id',
        'id_on_api',
        'name',
        'type',
        'logo',
        'priority'
    ];
    public function Country() {
        return $this -> belongsTo(Country::class);
    }

    public function Fixtures() {
        return $this -> hasMany(Fixture::class);
    }

    public function Sport(){
        return $this -> belongsTo(Sport::class);
    }
}
