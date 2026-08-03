<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CarCategory;
use App\Enums\FuelType;
use App\Models\CarModel;
use Illuminate\Database\Seeder;

/**
 * Katalog produk Chery (docs/04-skema-database.md §4.4).
 *
 * Dua hal yang sengaja dikosongkan, bukan ditebak:
 *
 * 1. `price_start` null — skema membacanya sebagai "hubungi kami". Seeder ini
 *    berjalan di produksi, jadi harga karangan akan tampil di landing page
 *    sebagai harga sungguhan. Diisi admin lewat layar F1.3.
 * 2. `series_code` hanya diisi untuk seri yang jelas terdaftar di program
 *    Free Maintenance sistem lama (tiggo_5x_cross, tiggo_8, ev, csh). Model
 *    lain dibiarkan null agar tidak salah mengaitkan paket gratis.
 *
 * Gambar tidak ikut dibuat: `car_model_images` mewajibkan public_id dan url,
 * yang baru ada setelah unggahan Cloudinary di F1.3.
 *
 * Seeder produksi — idempoten, aman dijalankan ulang.
 */
class CarModelSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->models() as $sortOrder => $model) {
            $variants = $model['variants'];
            unset($model['variants']);

            $carModel = CarModel::updateOrCreate(
                ['slug' => $model['slug']],
                [...$model, 'sort_order' => $sortOrder, 'is_active' => true],
            );

            foreach ($variants as $variantOrder => $variantName) {
                $carModel->variants()->updateOrCreate(
                    ['name' => $variantName],
                    ['sort_order' => $variantOrder, 'is_active' => true],
                );
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function models(): array
    {
        return [
            [
                'name' => 'Tiggo 5X',
                'slug' => 'tiggo-5x',
                'category' => CarCategory::Suv,
                'fuel_type' => FuelType::Ice,
                'series_code' => 'tiggo_5x_cross',
                'price_start' => null,
                'short_description' => 'SUV kompak untuk keseharian dalam kota.',
                'variants' => ['Comfort', 'Premium'],
            ],
            [
                'name' => 'Tiggo Cross',
                'slug' => 'tiggo-cross',
                'category' => CarCategory::Suv,
                'fuel_type' => FuelType::Ice,
                'series_code' => 'tiggo_5x_cross',
                'price_start' => null,
                'short_description' => 'SUV kompak bergaya crossover dengan fitur berkendara modern.',
                'variants' => ['Comfort', 'Premium'],
            ],
            [
                'name' => 'Tiggo 7 Pro',
                'slug' => 'tiggo-7-pro',
                'category' => CarCategory::Suv,
                'fuel_type' => FuelType::Ice,
                'series_code' => null,
                'price_start' => null,
                'short_description' => 'SUV lima penumpang dengan kabin lapang dan mesin turbo.',
                'variants' => ['Premium', 'Luxury'],
            ],
            [
                'name' => 'Tiggo 8 Pro',
                'slug' => 'tiggo-8-pro',
                'category' => CarCategory::Suv,
                'fuel_type' => FuelType::Ice,
                'series_code' => 'tiggo_8',
                'price_start' => null,
                'short_description' => 'SUV tujuh penumpang untuk keluarga besar.',
                'variants' => ['Premium', 'Luxury'],
            ],
            [
                'name' => 'Omoda 5',
                'slug' => 'omoda-5',
                'category' => CarCategory::Suv,
                'fuel_type' => FuelType::Ice,
                'series_code' => null,
                'price_start' => null,
                'short_description' => 'Crossover bergaya muda dengan desain futuristik.',
                'variants' => ['RZ', 'Z'],
            ],
            [
                'name' => 'Omoda E5',
                'slug' => 'omoda-e5',
                'category' => CarCategory::Ev,
                'fuel_type' => FuelType::Ev,
                'series_code' => 'ev',
                'price_start' => null,
                'short_description' => 'Crossover listrik tanpa emisi untuk perjalanan harian.',
                'variants' => ['Standard'],
            ],
            [
                'name' => 'Jaecoo J7',
                'slug' => 'jaecoo-j7',
                'category' => CarCategory::Suv,
                'fuel_type' => FuelType::Ice,
                'series_code' => null,
                'price_start' => null,
                'short_description' => 'SUV bergaya tegas dengan kesan berkendara premium.',
                'variants' => ['Standard', 'Premium'],
            ],
        ];
    }
}
