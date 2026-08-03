<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Penyimpanan Berkas — Cloudinary
|--------------------------------------------------------------------------
|
| Keputusan R3 (docs/10-roadmap-implementasi.md): filesystem Railway bersifat
| ephemeral — apa pun yang ditulis ke disk hilang saat redeploy. Karena itu
| tidak ada `storage:link` dan tidak ada berkas unggahan di disk; semuanya
| lewat App\Services\ImageUploader ke Cloudinary.
|
| Rujukan lengkap: docs/12-panduan-instalasi-deploy.md §12.5.
|
*/

return [

    /*
    | Kredensial berbentuk cloudinary://<api_key>:<api_secret>@<cloud_name>,
    | disalin dari Dashboard Cloudinary. Hanya hidup di .env lokal dan di
    | panel Variables Railway — tidak pernah masuk repo.
    */
    'url' => env('CLOUDINARY_URL'),

    /*
    | Bila true, ImageUploader diganti FakeImageUploader sehingga tidak ada
    | panggilan jaringan sama sekali. Otomatis aktif saat menjalankan uji;
    | isi CLOUDINARY_FAKE=true bila ingin memakainya di pengembangan lokal
    | tanpa akun Cloudinary.
    */
    'fake' => (bool) env('CLOUDINARY_FAKE', false),

    /*
    | Folder terpisah per jenis. Pemisahan ini yang membuat berkas yatim
    | akibat migrate:fresh bisa dibersihkan per kelompok lewat Media Library
    | Cloudinary — lihat docs/12 §12.6.3.
    */
    'folders' => [
        'car_models' => 'chery-arta/car-models',
        'brochures' => 'chery-arta/brochures',
        'facilities' => 'chery-arta/facilities',
        'testimonials' => 'chery-arta/testimonials',
    ],

    /*
    | Batas unggahan, dipakai App\Support\UploadRules dan dari sana masuk ke
    | Form Request. Cloudinary BUKAN pengganti validasi server
    | (.claude/rules/50-keamanan.md).
    */
    'uploads' => [
        'image' => [
            'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 2048,
        ],
        'document' => [
            'mimes' => ['pdf'],
            'max_kb' => 5120,
        ],
    ],

    /*
    | Lebar transformasi baku. Ukuran gambar TIDAK diproses di server —
    | Cloudinary yang mengubah ukuran lewat URL (f_auto,q_auto,w_…), sehingga
    | ekstensi PHP `gd` tidak dibutuhkan.
    */
    'widths' => [
        'thumbnail' => 400,
        'full' => 1600,
    ],

];
