---
description: Jalankan seluruh gerbang kualitas (test, Pint, PHPStan, ESLint, tsc, build) lalu perbaiki yang gagal
---

Jalankan gerbang kualitas proyek dan perbaiki temuannya.

## 1. Jalankan semuanya, kumpulkan hasilnya

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
npm run lint
npx tsc --noEmit
npm run build
```

## 2. Laporkan apa adanya

Buat ringkasan: perintah mana lulus, mana gagal, berapa banyak temuan. **Jangan** menyatakan
bersih bila ada yang merah.

## 3. Perbaiki, dengan urutan ini

1. Uji yang gagal — cari sebabnya; jangan mengubah asersi agar uji lolos kecuali asersinya
   memang salah, dan jelaskan alasannya.
2. Galat tipe TypeScript — perbaiki tipenya, **jangan** memasang `any` atau `@ts-ignore`.
3. Temuan PHPStan — perbaiki, jangan menambah baseline.
4. Format (Pint/ESLint) — jalankan `./vendor/bin/pint` dan `npm run lint -- --fix`.

## 4. Periksa hal yang tidak tertangkap alat

- `console.log`, `dd()`, `dump()`, `var_dump` yang tertinggal
- `any` di props halaman
- Hex warna mentah di JSX (harusnya token)
- `alert()` / `confirm()` (harusnya toast / `ConfirmDialog`)
- Angka aturan bisnis (2, H-1, daftar slot) yang ditulis di luar `config/booking.php`
- Rute yang mengubah data tanpa pemeriksaan Policy

## 5. Jalankan ulang seluruh gerbang setelah perbaikan

Laporkan hasil akhirnya, termasuk bila masih ada yang gagal dan mengapa.
