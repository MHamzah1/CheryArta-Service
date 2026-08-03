<?php

declare(strict_types=1);

use App\Support\CloudinaryUrl;

/*
| Ukuran gambar tidak lagi diproses di server (keputusan R3) — Cloudinary yang
| mengubahnya lewat segmen transformasi di URL. Karena URL inilah yang dipakai
| setiap halaman katalog, penyusunannya diuji terpisah dari jaringan.
| Lihat docs/12-panduan-instalasi-deploy.md §12.5.
*/

it('menyisipkan transformasi tepat setelah segmen /upload/', function () {
    $url = 'https://res.cloudinary.com/demo/image/upload/v1699999999/chery-arta/car-models/abc.jpg';

    expect(CloudinaryUrl::withWidth($url, 400))
        ->toBe('https://res.cloudinary.com/demo/image/upload/f_auto,q_auto,w_400/v1699999999/chery-arta/car-models/abc.jpg');
});

it('mengganti transformasi lama alih-alih menumpuknya', function () {
    $url = 'https://res.cloudinary.com/demo/image/upload/f_auto,q_auto,w_400/v1/chery-arta/facilities/x.webp';

    expect(CloudinaryUrl::withWidth($url, 1600))
        ->toBe('https://res.cloudinary.com/demo/image/upload/f_auto,q_auto,w_1600/v1/chery-arta/facilities/x.webp');
});

it('membiarkan nama folder yang tidak memuat penanda transformasi', function () {
    $url = 'https://res.cloudinary.com/demo/image/upload/chery-arta/testimonials/budi.png';

    expect(CloudinaryUrl::withWidth($url, 400))
        ->toBe('https://res.cloudinary.com/demo/image/upload/f_auto,q_auto,w_400/chery-arta/testimonials/budi.png');
});

it('mengembalikan URL non-Cloudinary apa adanya', function () {
    $url = 'https://contoh.test/gambar/foto.jpg';

    expect(CloudinaryUrl::withWidth($url, 400))->toBe($url);
});
