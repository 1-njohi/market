<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Market extends Model
{

    use HasFactory;
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
