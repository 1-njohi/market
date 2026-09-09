<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
     protected $fillable = [
        'id_on_api',
        'name',
        'country',
        'country_id',
        'logo',
        'flag',
        'season',
        'round',
        'standings',
        'priority'
    ];

    protected $casts = [
        'standings' => 'boolean',
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
