# Rancangan Website Chery Arta — Service & Booking

Dokumen perancangan untuk membangun ulang website Chery Arta dari prototipe HTML tunggal
berbasis Firebase menjadi aplikasi web ber-arsitektur **Laravel + Inertia.js + React + MySQL**.

**Status:** Big Fase 1 · F1.0 ✅ · F1.1 ⚠️ hampir selesai · F1.2 ✅ · F1.3 ✅ · F1.4 ✅ ·
**F1.5 ✅ selesai** — berikutnya **F1.6 Landing Page Publik**.
Satu-satunya pertanyaan yang masih menghambat adalah
[Q4](#pertanyaan-terbuka), dan itu pun baru dibutuhkan penuh di F2.2.
Sisa F1.1: proteksi branch `main`, `cloudinary:cek`, dan verifikasi dari komputer kedua —
daftarnya di [12 §12.8](12-panduan-instalasi-deploy.md#128-daftar-periksa-selesai-f11)
**Versi:** 2.0 — pengerjaan dibagi menjadi [dua Big Fase](10-roadmap-implementasi.md)
**Tanggal:** 3 Agustus 2026

## Menjalankan Proyek

Panduan lengkap (Railway, database bersama, DBeaver, Cloudinary) ada di
[12-panduan-instalasi-deploy.md](12-panduan-instalasi-deploy.md). Ringkasnya:

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# isi .env: kredensial DB Railway bersama + CLOUDINARY_URL
npm run dev          # terminal 1
php artisan serve    # terminal 2  → http://localhost:8000
```

> Tidak ada `php artisan storage:link` — seluruh gambar disimpan di **Cloudinary**, bukan di
> filesystem, karena disk Railway bersifat ephemeral.
>
> Laptop dan PC kantor menunjuk ke **satu database Railway yang sama**, jadi datanya seragam
> tanpa sinkronisasi. Selama isinya masih data seeder, `migrate:fresh --seed` boleh dipakai untuk
> menyamakan keadaan — batasnya di
> [§12.6](12-panduan-instalasi-deploy.md#126-database-bersama-satu-developer-dua-device).

Gerbang kualitas: `php artisan test` · `./vendor/bin/pint` · `./vendor/bin/phpstan analyse` ·
`npm run types` · `npm run lint` · `npm run build`

### Akun Demo (lokal saja)

Dibuat oleh `UserSeeder`, password semuanya `password`:

| Peran | Email |
|-------|-------|
| Super Admin | `admin@cheryarta.test` |
| Service Advisor | `advisor@cheryarta.test` |
| Customer | `rani@example.test` |

> Selama Big Fase 1 akun-akun ini memang yang dipakai, termasuk di situs Railway — wajar, karena
> belum ada pengguna nyata. **Hapus sebelum go-live**
> ([09 §9.10](09-keamanan-hak-akses.md#910-daftar-periksa-sebelum-rilis),
> [12 §12.6.6](12-panduan-instalasi-deploy.md#1266-seeder--tulang-punggung-bukan-pelengkap)).

### Keadaan Lingkungan

| Hal | Keadaan | Tindakan |
|-----|---------|----------|
| MySQL Railway (bersama) | ⏳ Dibuat di F1.1 | Menjadi satu-satunya database untuk development **dan** deployment sampai F2.5.1 |
| MariaDB 10.4 (XAMPP) | ✅ Berjalan, `cheryarta_dev` termigrasi | Dipertahankan **hanya** untuk menguji `migrate`/`rollback` — bukan untuk pengembangan sehari-hari |
| Cloudinary | ⏳ Akun belum dibuat; kodenya siap | Penyimpanan seluruh gambar katalog, fasilitas, testimoni, dan brosur PDF. Sementara belum ada akun, setel `CLOUDINARY_FAKE=true` di `.env` |
| Ekstensi PHP `gd` | ✅ Tidak lagi dibutuhkan | Pemrosesan ulang gambar diserahkan ke transformasi URL Cloudinary (`f_auto,q_auto,w_…`) |
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
| 10 | [Roadmap Implementasi](10-roadmap-implementasi.md) | **2 Big Fase**, sub-fase, deliverable, definition of done, risiko |
| 11 | [Struktur Folder Proyek](11-struktur-folder-proyek.md) | Pohon direktori Laravel & React, konvensi penamaan |
| 12 | [Panduan Instalasi & Deploy](12-panduan-instalasi-deploy.md) | Railway, database bersama, DBeaver, Cloudinary, aturan main migration |
| 13 | [Panduan Railway CLI](13-panduan-railway-cli.md) | Perintah CLI disusun per kebutuhan: log, variabel, database, SSH, deploy, dan yang tidak berlaku di proyek ini |

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
5. **Aset gambar disimpan di Cloudinary**, tidak lagi menumpang `raw.githubusercontent.com`
   seperti sistem lama dan tidak pula di filesystem server — disk Railway bersifat ephemeral.
   *(Direvisi 3 Agustus 2026; sebelumnya `storage/app/public`.)*
6. **Informasi perusahaan tidak berubah** (alamat Kranji Bekasi, telepon, jam operasional, klaim 15+ tahun pengalaman, 4 keunggulan, 8 fasilitas) — dikutip persis dari prototipe lama.
7. **Target deployment: Railway, auto-deploy dari GitHub.** *(Direvisi 3 Agustus 2026; sebelumnya
   VPS Linux + Nginx.)* Selama Big Fase 1 satu database dipakai bersama oleh development dan
   deployment — dipisahkan di F2.5.1 sebelum go-live.

## Pertanyaan Terbuka

Hal-hal berikut belum punya jawaban dan **perlu keputusan sebelum F1.4 (Booking)**:

| # | Pertanyaan | Dampak bila salah |
|---|------------|-------------------|
| ~~Q1~~ | ~~Slot 14:00 di hari **Sabtu** bertabrakan dengan jam tutup Sabtu (14:00).~~ **✅ Diputuskan 3 Agustus 2026: slot Sabtu berhenti di 13:00**, lewat `slots_by_weekday` di `config/booking.php`. Detail: [05-alur-bisnis.md](05-alur-bisnis.md#catatan-konflik-jam-sabtu) | — |
| Q2 | ~~Harga paket layanan berbayar (kategori *Other*) — siapa yang menentukan?~~ **✅ Terjawab 3 Agustus 2026 oleh F1.3: Super Admin mengisinya sendiri lewat `/admin/paket-layanan`.** Yang **masih terbuka**: apakah harga itu ditampilkan publik di landing page | Menentukan apakah section "Harga Layanan" muncul di landing page. **Dibutuhkan sebelum F1.6.** |
| ~~Q3~~ | ~~Apakah customer boleh booking untuk kendaraan **non-Chery**?~~ **✅ Terjawab 3 Agustus 2026 oleh F1.4: boleh.** Booking memvalidasi kepemilikan kendaraan, bukan asal modelnya; `vehicles.car_model_id` tetap nullable dan nama model manual dipakai apa adanya. | — |
| Q4 | Berapa nomor WA bengkel yang dipakai untuk klik-to-chat, dan apakah satu nomor dipakai bersama semua advisor? | Menentukan apakah nomor pengirim disimpan per user atau per perusahaan. Saat ini dirancang **per perusahaan** (satu nomor resmi). |
