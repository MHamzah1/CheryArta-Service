---
description: Bangun ulang database dari nol beserta seeder, lalu verifikasi isinya
allowed-tools: Bash(php artisan migrate*), Bash(php artisan db:seed*), Bash(php artisan storage:link), Bash(php artisan tinker*)
---

Bangun ulang database pengembangan.

## Prasyarat — periksa dulu

1. Pastikan `APP_ENV=local`. **Bila bukan `local`, hentikan dan beri tahu pengguna** — perintah
   ini menghapus seluruh data.
2. Tanyakan konfirmasi bila database berisi data yang tampak nyata (bukan hasil seeder).

## Jalankan

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

## Verifikasi hasilnya

Periksa dan laporkan jumlah baris:

- `users` — minimal 1 Super Admin + 1 Service Advisor
- `service_packages` — 10 paket dengan kode asli sistem lama (`first_maintenance_1000`,
  `first_maintenance_5000`, `first_maintenance_ev_5000`, `Tiggo_5X_Cross_Free`, `Tiggo_8_Free`,
  `EV_Free`, `CSH_Free`, `Other`, …)
- `car_models` — katalog Chery beserta gambarnya
- `facilities` — 8 fasilitas
- `whatsapp_templates` — 6 template
- `faqs` — pertanyaan umum

Laporkan juga kredensial akun demo yang dibuat seeder (hanya berlaku di lingkungan lokal).

## Bila gagal

Tampilkan galatnya apa adanya, tunjukkan migration mana yang bermasalah, dan perbaiki
migration-nya — jangan menambal dengan mengubah data secara manual.
