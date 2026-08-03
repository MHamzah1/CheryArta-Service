<?php

declare(strict_types=1);

use App\Enums\AssetType;
use App\Services\FakeImageUploader;
use App\Services\ImageUploader;
use App\Support\UploadRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

/*
| Filesystem Railway ephemeral (keputusan R3), jadi seluruh berkas melewati
| ImageUploader menuju Cloudinary. Uji di sini menjaga tiga hal: uji tidak
| pernah menembak jaringan, nama berkas asli benar-benar dibuang, dan batas
| unggahan hanya dibaca dari config.
*/

it('memakai FakeImageUploader saat menjalankan uji sehingga tidak ada panggilan jaringan', function () {
    expect(app(ImageUploader::class))->toBeInstanceOf(FakeImageUploader::class);
});

it('menaruh berkas di folder yang diminta dan menghasilkan public id beserta url', function () {
    $uploader = app(ImageUploader::class);
    $folder = Config::string('cloudinary.folders.car_models');

    $asset = $uploader->upload(
        UploadedFile::fake()->create('tiggo8.jpg', 500, 'image/jpeg'),
        $folder,
    );

    expect($asset->publicId)->toStartWith($folder.'/')
        ->and($asset->url)->toContain('/image/upload/')
        ->and($asset->url)->toContain($asset->publicId);
});

it('membuang nama berkas asli dari public id', function () {
    $uploader = app(ImageUploader::class);

    $asset = $uploader->upload(
        UploadedFile::fake()->create('KTP Pelanggan.png', 10, 'image/png'),
        Config::string('cloudinary.folders.facilities'),
    );

    expect($asset->publicId)->not->toContain('KTP')
        ->and($asset->publicId)->not->toContain('Pelanggan');
});

it('mencatat berkas yang diunggah dan yang dihapus', function () {
    /** @var FakeImageUploader $uploader */
    $uploader = app(ImageUploader::class);

    $asset = $uploader->upload(
        UploadedFile::fake()->create('brosur.pdf', 100, 'application/pdf'),
        Config::string('cloudinary.folders.brochures'),
        AssetType::Document,
    );

    $uploader->delete($asset->publicId, AssetType::Document);

    expect($uploader->uploads())->toHaveCount(1)
        ->and($uploader->uploads()[0]['type'])->toBe(AssetType::Document)
        ->and($uploader->deletedPublicIds())->toBe([$asset->publicId]);
});

it('menyusun URL transformasi dari lebar yang ada di config', function () {
    $uploader = app(ImageUploader::class);

    $asset = $uploader->upload(
        UploadedFile::fake()->create('interior.webp', 200, 'image/webp'),
        Config::string('cloudinary.folders.car_models'),
    );

    expect($uploader->transformedUrl($asset->url, Config::integer('cloudinary.widths.thumbnail')))
        ->toContain('f_auto,q_auto,w_400/');
});

it('menghasilkan pasangan kolom siap simpan dengan awalan', function () {
    $uploader = app(ImageUploader::class);

    $asset = $uploader->upload(
        UploadedFile::fake()->create('brosur.pdf', 100, 'application/pdf'),
        Config::string('cloudinary.folders.brochures'),
        AssetType::Document,
    );

    expect($asset->toColumns('brochure_'))->toBe([
        'brochure_public_id' => $asset->publicId,
        'brochure_url' => $asset->url,
    ]);
});

it('membaca batas unggahan gambar dari config, bukan dari angka yang ditulis ulang', function () {
    expect(UploadRules::for(AssetType::Image))
        ->toBe(['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']);
});

it('membaca batas unggahan dokumen dari config', function () {
    expect(UploadRules::for(AssetType::Document))
        ->toBe(['file', 'mimes:pdf', 'max:5120']);
});

it('menolak brosur yang melampaui 5 MB', function () {
    $validator = Validator::make(
        ['brosur' => UploadedFile::fake()->create('brosur.pdf', 5121, 'application/pdf')],
        ['brosur' => UploadRules::for(AssetType::Document)],
    );

    expect($validator->fails())->toBeTrue();
});

it('menerima brosur PDF di bawah batas', function () {
    $validator = Validator::make(
        ['brosur' => UploadedFile::fake()->create('brosur.pdf', 4096, 'application/pdf')],
        ['brosur' => UploadRules::for(AssetType::Document)],
    );

    expect($validator->fails())->toBeFalse();
});
