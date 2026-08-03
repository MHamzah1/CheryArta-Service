<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CarModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CarModelVariant>
 */
class CarModelVariantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'car_model_id' => CarModel::factory(),
            'name' => fake()->randomElement(['Premium', 'Luxury', 'Comfort']),
            'price' => fake()->numberBetween(200, 700) * 1_000_000,
            'specs' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
