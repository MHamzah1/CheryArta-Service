<?php

declare(strict_types=1);

use App\Models\CarModel;
use App\Models\Facility;
use App\Services\FakeImageUploader;
use App\Services\ImageUploader;
use Database\Seeders\AssetImageSeeder;
use Database\Seeders\CarModelSeeder;
use Database\Seeders\FacilitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Uji berjalan dengan FakeImageUploader (dipasang AppServiceProvider saat
 * `runningUnitTests()`), sehingga tidak ada satu pun panggilan jaringan ke
 * Cloudinary — tetapi jalur kodenya persis sama dengan jalur sungguhan.
 */
beforeEach(function () {
    $this->seed(CarModelSeeder::class);
    $this->seed(FacilitySeeder::class);
});

it('memasang satu gambar utama untuk setiap model katalog', function () {
    $this->seed(AssetImageSeeder::class);

    $tiggoCross = CarModel::query()->where('slug', 'tiggo-cross')->sole();

    expect($tiggoCross->images()->count())->toBe(1);
    expect($tiggoCross->images()->first()->is_primary)->toBeTrue();
});

it('memberi alt bermakna pada gambar katalog', function () {
    $this->seed(AssetImageSeeder::class);

    $gambar = CarModel::query()->where('slug', 'tiggo-8-pro')->sole()->images()->sole();

    expect($gambar->alt)->toBe('Foto mobil Tiggo 8 Pro');
});

it('mengisi kolom gambar seluruh fasilitas', function () {
    $this->seed(AssetImageSeeder::class);

    expect(Facility::query()->whereNull('image_public_id')->count())->toBe(0);
});

it('tidak menggandakan gambar saat dijalankan ulang', function () {
    $this->seed(AssetImageSeeder::class);
    $sebelum = CarModel::query()->where('slug', 'tiggo-cross')->sole()->images()->sole()->public_id;

    $this->seed(AssetImageSeeder::class);

    $gambar = CarModel::query()->where('slug', 'tiggo-cross')->sole()->images;

    expect($gambar)->toHaveCount(1)
        ->and($gambar->first()->public_id)->toBe($sebelum);
});

it('tidak mengunggah ulang gambar yang sudah terpasang', function () {
    $fake = new FakeImageUploader;
    $this->app->instance(ImageUploader::class, $fake);

    $this->seed(AssetImageSeeder::class);
    $jumlahUnggahan = count($fake->uploads());

    $this->seed(AssetImageSeeder::class);

    expect($fake->uploads())->toHaveCount($jumlahUnggahan);
});

it('mengunggah ke folder Cloudinary yang benar per jenis aset', function () {
    $fake = new FakeImageUploader;
    $this->app->instance(ImageUploader::class, $fake);

    $this->seed(AssetImageSeeder::class);

    $folder = array_unique(array_column($fake->uploads(), 'folder'));

    expect($folder)->toEqualCanonicalizing([
        config('cloudinary.folders.car_models'),
        config('cloudinary.folders.facilities'),
    ]);
});

it('melewati seluruh gambar tanpa galat bila Cloudinary belum disetel', function () {
    // Meniru keadaan CI: CLOUDINARY_URL kosong sehingga container melempar.
    $this->app->bind(ImageUploader::class, function (): never {
        throw new RuntimeException('CLOUDINARY_URL belum diisi.');
    });

    $this->seed(AssetImageSeeder::class);

    expect(Facility::query()->whereNotNull('image_public_id')->count())->toBe(0);
});
