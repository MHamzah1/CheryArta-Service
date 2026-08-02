<?php

declare(strict_types=1);

use App\Support\PhoneNumber;

/*
| Sistem lama menyimpan nomor apa adanya termasuk tanda hubung hasil format
| otomatis (0812-3456-7890), sehingga tidak bisa dipakai untuk tautan wa.me.
| Lihat docs/08-notifikasi-whatsapp.md §8.3.
*/

it('menormalisasi berbagai bentuk nomor Indonesia ke format 62', function (string $masukan, string $harapan) {
    expect(PhoneNumber::normalize($masukan))->toBe($harapan);
})->with([
    'awalan nol' => ['08123456789', '628123456789'],
    'bertanda hubung' => ['0812-3456-7890', '6281234567890'],
    'awalan +62' => ['+62 812 3456 7890', '6281234567890'],
    'awalan 62' => ['6281234567890', '6281234567890'],
    'tanpa awalan' => ['81234567890', '6281234567890'],
    'berspasi dan berkurung' => ['(0812) 3456 7890', '6281234567890'],
]);

it('mengembalikan string kosong untuk masukan tanpa angka', function () {
    expect(PhoneNumber::normalize('-'))->toBe('');
});

it('memformat balik nomor ternormalisasi untuk ditampilkan', function () {
    expect(PhoneNumber::forDisplay('6281234567890'))->toBe('0812-3456-7890');
});

it('menerima nomor seluler Indonesia yang sah', function (string $nomor) {
    expect(PhoneNumber::isValid($nomor))->toBeTrue();
})->with([
    '08123456789',
    '+6281234567890',
    '0812-3456-7890',
]);

it('menolak nomor yang tidak sah', function (string $nomor) {
    expect(PhoneNumber::isValid($nomor))->toBeFalse();
})->with([
    'terlalu pendek' => ['0812345'],
    'bukan nomor seluler' => ['02138317201'],
    'bukan angka' => ['abcdefghij'],
    'kosong' => [''],
]);
