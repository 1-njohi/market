<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odd extends Model
{
    protected $fillable = [
        'fixture_id',
        'market_id',
        'value',
        'odd',
        'status' //pending, loser, winner
    ];

    public function Fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function Market()
    {
        return $this->belongsTo(Market::class);
    }

    public function Betslips()
    {
        return $this->belongsToMany(Betslip::class, 'betslip_odd')
                    ->withPivot('status', 'odd_value_at_time')
                    ->withTimestamps();
    }
}
