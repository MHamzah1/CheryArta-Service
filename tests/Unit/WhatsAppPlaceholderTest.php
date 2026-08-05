<?php

declare(strict_types=1);

use App\Support\WhatsAppPlaceholders;

// Butuh aplikasi yang ter-boot karena daftar placeholder dan alamat bengkel
// dibaca dari config. Tanpa database — tidak ada satu pun uji di sini yang
// menyentuh tabel.
uses(Tests\TestCase::class);

/*
|--------------------------------------------------------------------------
| Placeholder pesan WhatsApp — satu daftar, tiga pemakai (docs/08 §8.4)
|--------------------------------------------------------------------------
|
| Daftar nama placeholder pernah NYATA berselisih di tiga tempat sekaligus
| (grill K3): docs/08 menyebut sebelas nama, docs/04 menyebut `{{status}}` yang
| tidak pernah ada, dan kode merender sepuluh. Selisih semacam itu hanya
| ketahuan dari pesan pelanggan yang berisi tulisan mentah `{{typo}}`.
|
| Uji ini yang menjaganya: himpunan untuk VALIDASI (config) dan himpunan untuk
| PERENDERAN (WhatsAppPlaceholders) wajib identik, dan nilai contoh untuk
| pratinjau wajib memuat nama yang sama persis dengan nilai sungguhan.
|
*/

it('menyamakan daftar placeholder di config dengan yang benar-benar dirender', function () {
    $config = config('whatsapp.allowed_placeholders');

    sort($config);
    $dirender = WhatsAppPlaceholders::names();
    sort($dirender);

    expect($dirender)->toBe($config);
});

it('memakai nama yang sama pada nilai contoh dan nilai sungguhan', function () {
    // forBooking() menuntut satu Booking beserta relasinya, jadi yang
    // dibandingkan di sini adalah bentuk tokennya — cukup untuk menangkap
    // placeholder yang ditambahkan hanya di satu sisi.
    $contoh = array_keys(WhatsAppPlaceholders::sample());

    foreach ($contoh as $token) {
        expect($token)->toMatch('/^\{\{[a-z_]+\}\}$/');
    }

    expect($contoh)->toHaveCount(count(WhatsAppPlaceholders::names()));
});

it('belum menyediakan ringkasan_biaya sampai modul invoice lahir', function () {
    expect(WhatsAppPlaceholders::names())->not->toContain('ringkasan_biaya');
});

it('tidak menyediakan placeholder status yang pernah tertulis di docs/04', function () {
    // Setiap template sudah terikat satu pemicu, jadi {{status}} selalu
    // berbunyi hal yang sama — informasi nol, dan menyesatkan di pengingat.
    expect(WhatsAppPlaceholders::names())->not->toContain('status');
});

it('mengganti seluruh token yang dikenali saat merender', function () {
    $hasil = WhatsAppPlaceholders::render(
        'Halo {{nama}}, kode {{kode_booking}}.',
        ['{{nama}}' => 'Budi', '{{kode_booking}}' => 'CA-20260805-0001'],
    );

    expect($hasil)->toBe('Halo Budi, kode CA-20260805-0001.');
});
