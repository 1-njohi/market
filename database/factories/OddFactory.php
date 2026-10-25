<?php

namespace Database\Factories;

use App\Models\Odd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Odd>
 */
class OddFactory extends Factory
{
    protected $model = Odd::class;

    public function definition(): array
    {
        return [
            'value'  => 'Home',
            'odd'    => 2.00,
            'status' => 'pending',
            // fixture_id and market_id are set by the caller — each test
            // pairs them with a freshly created Fixture and Market.
        ];
    }
}