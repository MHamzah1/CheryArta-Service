# Rancangan Website Chery Arta — Service & Booking

Dokumen perancangan untuk membangun ulang website Chery Arta dari prototipe HTML tunggal
berbasis Firebase menjadi aplikasi web ber-arsitektur **Laravel + Inertia.js + React + MySQL**.

**Status:** Fase 0 (Fondasi) selesai — lihat [10-roadmap-implementasi.md](10-roadmap-implementasi.md)
**Versi:** 1.1
**Tanggal:** 2 Agustus 2026

## Menjalankan Proyek

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan storage:link
npm run dev          # terminal 1
php artisan serve    # terminal 2  → http://localhost:8000
```

Gerbang kualitas: `php artisan test` · `./vendor/bin/pint` · `./vendor/bin/phpstan analyse` ·
`npm run types` · `npm run lint` · `npm run build`

### Akun Demo (lokal saja)

Dibuat oleh `UserSeeder`, password semuanya `password`:

| Peran | Email |
|-------|-------|
| Super Admin | `admin@cheryarta.test` |
| Service Advisor | `advisor@cheryarta.test` |
| Customer | `rani@example.test` |

> Jangan jalankan seeder ini di produksi — lihat daftar periksa [09 §9.10](09-keamanan-hak-akses.md#910-daftar-periksa-sebelum-rilis).

### Keadaan Lingkungan

| Hal | Keadaan | Tindakan |
|-----|---------|----------|
| MariaDB 10.4 (XAMPP) | ✅ Berjalan, database `cheryarta_dev` sudah dibuat & termigrasi | — |
| Ekstensi PHP `gd` | ⚠️ **Belum aktif** | Aktifkan `extension=gd` di `C:\xampp\php\php.ini` lalu restart Apache. Dibutuhkan Fase 2 untuk memproses ulang gambar katalog & fasilitas. |
| Ekstensi PHP `intl` | Belum aktif | Aktifkan bila kelak dibutuhkan pemformatan lokal di sisi server. Saat ini pemformatan dilakukan di `resources/js/lib/format.ts`. |

---

## Daftar Dokumen

| No | Dokumen | Isi |
|----|---------|-----|
| 01 | [Analisis Sistem Lama](01-analisis-sistem-lama.md) | Inventaris fitur prototipe lama, temuan cacat, apa yang dibawa & dibuang |
| 02 | [Kebutuhan Produk (PRD)](02-kebutuhan-produk.md) | Tujuan bisnis, persona, riset kebutuhan landing page & admin, user story, scope MoSCoW |
| 03 | [Arsitektur Teknis](03-arsitektur-teknis.md) | Stack, diagram arsitektur, pola Inertia, autentikasi, storage, deployment |
| 04 | [Skema Database](04-skema-database.md) | ERD, definisi 16 tabel, enum, index, rencana seeder |
| 05 | [Alur Bisnis](05-alur-bisnis.md) | Aturan slot booking, state machine status, reschedule, invoice, sequence diagram |
| 06 | [Desain UI/UX](06-desain-ui-ux.md) | Design token, komponen, sitemap, wireframe tiap halaman, aksesibilitas |
| 07 | [Modul Admin Internal](07-modul-admin.md) | Spesifikasi layar per modul, field, validasi, hak akses |
| 08 | [Notifikasi WhatsApp](08-notifikasi-whatsapp.md) | Mekanisme klik-to-chat, template pesan, normalisasi nomor, logging |
| 09 | [Keamanan & Hak Akses](09-keamanan-hak-akses.md) | Matriks role, perbaikan celah sistem lama, kebijakan upload & backup |
| 10 | [Roadmap Implementasi](10-roadmap-implementasi.md) | 6 fase pengerjaan, deliverable, definition of done, risiko |
| 11 | [Struktur Folder Proyek](11-struktur-folder-proyek.md) | Pohon direktori Laravel & React, konvensi penamaan |

---

## Ringkasan Keputusan (hasil wawancara)

| # | Topik | Keputusan | Konsekuensi utama |
|---|-------|-----------|-------------------|
| 1 | Integrasi Laravel–React | **Inertia.js 2 + React 19 (monolith)** | Satu aplikasi, satu deploy, auth pakai session Laravel. Tidak ada API publik ber-token. Endpoint JSON internal tetap dibuat khusus untuk cek ketersediaan slot. |
| 2 | Katalog Mobil | **Katalog produk Chery + Kendaraan milik customer** | Dua modul terpisah: `car_models` (etalase publik) dan `vehicles` (unit milik customer, basis riwayat service). |
| 3 | Fitur area customer | Booking, riwayat, tracking status, reschedule/batal mandiri, estimasi biaya & invoice | Butuh tabel `booking_status_histories`, `invoices`, `invoice_items`, dan aturan batas waktu reschedule. |
| 4 | Notifikasi | **WhatsApp saja**, tanpa email notifikasi | Tidak ada verifikasi email. Risiko akun sampah ditangani lewat rate limit + moderasi admin. |
| 5 | Identitas login | **Email + password**, nomor WA wajib saat register | Nomor WA unik per akun, dinormalisasi ke format `62xxxxxxxxxx`. |
| 6 | Gateway WA | **Klik-to-chat (`wa.me`)** dipicu dari panel admin saat status berubah | Nol biaya, tanpa risiko blokir Meta. Pengiriman butuh 1 klik manual admin. Jalur upgrade ke gateway didesain sejak awal (interface `WhatsAppNotifier`). |
| 7 | Aturan slot booking | **Dipertahankan persis seperti sistem lama** | Maks. 2 booking per slot jam, wajib H-1, Minggu tutup, slot 08:00–14:00. Disimpan di `config/booking.php`, bukan tersebar di kode. |
| 8 | Role internal | **Super Admin + Service Advisor** | Otorisasi lewat Policy + enum role pada tabel `users`. Semua perubahan status tercatat di activity log. |

## Asumsi yang Dipakai (koreksi bila keliru)

Hal-hal berikut **tidak ditanyakan** karena punya jawaban standar; saya pakai default ini dan
mendokumentasikannya agar mudah dibantah:

1. **Tidak ada migrasi data** dari Firebase — sistem mulai dari database kosong + data seeder.
   Bila data booking lama harus dibawa, disiapkan perintah artisan `booking:import-firebase` (opsional, Fase 6).
2. **Tidak ada pembayaran online.** Invoice hanya rincian biaya; pembayaran dilakukan di bengkel.
3. **Bahasa antarmuka: Indonesia.** Istilah teknis (status, role) memakai istilah bengkel yang lazim.
4. **Zona waktu `Asia/Jakarta`**, mata uang **IDR**, format tanggal `d F Y`.
5. **Aset gambar dipindah ke storage lokal** (`storage/app/public`), tidak lagi menumpang
   `raw.githubusercontent.com` seperti sistem lama.
6. **Informasi perusahaan tidak berubah** (alamat Kranji Bekasi, telepon, jam operasional, klaim 15+ tahun pengalaman, 4 keunggulan, 8 fasilitas) — dikutip persis dari prototipe lama.
7. **Target deployment:** VPS Linux tunggal (Nginx + PHP-FPM + MySQL), bukan serverless.

## Pertanyaan Terbuka

Hal-hal berikut belum punya jawaban dan **perlu keputusan sebelum Fase 3**:

| # | Pertanyaan | Dampak bila salah |
|---|------------|-------------------|
| Q1 | Slot 14:00 di hari **Sabtu** bertabrakan dengan jam tutup Sabtu (14:00). Slot terakhir Sabtu tetap 14:00, atau dipotong sampai 13:00? | Booking Sabtu sore diterima padahal bengkel sudah tutup. Detail: [05-alur-bisnis.md](05-alur-bisnis.md#catatan-konflik-jam-sabtu) |
| Q2 | Harga paket layanan berbayar (kategori *Other*) — siapa yang menentukan, dan apakah ditampilkan publik di landing page? | Menentukan apakah section "Harga Layanan" muncul di landing page. |
| Q3 | Apakah customer boleh booking untuk kendaraan **non-Chery**? | Menentukan apakah `vehicles.car_model_id` boleh null + input model manual. Saat ini dirancang **boleh** (nullable). |
| Q4 | Berapa nomor WA bengkel yang dipakai untuk klik-to-chat, dan apakah satu nomor dipakai bersama semua advisor? | Menentukan apakah nomor pengirim disimpan per user atau per perusahaan. Saat ini dirancang **per perusahaan** (satu nomor resmi). |
