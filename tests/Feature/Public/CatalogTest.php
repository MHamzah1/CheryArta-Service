<?php

declare(strict_types=1);

use App\Enums\CarCategory;
use App\Enums\FuelType;
use App\Models\CarModel;
use App\Models\CarModelImage;
use App\Models\CarModelVariant;

/*
| Katalog publik (roadmap 1.6.2).
|
| Sumber datanya A4. Yang paling perlu dijaga: model yang dinonaktifkan admin
| benar-benar hilang dari halaman publik, termasuk lewat URL langsung.
*/

it('menampilkan hanya model aktif di daftar katalog', function () {
    CarModel::factory()->create(['name' => 'Tiggo 8 Pro']);
    CarModel::factory()->inactive()->create(['name' => 'Model Ditarik']);

    $this->get(route('public.catalog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('katalog/index')
            ->has('carModels.data', 1)
            ->where('carModels.data.0.name', 'Tiggo 8 Pro'));
});

it('menyaring katalog berdasarkan kategori', function () {
    CarModel::factory()->create(['name' => 'Tiggo SUV', 'category' => CarCategory::Suv]);
    CarModel::factory()->create(['name' => 'Arrizo Sedan', 'category' => CarCategory::Sedan]);

    $this->get(route('public.catalog.index', ['kategori' => 'sedan']))
        ->assertInertia(fn ($page) => $page
            ->has('carModels.data', 1)
            ->where('carModels.data.0.name', 'Arrizo Sedan')
            ->where('filters.kategori', 'sedan'));
});

it('menyaring katalog berdasarkan bahan bakar', function () {
    CarModel::factory()->create(['name' => 'Tiggo Bensin', 'fuel_type' => FuelType::Ice]);
    CarModel::factory()->electric()->create(['name' => 'Omoda Listrik']);

    $this->get(route('public.catalog.index', ['bahan_bakar' => 'ev']))
        ->assertInertia(fn ($page) => $page
            ->has('carModels.data', 1)
            ->where('carModels.data.0.name', 'Omoda Listrik'));
});

it('mengabaikan nilai saringan yang tidak dikenal alih-alih menghasilkan galat', function () {
    // URL katalog sering disalin-tempel orang. Nilai asing dibuang diam-diam;
    // halaman kosong dengan pesan galat bukan jawaban yang berguna.
    CarModel::factory()->create(['name' => 'Tiggo 8 Pro']);

    $this->get(route('public.catalog.index', ['kategori' => 'pesawat']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('carModels.data', 1)
            ->where('filters.kategori', null));
});

it('membuka detail model beserta varian, galeri, dan spesifikasinya', function () {
    $carModel = CarModel::factory()->create(['name' => 'Tiggo 8 Pro', 'slug' => 'tiggo-8-pro']);
    CarModelVariant::factory()->create(['car_model_id' => $carModel->id, 'name' => 'Premium']);
    CarModelImage::factory()->create(['car_model_id' => $carModel->id, 'is_primary' => true]);

    $this->get(route('public.catalog.show', 'tiggo-8-pro'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('katalog/show')
            ->where('carModel.name', 'Tiggo 8 Pro')
            ->has('variants', 1)
            ->where('variants.0.name', 'Premium')
            ->has('images', 1)
            ->has('carModel.specs'));
});

it('tidak menampilkan varian yang dinonaktifkan', function () {
    $carModel = CarModel::factory()->create(['slug' => 'tiggo-8-pro']);
    CarModelVariant::factory()->create(['car_model_id' => $carModel->id, 'name' => 'Premium']);
    CarModelVariant::factory()->create([
        'car_model_id' => $carModel->id,
        'name' => 'Varian Lama',
        'is_active' => false,
    ]);

    $this->get(route('public.catalog.show', 'tiggo-8-pro'))
        ->assertInertia(fn ($page) => $page
            ->has('variants', 1)
            ->where('variants.0.name', 'Premium'));
});

it('menolak membuka model yang dinonaktifkan lewat URL langsung', function () {
    CarModel::factory()->inactive()->create(['slug' => 'model-ditarik']);

    $this->get(route('public.catalog.show', 'model-ditarik'))->assertNotFound();
});

it('tidak menawarkan model nonaktif sebagai model lain', function () {
    CarModel::factory()->create(['slug' => 'tiggo-8-pro']);
    CarModel::factory()->create(['name' => 'Omoda 5']);
    CarModel::factory()->inactive()->create(['name' => 'Model Ditarik']);

    $this->get(route('public.catalog.show', 'tiggo-8-pro'))
        ->assertInertia(fn ($page) => $page
            ->has('relatedModels', 1)
            ->where('relatedModels.0.name', 'Omoda 5'));
});
