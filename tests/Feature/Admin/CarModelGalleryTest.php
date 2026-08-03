<?php

declare(strict_types=1);

use App\Models\CarModel;
use App\Models\CarModelImage;
use App\Services\FakeImageUploader;
use App\Services\ImageUploader;
use Illuminate\Http\UploadedFile;

/*
| Galeri katalog lewat Cloudinary — roadmap 1.3.4 & 1.3.7.
|
| Tidak ada uji di berkas ini yang menembak jaringan: container mengganti
| ImageUploader dengan FakeImageUploader selama pengujian.
*/

/** @return list<array{0: string, 1: string}> */
function ruteGaleri(CarModel $carModel, CarModelImage $image): array
{
    return [
        ['post', route('admin.car-models.images.store', $carModel)],
        ['put', route('admin.car-models.images.reorder', $carModel)],
        ['put', route('admin.car-models.images.update', [$carModel, $image])],
        ['put', route('admin.car-models.images.primary', [$carModel, $image])],
        ['delete', route('admin.car-models.images.destroy', [$carModel, $image])],
    ];
}

it('menolak service advisor di seluruh rute galeri', function () {
    $advisor = serviceAdvisor();
    $carModel = CarModel::factory()->create();
    $image = CarModelImage::factory()->create(['car_model_id' => $carModel->id]);

    foreach (ruteGaleri($carModel, $image) as [$method, $url]) {
        $this->actingAs($advisor)->$method($url)->assertForbidden();
    }
});

it('mengunggah beberapa gambar sekaligus beserta teks altnya', function () {
    $carModel = CarModel::factory()->create();

    $this->actingAs(superAdmin())->post(route('admin.car-models.images.store', $carModel), [
        'images' => [
            UploadedFile::fake()->create('depan.jpg', 300, 'image/jpeg'),
            UploadedFile::fake()->create('samping.webp', 300, 'image/webp'),
        ],
        'alts' => ['Tampak depan', 'Tampak samping'],
    ])->assertSessionHasNoErrors();

    expect($carModel->images()->count())->toBe(2)
        ->and($carModel->images()->pluck('alt')->all())->toBe(['Tampak depan', 'Tampak samping']);
});

it('membuang nama berkas asli saat menyimpan gambar', function () {
    $carModel = CarModel::factory()->create();

    $this->actingAs(superAdmin())->post(route('admin.car-models.images.store', $carModel), [
        'images' => [UploadedFile::fake()->create('Foto Pribadi.jpg', 100, 'image/jpeg')],
        'alts' => ['Tampak depan'],
    ]);

    expect($carModel->images()->firstOrFail()->public_id)->not->toContain('Pribadi');
});

it('menjadikan gambar pertama sebagai gambar utama', function () {
    $carModel = CarModel::factory()->create();

    $this->actingAs(superAdmin())->post(route('admin.car-models.images.store', $carModel), [
        'images' => [
            UploadedFile::fake()->create('satu.jpg', 100, 'image/jpeg'),
            UploadedFile::fake()->create('dua.jpg', 100, 'image/jpeg'),
        ],
        'alts' => ['Satu', 'Dua'],
    ]);

    expect($carModel->images()->where('is_primary', true)->count())->toBe(1)
        ->and($carModel->images()->orderBy('sort_order')->firstOrFail()->is_primary)->toBeTrue();
});

it('mewajibkan teks alt untuk setiap gambar', function () {
    $carModel = CarModel::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.images.store', $carModel), [
            'images' => [UploadedFile::fake()->create('depan.jpg', 100, 'image/jpeg')],
            'alts' => [''],
        ])
        ->assertSessionHasErrors('alts.0');

    expect($carModel->images()->count())->toBe(0);
});

it('menolak unggahan tanpa teks alt sama sekali', function () {
    $carModel = CarModel::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.images.store', $carModel), [
            'images' => [UploadedFile::fake()->create('depan.jpg', 100, 'image/jpeg')],
        ])
        ->assertSessionHasErrors('alts');
});

it('menolak gambar yang melampaui 2 MB', function () {
    $carModel = CarModel::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.images.store', $carModel), [
            'images' => [UploadedFile::fake()->create('besar.jpg', 2049, 'image/jpeg')],
            'alts' => ['Tampak depan'],
        ])
        ->assertSessionHasErrors('images.0');

    expect($carModel->images()->count())->toBe(0);
});

it('menolak berkas yang menyamar sebagai gambar', function () {
    $carModel = CarModel::factory()->create();

    // Ekstensi .jpg, isinya bukan gambar — validasi memeriksa isi berkas,
    // bukan namanya (.claude/rules/50-keamanan.md).
    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.images.store', $carModel), [
            'images' => [UploadedFile::fake()->create('jahat.jpg', 50, 'text/plain')],
            'alts' => ['Tampak depan'],
        ])
        ->assertSessionHasErrors('images.0');

    expect($carModel->images()->count())->toBe(0);
});

it('menolak unggahan lebih dari sepuluh gambar sekaligus', function () {
    $carModel = CarModel::factory()->create();

    $gambar = [];
    $alt = [];

    for ($i = 0; $i < 11; $i++) {
        $gambar[] = UploadedFile::fake()->create("foto-{$i}.jpg", 50, 'image/jpeg');
        $alt[] = "Foto {$i}";
    }

    $this->actingAs(superAdmin())
        ->post(route('admin.car-models.images.store', $carModel), ['images' => $gambar, 'alts' => $alt])
        ->assertSessionHasErrors('images');
});

it('memperbarui teks alt gambar', function () {
    $carModel = CarModel::factory()->create();
    $image = CarModelImage::factory()->create(['car_model_id' => $carModel->id, 'alt' => 'Lama']);

    $this->actingAs(superAdmin())
        ->put(route('admin.car-models.images.update', [$carModel, $image]), ['alt' => 'Tampak depan warna putih']);

    expect($image->refresh()->alt)->toBe('Tampak depan warna putih');
});

it('menolak teks alt kosong saat disunting', function () {
    $carModel = CarModel::factory()->create();
    $image = CarModelImage::factory()->create(['car_model_id' => $carModel->id, 'alt' => 'Lama']);

    $this->actingAs(superAdmin())
        ->put(route('admin.car-models.images.update', [$carModel, $image]), ['alt' => ''])
        ->assertSessionHasErrors('alt');

    expect($image->refresh()->alt)->toBe('Lama');
});

it('menyisakan tepat satu gambar utama saat gambar lain ditandai utama', function () {
    $carModel = CarModel::factory()->create();
    $lama = CarModelImage::factory()->primary()->create(['car_model_id' => $carModel->id]);
    $baru = CarModelImage::factory()->create(['car_model_id' => $carModel->id]);

    $this->actingAs(superAdmin())->put(route('admin.car-models.images.primary', [$carModel, $baru]));

    expect($baru->refresh()->is_primary)->toBeTrue()
        ->and($lama->refresh()->is_primary)->toBeFalse()
        ->and($carModel->images()->where('is_primary', true)->count())->toBe(1);
});

it('menyimpan urutan gambar hasil seret', function () {
    $carModel = CarModel::factory()->create();
    $satu = CarModelImage::factory()->create(['car_model_id' => $carModel->id, 'sort_order' => 1]);
    $dua = CarModelImage::factory()->create(['car_model_id' => $carModel->id, 'sort_order' => 2]);

    $this->actingAs(superAdmin())
        ->put(route('admin.car-models.images.reorder', $carModel), ['ids' => [$dua->id, $satu->id]]);

    expect($dua->refresh()->sort_order)->toBe(1)
        ->and($satu->refresh()->sort_order)->toBe(2);
});

it('menolak urutan yang memuat gambar milik model lain', function () {
    $carModel = CarModel::factory()->create();
    $milikSendiri = CarModelImage::factory()->create(['car_model_id' => $carModel->id]);
    $milikOrangLain = CarModelImage::factory()->create();

    $this->actingAs(superAdmin())
        ->put(route('admin.car-models.images.reorder', $carModel), [
            'ids' => [$milikOrangLain->id, $milikSendiri->id],
        ])
        ->assertSessionHasErrors('ids.0');
});

it('menghapus gambar beserta berkasnya di penyimpanan', function () {
    /** @var FakeImageUploader $uploader */
    $uploader = app(ImageUploader::class);

    $carModel = CarModel::factory()->create();
    $image = CarModelImage::factory()->create([
        'car_model_id' => $carModel->id,
        'public_id' => 'chery-arta/car-models/abc',
    ]);

    $this->actingAs(superAdmin())->delete(route('admin.car-models.images.destroy', [$carModel, $image]));

    expect(CarModelImage::whereKey($image->id)->exists())->toBeFalse()
        ->and($uploader->deletedPublicIds())->toBe(['chery-arta/car-models/abc']);
});

it('mengangkat gambar berikutnya menjadi utama saat gambar utama dihapus', function () {
    $carModel = CarModel::factory()->create();
    $utama = CarModelImage::factory()->primary()->create(['car_model_id' => $carModel->id, 'sort_order' => 1]);
    $berikutnya = CarModelImage::factory()->create(['car_model_id' => $carModel->id, 'sort_order' => 2]);

    $this->actingAs(superAdmin())->delete(route('admin.car-models.images.destroy', [$carModel, $utama]));

    expect($berikutnya->refresh()->is_primary)->toBeTrue();
});

it('menolak menyentuh gambar milik model lain', function () {
    $carModel = CarModel::factory()->create();
    $milikOrangLain = CarModelImage::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('admin.car-models.images.destroy', [$carModel, $milikOrangLain]))
        ->assertNotFound();

    expect(CarModelImage::whereKey($milikOrangLain->id)->exists())->toBeTrue();
});
