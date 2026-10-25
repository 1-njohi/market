<?php

namespace Database\Factories;

use App\Models\Fixture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fixture>
 */
class FixtureFactory extends Factory
{
    protected $model = Fixture::class;

    public function definition(): array
    {
        return [
            // Required: unique external id. Everything else on the
            // fixtures table is nullable or defaulted.
            'id_on_api'    => $this->faker->unique()->numberBetween(1, 999_999_999),

            // The one field matchday inference and the lock calculator
            // both read. Tests override this per-case.
            'league_id'    => 39,
            'date'         => now()->addDay(),

            'status_short' => 'NS',
            'home_winner'  => false,
            'settled'      => false,
        ];
    }
}