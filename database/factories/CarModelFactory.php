<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CarCategory;
use App\Enums\FuelType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CarModel>
 */
class CarModelFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = 'Tiggo '.fake()->unique()->numberBetween(1, 999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'category' => fake()->randomElement(CarCategory::cases()),
            'fuel_type' => FuelType::Ice,
            'series_code' => Str::slug($name, '_'),
            'price_start' => fake()->numberBetween(200, 700) * 1_000_000,
            'short_description' => fake()->sentence(8),
            'description' => fake()->paragraph(),
            'specs' => [
                'mesin' => '1.5 TCI',
                'transmisi' => 'CVT',
                'kapasitas' => '5 penumpang',
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function electric(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => CarCategory::Ev,
            'fuel_type' => FuelType::Ev,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
