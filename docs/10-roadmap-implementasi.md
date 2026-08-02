# 10 — Roadmap Implementasi

Estimasi memakai satuan **hari kerja untuk satu pengembang**. Urutan fase dipilih agar setiap
fase menghasilkan sesuatu yang bisa dilihat dan diuji, bukan potongan yang menggantung.

## Fase 0 — Fondasi  (2 hari) — ✅ SELESAI

> **Penyimpangan dari rencana, disengaja:**
> 1. Memakai `laravel/react-starter-kit` alih-alih `laravel/laravel` + Breeze — kit itu sudah
>    membawa Inertia 2 + React 19 + TS + Tailwind 4 + shadcn/ui (Breeze masih Tailwind 3).
> 2. **Tabel `users` dari tugas 1.1 ditarik maju ke Fase 0.** Middleware `role` (tugas 0.4)
>    mustahil diuji tanpa kolom `role` dan `is_active`; PHPStan pun menandainya sebagai
>    properti yang tidak ada. Kolom yang ditambahkan: `phone_wa`, `role`, `address`,
>    `is_active`, `last_login_at`, `must_reset_password`, `deleted_at`.

| # | Pekerjaan |
|---|-----------|
| 0.1 | `composer create-project laravel/react-starter-kit` (Laravel 12 + Inertia 2 + React 19 + TS) |
| 0.2 | Tailwind v4 + design token [§6.2](06-desain-ui-ux.md#62-design-token) + shadcn/ui |
| 0.3 | `config/booking.php` & `config/company.php` (data perusahaan dikutip dari sistem lama) |
| 0.4 | Enum `UserRole`, `BookingStatus`, `InvoiceStatus`; middleware `role` |
| 0.5 | Layout: `PublicLayout`, `CustomerLayout`, `AdminLayout` + navigasi & toast |
| 0.6 | Pint, ESLint, Prettier, Pest, Larastan; git repo + `.gitignore` |
| 0.7 | Unduh 9 gambar dari repositori lama ke `database/seeders/assets/` |

**Selesai bila:** `npm run dev` + `php artisan serve` menampilkan kerangka tiga layout, dan
`php artisan test` hijau.

## Fase 1 — Autentikasi & Data Inti  (3 hari)

> **Prasyarat:** MariaDB/MySQL harus dinyalakan (XAMPP Control Panel → MySQL → Start), lalu
> buat database `cheryarta_dev`. Sampai Fase 0 selesai, seluruh pengujian memakai SQLite
> in-memory sehingga belum membutuhkannya.

| # | Pekerjaan |
|---|-----------|
| 1.1 | Migration: ~~`users`~~ (sudah di Fase 0), `car_models`, `car_model_variants`, `car_model_images`, `vehicles`, `service_packages` |
| 1.2 | Model + relasi + `$fillable` + casts enum |
| 1.3 | Register (nama, email, WA, password) dengan normalisasi nomor + rate limit |
| 1.4 | Login/logout/reset password; blokir akun `is_active = false` |
| 1.5 | Seeder: user, paket layanan (10 kode lama), katalog mobil, fasilitas |
| 1.6 | CRUD kendaraan milik customer + komponen `PlateInput` (prefix 1–2 huruf) |
| 1.7 | Uji: registrasi, login, normalisasi nomor, kepemilikan kendaraan |

**Selesai bila:** pelanggan bisa mendaftar, masuk, dan menyimpan kendaraannya.

## Fase 2 — Landing Page Publik  (4 hari)

| # | Pekerjaan |
|---|-----------|
| 2.1 | Beranda: hero, 4 keunggulan, layanan, katalog ringkas, cara booking, fasilitas, testimoni, FAQ, lokasi, footer |
| 2.2 | Halaman katalog + detail model (galeri, varian, spesifikasi, CTA) |
| 2.3 | Halaman layanan, fasilitas (lightbox), tentang, FAQ, kontak (+ form & rate limit) |
| 2.4 | Tombol WhatsApp mengambang |
| 2.5 | SEO: judul per halaman, Open Graph, `schema.org/AutoRepair`, sitemap, robots |
| 2.6 | Uji responsif 360/768/1280 + pemeriksaan kontras |

**Selesai bila:** seluruh konten sistem lama tampil kembali dengan tampilan baru, dan halaman
bisa dipakai penuh di layar 360px.

## Fase 3 — Booking End-to-End  (4 hari)

| # | Pekerjaan |
|---|-----------|
| 3.1 | Migration `bookings` + `booking_status_histories` |
| 3.2 | `SlotService`: daftar slot, sisa kuota, aturan H-1, hari tutup, batas 60 hari |
| 3.3 | Endpoint `GET /booking/slots?date=` + komponen `SlotPicker` |
| 3.4 | Form booking 4 langkah + `BookingService::create()` dengan `lockForUpdate` |
| 3.5 | Generator kode booking `CA-YYYYMMDD-NNNN` |
| 3.6 | Halaman sukses, detail booking, timeline, riwayat customer |
| 3.7 | Jadwal ulang & pembatalan mandiri (Policy + batas H-1) |
| 3.8 | Pelacakan publik via kode booking |
| 3.9 | Uji: kuota penuh, permintaan bersamaan, tanggal Minggu, H-1, akses booking milik orang lain |

**Selesai bila:** pelanggan dapat memesan, melihat status, menjadwal ulang, dan membatalkan —
serta seluruh aturan slot lama terbukti lewat uji otomatis.

## Fase 4 — Panel Admin  (5 hari)

| # | Pekerjaan |
|---|-----------|
| 4.1 | Dashboard: KPI, grafik tren, okupansi slot, tabel hari ini + aksi cepat |
| 4.2 | Manajemen booking: daftar + filter server + detail + ubah status (state machine) |
| 4.3 | Booking walk-in (buat customer baru bila perlu) |
| 4.4 | Halaman jadwal/okupansi harian |
| 4.5 | Customer & kendaraan (daftar, detail, riwayat) |
| 4.6 | Master katalog mobil (model, varian, galeri) & paket layanan |
| 4.7 | Manajemen pengguna internal + Policy |
| 4.8 | Activity log (`spatie/laravel-activitylog`) |
| 4.9 | Uji otorisasi: setiap rute admin ditolak untuk customer & tamu |

**Selesai bila:** advisor dapat menjalankan satu hari kerja penuh tanpa menyentuh database.

## Fase 5 — WhatsApp, Invoice, Laporan  (4 hari)

| # | Pekerjaan |
|---|-----------|
| 5.1 | Migration `whatsapp_templates`, `whatsapp_messages` + seeder 6 template |
| 5.2 | `WhatsAppNotifier` + `ClickToChatNotifier` + panel draft di detail booking + tandai terkirim |
| 5.3 | CRUD template WA dengan validasi placeholder |
| 5.4 | Migration `invoices`, `invoice_items` + `InvoiceService` |
| 5.5 | Penyunting invoice, terbitkan, tandai lunas, void, PDF (dompdf) |
| 5.6 | Halaman invoice untuk customer + unduh PDF |
| 5.7 | Laporan: rekap booking, okupansi, pendapatan, customer baru |
| 5.8 | Export Excel (server) + Salin untuk Spreadsheet (TSV, perilaku lama) |
| 5.9 | Konten: fasilitas, FAQ, testimoni, pesan masuk |

**Selesai bila:** seluruh fitur sistem lama tergantikan, ditambah invoice dan notifikasi WA.

## Fase 6 — Pematangan & Rilis  (3 hari)

| # | Pekerjaan |
|---|-----------|
| 6.1 | Halaman galat 403/404/419/500 berbahasa Indonesia |
| 6.2 | Header keamanan, CSP, rate limit menyeluruh |
| 6.3 | Optimasi: gambar `webp` responsif, lazy-load, `route:cache`, `config:cache` |
| 6.4 | Uji lintas peramban (Chrome, Safari iOS, Firefox) & perangkat nyata |
| 6.5 | *Opsional:* perintah `booking:import-firebase` bila data lama perlu dibawa |
| 6.6 | Penyiapan server: Nginx, PHP-FPM, MySQL, Supervisor, cron, SSL |
| 6.7 | Cadangan otomatis + uji restore |
| 6.8 | Panduan singkat untuk admin (PDF 2 halaman) + serah terima |

**Selesai bila:** seluruh daftar periksa [09 §9.10](09-keamanan-hak-akses.md#910-daftar-periksa-sebelum-rilis) tercentang.

## Ringkasan Jadwal

| Fase | Hari | Kumulatif |
|------|-----:|----------:|
| 0 — Fondasi | 2 | 2 |
| 1 — Auth & data inti | 3 | 5 |
| 2 — Landing page | 4 | 9 |
| 3 — Booking | 4 | 13 |
| 4 — Panel admin | 5 | 18 |
| 5 — WA, invoice, laporan | 4 | 22 |
| 6 — Pematangan & rilis | 3 | **25** |

Sekitar **5 minggu kerja** untuk satu pengembang. Bila dikerjakan dua orang (backend + frontend),
Fase 2 dan 4 dapat berjalan paralel sehingga total ± 3,5 minggu.

## Definition of Done (berlaku untuk setiap tugas)

1. Validasi berada di server; versi klien hanya untuk kenyamanan.
2. Otorisasi diperiksa lewat Policy, bukan sekadar disembunyikan di UI.
3. Ada uji Pest untuk jalur sukses **dan** jalur ditolak.
4. Tampil benar pada 360px, 768px, dan 1280px.
5. Tidak ada `console.log`, `dd()`, atau data contoh yang tertinggal.
6. Props Inertia punya tipe TypeScript.
7. Pint, ESLint, dan `php artisan test` lulus.
8. Teks antarmuka berbahasa Indonesia dan konsisten dengan istilah yang dipakai dokumen ini.

## Risiko

| Risiko | Dampak | Penanganan |
|--------|--------|------------|
| Aturan slot lama dipertahankan (kuota 2/jam) ternyata tidak sesuai kapasitas bengkel sebenarnya | Slot penuh palsu atau bengkel kebanjiran | Nilai berada di `config/booking.php` — bisa diubah tanpa menyentuh kode; `SlotService` sudah menyiapkan `slots_by_weekday` |
| Konflik jam Sabtu ([Q1](README.md#pertanyaan-terbuka)) belum diputuskan | Booking diterima saat bengkel tutup | Diputuskan sebelum Fase 3; perubahan hanya di berkas konfigurasi |
| Notifikasi WA bergantung kedisiplinan advisor menekan kirim | Pelanggan tidak menerima kabar | Panel menampilkan penanda "belum dikirim" yang menonjol; dashboard menampilkan hitungan draft yang belum terkirim |
| Reset password tanpa SMTP ([09 §9.2](09-keamanan-hak-akses.md#92-autentikasi)) | Pelanggan terkunci dari akunnya | Pastikan SMTP tersedia, atau sediakan alur reset lewat admin sebelum rilis |
| Foto katalog & fasilitas belum lengkap | Landing page terasa kosong | Kumpulkan aset di Fase 0; sementara pakai 9 gambar dari sistem lama |
| Scope melebar (stok sparepart, penugasan mekanik) | Jadwal meleset | Sudah ditetapkan sebagai Won't do di [02 §2.6](02-kebutuhan-produk.md#26-batas-scope-wont-do--fase-ini) |
