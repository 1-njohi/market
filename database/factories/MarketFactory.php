<?php

namespace Database\Factories;

use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Market>
 */
class MarketFactory extends Factory
{
    protected $model = Market::class;

    public function definition(): array
    {
        return [
            // markets.id is NOT auto-incrementing (see the migration:
            // unsignedBigInteger('id')->primary()). The factory must
            // supply an id. unique() is shared across the container's
            // Faker instance, so concurrent factory calls within a test
            // won't collide.
            'id'   => $this->faker->unique()->numberBetween(1, 999_999_999),

            'name' => 'Match Winner',
        ];
    }
}