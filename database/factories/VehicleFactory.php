<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'car_model_id' => null,
            'model_name_manual' => 'Tiggo 8 Pro',
            'plate_prefix' => fake()->randomElement(['B', 'D', 'F', 'AB', 'BK']),
            'plate_number' => (string) fake()->numberBetween(1, 9999),
            'plate_suffix' => fake()->lexify('???'),
            'year' => fake()->numberBetween(2018, 2026),
            'color' => fake()->randomElement(['Hitam', 'Putih', 'Silver', 'Merah']),
            'is_primary' => false,
        ];
    }

    /** Plat berprefiks satu huruf — bentuk yang ditolak sistem lama (temuan B1). */
    public function singleLetterPrefix(): static
    {
        return $this->state(fn (array $attributes) => [
            'plate_prefix' => 'B',
        ]);
    }

    /** Plat berprefiks dua huruf. */
    public function doubleLetterPrefix(): static
    {
        return $this->state(fn (array $attributes) => [
            'plate_prefix' => 'AB',
        ]);
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
