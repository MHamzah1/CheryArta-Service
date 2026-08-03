<?php

declare(strict_types=1);

use App\Models\CarModel;
use App\Models\CarModelImage;
use App\Models\CarModelVariant;
use App\Models\Vehicle;
use App\Services\FakeImageUploader;
use App\Services\ImageUploader;
use Illuminate\Http\UploadedFile;

/*
| A4 — Katalog Mobil (docs/07-modul-admin.md §A4), roadmap 1.3.2, 1.3.3,
| 1.3.5, dan 1.3.7.
*/

/** @return array<string, mixed> */
function dataModel(array $timpa = []): array
{
    return array_merge([
        'name' => 'Tiggo 8 Pro Max',
        'slug' => '',
        'category' => 'suv',
        'fuel_type' => 'ice',
        'series_code' => 'tiggo_8',
        'price_start' => 500000000,
        'short_description' => 'SUV tujuh penumpang untuk keluarga besar.',
        'description' => 'Keterangan panjang.',
        'specs' => [
            ['key' => 'Mesin', 'value' => '1.6 TGDI'],
            ['key' => 'Transmisi', 'value' => '7DCT'],
        ],
        'is_active' => true,
        'sort_order' => 3,
    ], $timpa);
}

/** @return list<array{0: string, 1: string}> */
function ruteKatalog(CarModel $carModel, CarModelVariant $variant): array
{
    return [
        ['get', route('admin.car-models.index')],
        ['get', route('admin.car-models.create')],
        ['post', route('admin.car-models.store')],
        ['get', route('admin.car-models.edit', $carModel)],
        ['put', route('admin.car-models.update', $carModel)],
        ['delete', route('admin.car-models.destroy', $carModel)],
        ['delete', route('admin.car-models.brochure.destroy', $carModel)],
        ['post', route('admin.car-models.variants.store', $carModel)],
        ['put', route('admin.car-models.variants.update', [$carModel, $variant])],
        ['delete', route('admin.car-models.variants.destroy', [$carModel, $variant])],
    ];
}

it('menolak service advisor di seluruh rute katalog', function () {
    $advisor = serviceAdvisor();
    $carModel = CarModel::factory()->create();
    $variant = CarModelVariant::factory()->create(['car_model_id' => $carModel->id]);

    foreach (ruteKatalog($carModel, $variant) as [$method, $url]) {
        $this->actingAs($advisor)->$method($url)->assertForbidden();
    }
});

it('menolak customer di seluruh rute katalog', function () {
    $customer = customer();
    $carModel = CarModel::factory()->create();
    $variant = CarModelVariant::factory()->create(['car_model_id' => $carModel->id]);

    foreach (ruteKatalog($carModel, $variant) as [$method, $url]) {
        $this->actingAs($customer)->$method($url)->assertForbidden();
    }
});

it('menampilkan daftar katalog kepada super admin', function () {
    $carModel = CarModel::factory()->create(['name' => 'Omoda 5 GT']);

    $this->actingAs(superAdmin())
        ->get(route('admin.car-models.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/katalog/index')
            ->has('carModels.data', 1)
            ->where('carModels.data.0.name', $carModel->name));
});

it('menampilkan form tambah model beserta pilihannya', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.car-models.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/katalog/create')
            ->has('categories')
            ->has('fuelTypes')
            ->has('seriesOptions'));
});

it('menampilkan halaman ubah beserta varian dan galerinya', function () {
    $carModel = CarModel::factory()->create();
    CarModelVariant::factory()->create(['car_model_id' => $carModel->id]);
    CarModelImage::factory()->create(['car_model_id' => $carModel->id]);

    $this->actingAs(superAdmin())
        ->get(route('admin.car-models.edit', $carModel))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/katalog/edit')
            ->where('carModel.id', $carModel->id)
            ->has('variants', 1)
            // Galeri admin memakai versi kecil, bukan gambar penuh.
            ->has('images', 1, fn ($image) => $image->where('url', fn (string $url) => str_contains($url, 'w_400'))->etc()));
});

it('menurunkan slug dari nama saat slug dikosongkan', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.store'), dataModel(['slug' => '']));

    expect(CarModel::where('slug', 'tiggo-8-pro-max')->exists())->toBeTrue();
});

it('menambahkan akhiran angka bila slug turunan sudah terpakai', function () {
    CarModel::factory()->create(['slug' => 'tiggo-8-pro-max']);

    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.store'), dataModel(['slug' => '']));

    expect(CarModel::where('slug', 'tiggo-8-pro-max-2')->exists())->toBeTrue();
});

it('menolak slug ketikan yang sudah dipakai model lain', function () {
    CarModel::factory()->create(['slug' => 'tiggo-8-pro']);

    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.store'), dataModel(['slug' => 'tiggo-8-pro']))
        ->assertSessionHasErrors('slug');
});

it('menyimpan spesifikasi sebagai objek berurutan', function () {
    $this->actingAs(superAdmin())->post(route('admin.car-models.store'), dataModel());

    expect(CarModel::where('slug', 'tiggo-8-pro-max')->firstOrFail()->specs)
        ->toBe(['Mesin' => '1.6 TGDI', 'Transmisi' => '7DCT']);
});

it('menolak nama spesifikasi yang kembar', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.store'), dataModel([
            'specs' => [
                ['key' => 'Mesin', 'value' => '1.6 TGDI'],
                ['key' => 'mesin', 'value' => '2.0 TGDI'],
            ],
        ]))
        ->assertSessionHasErrors('specs.0.key');
});

it('menerima urutan tampil dan harga mulai yang dikosongkan', function () {
    // Keduanya sampai ke server sebagai null; hanya price_start yang nullable
    // di skema, sedangkan sort_order NOT NULL berdefault 0.
    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.store'), dataModel(['sort_order' => '', 'price_start' => '']))
        ->assertSessionHasNoErrors();

    $carModel = CarModel::where('slug', 'tiggo-8-pro-max')->firstOrFail();

    expect($carModel->sort_order)->toBe(0)
        ->and($carModel->price_start)->toBeNull();
});

it('mengalihkan ke halaman ubah setelah model dibuat', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.store'), dataModel())
        ->assertRedirect(route('admin.car-models.edit', CarModel::where('slug', 'tiggo-8-pro-max')->firstOrFail()));
});

it('mengunggah brosur PDF lewat form model', function () {
    /** @var FakeImageUploader $uploader */
    $uploader = app(ImageUploader::class);

    $this->actingAs(superAdmin())->post(route('admin.car-models.store'), dataModel([
        'brochure' => UploadedFile::fake()->create('brosur.pdf', 300, 'application/pdf'),
    ]));

    $carModel = CarModel::where('slug', 'tiggo-8-pro-max')->firstOrFail();

    expect($carModel->brochure_public_id)->not->toBeNull()
        ->and($carModel->brochure_url)->toContain($carModel->brochure_public_id)
        ->and($uploader->uploads())->toHaveCount(1);
});

it('menolak brosur yang melampaui 5 MB', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.store'), dataModel([
            'brochure' => UploadedFile::fake()->create('brosur.pdf', 5121, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('brochure');
});

it('menolak brosur yang bukan PDF meski berekstensi pdf', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.store'), dataModel([
            'brochure' => UploadedFile::fake()->create('brosur.pdf', 100, 'text/plain'),
        ]))
        ->assertSessionHasErrors('brochure');
});

it('membuang brosur beserta berkasnya', function () {
    /** @var FakeImageUploader $uploader */
    $uploader = app(ImageUploader::class);

    $carModel = CarModel::factory()->create([
        'brochure_public_id' => 'chery-arta/brochures/lama',
        'brochure_url' => 'https://res.cloudinary.com/fake/raw/upload/v1/chery-arta/brochures/lama.pdf',
    ]);

    $this->actingAs(superAdmin())->delete(route('admin.car-models.brochure.destroy', $carModel));

    expect($carModel->refresh()->brochure_public_id)->toBeNull()
        ->and($uploader->deletedPublicIds())->toBe(['chery-arta/brochures/lama']);
});

it('memperbarui model', function () {
    $carModel = CarModel::factory()->create(['name' => 'Nama Lama', 'slug' => 'nama-lama']);

    $this->actingAs(superAdmin())
        ->put(route('admin.car-models.update', $carModel), dataModel([
            'name' => 'Nama Baru',
            'slug' => 'nama-baru',
        ]))
        ->assertRedirect(route('admin.car-models.edit', $carModel));

    expect($carModel->refresh()->name)->toBe('Nama Baru')
        ->and($carModel->slug)->toBe('nama-baru');
});

it('menerima form model multipart lewat penimpaan metode PUT', function () {
    // Jalur inilah yang dipakai halaman ubah: brosur membuat pengirimannya
    // multipart, dan multipart tidak mengenal PUT.
    $carModel = CarModel::factory()->create(['name' => 'Nama Lama', 'slug' => 'nama-lama']);

    $this->actingAs(superAdmin())
        ->post(
            route('admin.car-models.update', $carModel),
            dataModel(['name' => 'Nama Baru', 'slug' => 'nama-baru']),
            ['X-HTTP-Method-Override' => 'PUT'],
        )
        ->assertRedirect(route('admin.car-models.edit', $carModel));

    expect($carModel->refresh()->name)->toBe('Nama Baru');
});

it('menghapus model yang belum dipakai kendaraan', function () {
    $carModel = CarModel::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('admin.car-models.destroy', $carModel))
        ->assertRedirect(route('admin.car-models.index'));

    expect(CarModel::whereKey($carModel->id)->exists())->toBeFalse();
});

it('menolak menghapus model yang sudah dipakai kendaraan pelanggan', function () {
    $carModel = CarModel::factory()->create();
    Vehicle::factory()->create(['car_model_id' => $carModel->id, 'user_id' => customer()->id]);

    $this->actingAs(superAdmin())
        ->delete(route('admin.car-models.destroy', $carModel))
        ->assertSessionHas('error');

    expect(CarModel::whereKey($carModel->id)->exists())->toBeTrue();
});

it('tetap melindungi model yang kendaraannya sudah dihapus lunak', function () {
    $carModel = CarModel::factory()->create();
    $vehicle = Vehicle::factory()->create(['car_model_id' => $carModel->id, 'user_id' => customer()->id]);
    $vehicle->delete();

    $this->actingAs(superAdmin())
        ->delete(route('admin.car-models.destroy', $carModel))
        ->assertSessionHas('error');

    expect(CarModel::whereKey($carModel->id)->exists())->toBeTrue();
});

it('menambahkan varian ke dalam model', function () {
    $carModel = CarModel::factory()->create();

    $this->actingAs(superAdmin())->post(route('admin.car-models.variants.store', $carModel), [
        'name' => 'Luxury',
        'price' => 620000000,
        'specs' => [['key' => 'Pelek', 'value' => '19 inci']],
        'is_active' => true,
    ]);

    $variant = $carModel->variants()->firstOrFail();

    expect($variant->name)->toBe('Luxury')
        ->and($variant->specs)->toBe(['Pelek' => '19 inci']);
});

it('menolak nama varian yang kembar dalam satu model', function () {
    $carModel = CarModel::factory()->create();
    CarModelVariant::factory()->create(['car_model_id' => $carModel->id, 'name' => 'Premium']);

    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.variants.store', $carModel), ['name' => 'Premium'])
        ->assertSessionHasErrors('name');
});

it('menerima nama varian yang sama pada model berbeda', function () {
    $satu = CarModel::factory()->create();
    $dua = CarModel::factory()->create();
    CarModelVariant::factory()->create(['car_model_id' => $satu->id, 'name' => 'Premium']);

    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.variants.store', $dua), ['name' => 'Premium'])
        ->assertSessionHasNoErrors();
});

it('memperbarui varian', function () {
    $carModel = CarModel::factory()->create();
    $variant = CarModelVariant::factory()->create(['car_model_id' => $carModel->id, 'name' => 'Comfort']);

    $this->actingAs(superAdmin())->put(route('admin.car-models.variants.update', [$carModel, $variant]), [
        'name' => 'Comfort Plus',
        'is_active' => false,
    ]);

    expect($variant->refresh()->name)->toBe('Comfort Plus')
        ->and($variant->is_active)->toBeFalse();
});

it('menghapus varian', function () {
    $carModel = CarModel::factory()->create();
    $variant = CarModelVariant::factory()->create(['car_model_id' => $carModel->id]);

    $this->actingAs(superAdmin())->delete(route('admin.car-models.variants.destroy', [$carModel, $variant]));

    expect(CarModelVariant::whereKey($variant->id)->exists())->toBeFalse();
});

it('menolak menyunting varian milik model lain', function () {
    $carModel = CarModel::factory()->create();
    $variantOrangLain = CarModelVariant::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('admin.car-models.variants.destroy', [$carModel, $variantOrangLain]))
        ->assertNotFound();

    expect(CarModelVariant::whereKey($variantOrangLain->id)->exists())->toBeTrue();
});
