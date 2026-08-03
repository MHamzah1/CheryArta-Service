<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServicePackageCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServicePackage>
 */
class ServicePackageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = 'Servis '.fake()->unique()->numberBetween(1000, 90000).' KM';

        return [
            'code' => Str::slug($name, '_'),
            'name' => $name,
            'category' => ServicePackageCategory::FreeMaintenance,
            'description' => fake()->sentence(),
            'applicable_series' => null,
            'estimated_duration_minutes' => 60,
            'price' => 0,
            'is_free' => true,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ServicePackageCategory::Other,
            'price' => fake()->numberBetween(200, 900) * 1_000,
            'is_free' => false,
        ]);
    }

    /** @param  list<string>  $series */
    public function forSeries(array $series): static
    {
        return $this->state(fn (array $attributes) => [
            'applicable_series' => $series,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
