<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CarModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CarModelImage>
 */
class CarModelImageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $publicId = 'chery-arta/car-models/'.Str::uuid();

        return [
            'car_model_id' => CarModel::factory(),
            'public_id' => $publicId,
            'url' => 'https://res.cloudinary.com/demo/image/upload/'.$publicId.'.webp',
            'alt' => fake()->sentence(4),
            'is_primary' => false,
            'sort_order' => 0,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
