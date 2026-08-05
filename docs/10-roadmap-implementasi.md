# 10 — Roadmap Implementasi

Pengerjaan berjalan sebagai **satu urutan tahap**, dikerjakan berurutan dari atas ke bawah.

**Tidak ada target hari.** Sebuah tahap dinyatakan selesai bila
[Definition of Done](#definition-of-done) terpenuhi — bukan setelah sekian lama dikerjakan.
Angka hari hanya menciptakan tekanan untuk menyatakan selesai sebelum uji dan otorisasinya
benar-benar beres, dan bagian itulah yang paling mahal bila dilewati.

## Di mana kita sekarang

| Tahap | Isi | Status |
|:-----:|-----|--------|
| **1** · `F1.0` | Fondasi | ✅ selesai |
| **2** · `F1.1` | Instalasi, database bersama & deploy Railway | ✅ selesai |
| **3** · `F1.2` | Autentikasi & data inti | ✅ selesai |
| **4** · `F1.3` | Master admin — katalog & paket layanan (A4, A5) | ✅ selesai |
| **5** · `F1.4` | Booking end-to-end (customer) | ✅ selesai |
| **6** · `F1.5` | Admin operasional (A2, A3, A6) | ✅ selesai |
| **7** · `F1.6` | Landing page publik | ✅ selesai |
| **8** · `F2.1` | Dashboard & laporan (A1, A9) | ✅ selesai |
| **9** · `F2.2` | Konten, pengguna & audit (A10–A14) | ✅ selesai |
| **10** · `F2.3` | Notifikasi WhatsApp penuh (A7) | ✅ selesai |
| **11** · `F2.4` | **Invoice (A8)** | 🔄 **berikutnya** |
| **12** · `F2.5` | Pengerasan & go-live | ⏳ |

Sepuluh tahap selesai, dua tersisa. Dua sub-fase pernah dicabut —
riwayatnya di [Tahap yang dicabut](#tahap-yang-dicabut).

> **Kode `F1.x` / `F2.x` dipertahankan apa adanya.** Nomor itu dirujuk ±350 kali di komentar
> kode, uji, dan dokumen lain. Ia kini sekadar **label tetap**, bukan lagi penanda pembagian
> fase — urutan yang berlaku adalah nomor Tahap di kolom kiri.

**Sasaran akhir:** pelanggan mendaftar → melihat katalog → memesan servis; staf mengelola
booking, jadwal, invoice, konten, dan akun internal; sistem berjalan aman di domain sendiri.

---

## Keputusan yang membentuk roadmap ini

Sembilan keputusan berikut diambil pada revisi 3 Agustus 2026 dan menjadi dasar urutan tahap.
Bila salah satunya berubah, roadmap ini ikut berubah.

> **R9 direvisi 4 Agustus 2026.** Versi sebelumnya menarik A11 Pengguna Internal maju menjadi
> sub-fase F1.7. Keputusan itu dicabut: A11 kembali ke Tahap 9, F1.7 tidak ada lagi. Isinya di
> bawah sudah versi baru.

| # | Keputusan | Konsekuensi |
|---|-----------|-------------|
| R1 | **Booking dibangun penuh sejak awal** — customer memesan sendiri *dan* admin mengelola | Inti booking (`SlotService`, state machine, kuota) tidak bisa ditunda; Tahap 5 & 6 wajib. |
| R2 | **Satu database Railway** untuk development sekaligus deployment | Dikerjakan satu orang yang berpindah laptop ↔ PC kantor, jadi data otomatis seragam tanpa sinkronisasi apa pun. `migrate:fresh --seed` **boleh** selama isinya masih data seeder — batasnya di [§Kapan `migrate:fresh` berhenti boleh](#kapan-migratefresh-berhenti-boleh). Dipisahkan di F2.5.1. |
| R3 | **Gambar disimpan di Cloudinary**, bukan filesystem | Filesystem Railway ephemeral. Konsekuensi skema: kolom `*_path` diganti `*_public_id` + `*_url`. Ekstensi PHP `gd` tidak lagi dibutuhkan. |
| R4 | **Fasilitas, FAQ, testimoni: tabel DB + seeder idempoten sejak Tahap 3** | Landing page membaca DB sejak awal; layar CRUD-nya (A10) menyusul di Tahap 9 tanpa kerja ulang di landing page. |
| R5 | **Sebelum go-live sistem dipakai developer saja** | Pengerasan (halaman galat, CSP, rate limit menyeluruh, backup, uji lintas peramban) terkumpul di Tahap 12. |
| R6 | **Notifikasi WA sebelum Tahap 10 = tombol manual sederhana** | Tombol "Chat via WhatsApp" di detail booking, teks dari `config`. Tanpa tabel template, tanpa log kirim — itu A7 di Tahap 10. |
| R7 | **Slot Sabtu berhenti di 13:00** — menutup [Q1](README.md#pertanyaan-terbuka) | `config/booking.php` memakai `slots_by_weekday` untuk hari Sabtu. Tidak ada perubahan kode `SlotService`. |
| R8 | **`/admin` mengalihkan ke `/admin/bookings`** | A1 Dashboard ditunda utuh; `pages/admin/dashboard.tsx` dari Fase 0 dihapus, bukan diisi setengah. |
| R9 | **A11 Pengguna Internal dikerjakan di Tahap 9** — F1.7 dicabut | Menggantikan keputusan sebelumnya yang menariknya lebih awal. Akun staf lahir dari `UserSeeder` sampai Tahap 9; penambahan advisor sungguhan lewat `tinker` atau DBeaver sampai F2.2.3. Penegakan `must_reset_password` ikut ke sana — dampaknya kecil selama kolom itu hanya mengenai akun customer. `AccessMatrixTest` (A14) ke F2.2.7 setelah F1.8 juga dicabut, dibuat sekaligus lengkap di sana. |

## Status modul admin

| Modul | Keadaan sekarang | Lahir di |
|-------|------------------|----------|
| A1 — Dashboard | ✅ lengkap | Tahap 8 |
| A2 — Manajemen Booking + walk-in | ✅ lengkap | Tahap 6 |
| A3 — Jadwal / Okupansi | ✅ lengkap | Tahap 6 |
| A4 — Katalog Mobil | ✅ lengkap | Tahap 4 |
| A5 — Paket Layanan | ✅ lengkap | Tahap 4 |
| A6 — Customer & Kendaraan | ✅ lengkap | Tahap 6 |
| A7 — Notifikasi WhatsApp | ✅ lengkap | Tahap 6 (tombol R6) · penuh di Tahap 10 |
| A8 — Invoice | ❌ belum ada | **Tahap 11** |
| A9 — Laporan & Export | ⚠️ tiga laporan jalan; **Pendapatan** menyusul | Tahap 8 · sisanya **Tahap 11** (2.4.7) |
| A10 — Konten landing page | ✅ lengkap | Tahap 3 (tabel) · Tahap 9 (layar) |
| A11 — Pengguna Internal | ✅ lengkap | Tahap 9 |
| A12 — Activity Log | ✅ lengkap; penjadwal retensi menyusul | Tahap 9 · cron di **Tahap 12** |
| A13 — Navigasi panel | ⚠️ lengkap kecuali Invoice | Tahap 9 · Template WA di Tahap 10 · sisanya Tahap 11 |
| A14 — Matriks hak akses | ✅ satu berkas `AccessMatrixTest` | Tahap 9 · baris WA di Tahap 10 · baris invoice menyusul |

> **A13 dan A14 tidak pernah bisa ditunda sepenuhnya.** A13 hanyalah spesifikasi menu — tanpa
> menu, layar A2–A6 tidak bisa dicapai. A14 hanyalah *ringkasan* matriks — otorisasinya sendiri
> (Policy tiap sumber daya, pembatasan SA-saja pada A4 & A5) wajib ada sejak modulnya lahir.
> Menunda otorisasi berarti mengulang temuan S3 sistem lama.
>
> Yang ditunda ke Tahap 9 hanyalah **penyusunannya sebagai satu berkas**. Sampai saat itu
> barisnya tetap ditegakkan, hanya tersebar di `AdminAccessTest`, `CustomerDirectoryTest`, dan
> uji katalog — dan tersebar berarti baris yang *hilang* tidak kelihatan. Itulah yang
> diperbaiki `AccessMatrixTest`.
>
> **Konsekuensi A11 menunggu sampai Tahap 9**, diterima secara sadar: sampai saat itu "advisor
> ditolak di rute SA" adalah aturan yang benar terhadap **akun contoh dari seeder**, bukan
> terhadap orang sungguhan. Menambah advisor baru berarti membuka `tinker` atau DBeaver. Dapat
> diterima selama sistem dipakai developer saja (**R5**) — tetapi menjadi penghalang begitu
> aplikasinya didemokan ke pemilik bengkel, jadi F2.2.3 tidak boleh mundur lebih jauh.

---

# Tahapan

Dikerjakan berurutan. Setiap tahap tunduk pada [Definition of Done](#definition-of-done)
yang sama.

## Tahap 1 · Fondasi  `F1.0` — ✅ SELESAI

> **Penyimpangan dari rencana, disengaja:**
> 1. Memakai `laravel/react-starter-kit` alih-alih `laravel/laravel` + Breeze — kit itu sudah
>    membawa Inertia 2 + React 19 + TS + Tailwind 4 + shadcn/ui (Breeze masih Tailwind 3).
> 2. **Tabel `users` dari tugas F1.2 ditarik maju ke F1.0.** Middleware `role` (tugas 0.4)
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

## Tahap 2 · Instalasi, Database Bersama & Deploy Railway  `F1.1` — ✅ SELESAI

Fase ini didahulukan agar **setiap commit berikutnya langsung terbukti bisa di-deploy**, dan agar
semua komputer developer memandang data yang sama sejak hari pertama. Panduan langkah demi
langkah ada di [12-panduan-instalasi-deploy.md](12-panduan-instalasi-deploy.md).

> **Selesai 3 Agustus 2026.** Seluruh 1.1.1–1.1.10 tercentang: situs hidup di Railway, kedua
> komputer developer memandang database yang sama, seeder terbukti mengisi database itu dan
> idempoten saat diulang, serta `cloudinary:cek` hijau memakai kredensial sungguhan.
>
> Daftar periksa beserta buktinya di
> [12 §12.8](12-panduan-instalasi-deploy.md#128-daftar-periksa-selesai-f11).
>
> **Penyimpangan dari rencana, disengaja:**
> 1. **`App\Services\ImageUploader` menjadi interface**, bukan kelas konkret seperti tertulis di
>    1.1.6. Tanpa itu `FakeImageUploader` tidak bisa menggantikannya di uji. Implementasi
>    sungguhannya `CloudinaryImageUploader`. Polanya sama dengan `WhatsAppNotifier` di F2.3.2.
> 2. **`railway.json` ditambahkan** di samping `nixpacks.toml`, agar pre-deploy command ikut
>    ter-review lewat git alih-alih hanya hidup di panel.
> 3. **Perintah `cloudinary:cek` ditambahkan.** Layar unggah baru ada di F1.3, sehingga tanpa
>    perintah ini kriteria "unggah gambar percobaan muncul di Cloudinary" hanya bisa dibuktikan
>    dengan kode sekali pakai.

| # | Pekerjaan |
|---|-----------|
| 1.1.1 | Repositori GitHub + branch `main` terlindungi; Railway project tertaut ke repo (auto-deploy tiap push ke `main`) |
| 1.1.2 | Service **MySQL** di Railway + aktifkan TCP proxy (public networking) agar bisa diakses dari komputer developer |
| 1.1.3 | Berkas build: `nixpacks.toml` (PHP + Node, jalankan `npm run build`), start command, dan **pre-deploy command** `php artisan migrate --force` |
| 1.1.4 | Variabel Railway: `APP_KEY`, `APP_ENV`, `APP_DEBUG=false`, `APP_URL`, `APP_TIMEZONE=Asia/Jakarta`, `DB_*` (referensi ke service MySQL), `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` |
| 1.1.5 | `trustProxies(at: '*')` di `bootstrap/app.php` — tanpa ini Laravel membangun URL `http://` di balik proxy Railway sehingga aset & redirect rusak |
| 1.1.6 | Akun **Cloudinary** + `CLOUDINARY_URL` di Railway dan di `.env` lokal; `App\Services\ImageUploader` (bungkus SDK `cloudinary/cloudinary_php`) + `FakeImageUploader` untuk uji |
| 1.1.7 | `.env.example` diperbarui: contoh koneksi ke DB Railway bersama + Cloudinary, **tanpa kredensial nyata** |
| 1.1.8 | Panduan **DBeaver**: koneksi MySQL ke host & port proxy Railway, `allowPublicKeyRetrieval=true` — ditulis di dokumen 12 |
| 1.1.9 | Dokumen 12 §12.6: batas pemakaian `migrate:fresh` terhadap DB Railway + prosedur `mysqldump` untuk dipakai kelak saat isi database sudah berarti |
| 1.1.10 | Workflow GitHub Actions: `php artisan test`, Pint, PHPStan, ESLint, `tsc --noEmit`, `npm run build` |

**Selesai bila:** `git push` ke `main` menghasilkan situs hidup di domain Railway; dua komputer
berbeda menjalankan `php artisan migrate:status` dan melihat hasil identik; DBeaver tersambung ke
database yang sama; unggah gambar percobaan muncul di Cloudinary.

### Kapan `migrate:fresh` berhenti boleh

Keputusan **R2** aman untuk sekarang karena dua alasan, dan keduanya punya masa berlaku:

1. **Pengembang tunggal** — berpindah laptop ↔ PC kantor. Tidak ada rekan yang datanya ikut hilang.
2. **R5 — belum ada pengguna nyata.** Seluruh isi database berasal dari seeder, jadi bisa
   dibangun ulang kapan saja.

Selama keduanya berlaku, `migrate:fresh`, `migrate:refresh`, `db:wipe`, dan `/db-segar`
**boleh dijalankan terhadap DB Railway**. Justru itu cara paling sederhana menyamakan dua
perangkat: satu perintah, keadaan seragam, tanpa sinkronisasi manual.

**Berhenti** begitu salah satu ini terjadi:

- Ada registrasi, booking, atau pesan kontak dari orang **di luar Anda sendiri**.
- Aplikasi mulai didemokan ke pemilik bengkel dan mereka mengisi data sungguhan.
- Masuk **F2.5.1** — database produksi dipisahkan; sejak itu `migrate:fresh` hanya untuk DB
  development.

Tiga akibat yang tetap perlu diingat meski diperbolehkan:

| Akibat | Penanganan |
|--------|------------|
| Tabel `sessions` ikut terhapus → semua yang sedang login di situs Railway ter-logout | Jalankan saat situs tidak sedang dipakai/didemokan |
| Baris gambar hilang, **berkasnya tetap di Cloudinary** → menumpuk jadi berkas yatim | Sesekali bersihkan lewat Media Library Cloudinary; folder sudah terpisah per jenis |
| Seeder menjadi satu-satunya jalan pulih | Seeder produksi wajib **lengkap & idempoten** — kalau tidak, `migrate:fresh --seed` meninggalkan sistem setengah isi |

## Tahap 3 · Autentikasi & Data Inti  `F1.2` — ✅ SELESAI

> **Selesai 3 Agustus 2026.** 124 uji Pest hijau; Pint, PHPStan, ESLint, `tsc --noEmit`, dan
> `npm run build` bersih. Kesembilan migration diuji `migrate` **dan** `migrate:rollback`
> terhadap database Railway bersama, lalu dipasang kembali.
>
> **Penyimpangan dari rencana, disengaja:**
> 1. **Kolom gambar memakai pasangan `public_id` + `url`**, bukan `path`/`image_path` seperti
>    tertulis di [04 §4.2](04-skema-database.md). Kolom `path` lahir sebelum keputusan **R3**
>    memindahkan seluruh berkas ke Cloudinary; bentuk pasangan inilah yang dihasilkan
>    `App\Support\UploadedAsset::toColumns()`. Dokumen 04 sudah diperbarui.
> 2. **8 paket layanan, bukan 10.** Penghitungan ulang terhadap prototipe menemukan 8 opsi;
>    angka 10 di [01 §1.5](01-analisis-sistem-lama.md) keliru dan sudah dikoreksi.
> 3. **Tiga enum tambahan** — `CarCategory`, `FuelType`, `ServicePackageCategory`. Kolomnya
>    disebut "varchar + PHP Enum" di dokumen 04 tetapi enumnya belum pernah didefinisikan.
> 4. **`VehicleService` ditambahkan** di luar empat service inti. Invarian "satu kendaraan utama
>    per pemilik" menyentuh banyak baris sekaligus sehingga butuh transaksi — terlalu berat
>    untuk controller, dan bukan urusan model.
> 5. **Rute `/admin` kini dijaga `role:super_admin,service_advisor`.** Catatan "FASE 1" di
>    `routes/web.php` menunggu kolom `role` & `is_active`, yang sudah ada sejak F1.0.
> 6. **Gambar fasilitas & katalog belum di-seed.** Berkasnya ada di
>    `database/seeders/assets/`, tetapi mengunggahnya menuntut `CLOUDINARY_URL` terisi —
>    dan seeder yang gagal tanpa kredensial membuat `migrate:fresh --seed` mustahil dijalankan
>    di CI. Gambar dipasang lewat layar admin di F1.3.
>
> **Sisa yang belum terbukti:** DoD #9 ("terbukti hidup setelah di-deploy ke Railway") — belum
> ada push ke `main` sejak perubahan ini, jadi yang teruji baru lingkungan lokal terhadap
> database Railway.

| # | Pekerjaan |
|---|-----------|
| 1.2.1 | Migration: ~~`users`~~ (sudah di F1.0), `car_models`, `car_model_variants`, `car_model_images`, `vehicles`, `service_packages` |
| 1.2.2 | Migration konten landing: `facilities`, `faqs`, `testimonials`, `contact_messages` (**R4** — tabel dulu, layar CRUD-nya di Tahap 9) |
| 1.2.3 | Model + relasi + `$fillable` + casts enum + factory tiap model |
| 1.2.4 | Register (nama, email, WA, password) dengan normalisasi nomor + rate limit |
| 1.2.5 | Login/logout/reset password; blokir akun `is_active = false` |
| 1.2.6 | Seeder idempoten: paket layanan (10 kode lama), katalog mobil, fasilitas, FAQ, testimoni |
| 1.2.7 | CRUD kendaraan milik customer + komponen `PlateInput` (prefix 1–2 huruf) |
| 1.2.8 | Uji: registrasi, login, normalisasi nomor, kepemilikan kendaraan, plat 1 & 2 huruf |

**Selesai bila:** pelanggan bisa mendaftar, masuk, dan menyimpan kendaraannya; seluruh tabel
inti + konten sudah terisi seeder di database Railway bersama.

## Tahap 4 · Master Admin: Katalog & Paket Layanan  `F1.3` — ✅ SELESAI

Dikerjakan **sebelum** landing page, karena landing page menampilkan data yang dikelola di sini.

> **Selesai 3 Agustus 2026.** 182 uji Pest hijau (62 di antaranya baru, di
> `tests/Feature/Admin/`); Pint, PHPStan, ESLint, `tsc --noEmit`, dan `npm run build` bersih.
> Tidak ada migration baru — seluruh tabelnya sudah lahir di F1.2.
>
> **Penyimpangan dari rencana, disengaja:**
> 1. **Aturan 1.3.5 ditegakkan di Service, bukan di Policy.** `CarModelPolicy` dan
>    `ServicePackagePolicy` murni menjawab "siapa" (Super Admin saja); "boleh dihapus atau
>    tidak" dijawab `CarModelService`/`ServicePackageService` lewat
>    `App\Exceptions\MasterDataInUseException`, yang ditangani terpusat di `bootstrap/app.php`
>    menjadi toast penjelasan. Penolakan berupa 403 tidak memberi tahu admin apa yang harus
>    dilakukan; pesannya kini menyebut "nonaktifkan saja".
> 2. **`ServicePackage::isReferenced()` masih mengembalikan `false`.** Satu-satunya tabel yang
>    akan merujuk paket layanan adalah `bookings`, yang baru lahir di F1.4. Method-nya sudah ada
>    beserta jalur penolakannya, jadi F1.4 cukup mengganti isi method itu — bukan menambah
>    pemeriksaan baru di controller.
> 3. **Tiga service baru** — `CarModelService`, `CarModelGalleryService`, `ServicePackageService`
>    — di luar empat service inti, dengan alasan yang sama seperti `VehicleService` di F1.2.
> 4. **Rute admin mengikat model lewat `{car_model:id}`**, bukan slug seperti katalog publik.
>    Slug boleh disunting admin, dan URL panel tidak boleh berubah di tengah penyuntingan.
> 5. **Spesifikasi dikirim sebagai daftar pasangan `[{key, value}]`**, meski disimpan sebagai
>    objek `{"mesin":"1.6 TGDI"}` sesuai [04 §4.2](04-skema-database.md). Bentuk daftar menjaga
>    urutan baris dan membuat kunci kembar bisa ditolak validasi dengan pesan yang menunjuk
>    baris tepatnya; `CarModelService` yang mengubahnya menjadi objek.
> 6. **`App\Support\SeriesCatalog`** menyatukan kode seri dari `car_models.series_code` dan dari
>    `service_packages.applicable_series` yang sudah tersimpan. Tanpa bagian kedua, paket lama
>    `CSH_Free` mustahil disunting karena model seri CSH belum ada di katalog.
> 7. **Komponen `DataTable` dan `Pagination` dibuat lebih awal** ([06 §6.6](06-desain-ui-ux.md)),
>    karena dua daftar di fase ini sudah membutuhkannya. F1.5 tinggal memakainya.
> 8. **Form model dikirim POST + `X-HTTP-Method-Override: PUT`.** Brosur PDF membuat
>    pengirimannya multipart, dan multipart tidak mengenal PUT. Jalur ini ikut diuji.
>
> **Sisa yang belum terbukti:** unggahan sungguhan ke Cloudinary. Seluruh uji memakai
> `FakeImageUploader` (tidak ada uji yang menembak jaringan), jadi kriteria "gambarnya tampil
> dari Cloudinary" masih perlu satu kali pembuktian manual lewat layar `/admin/katalog`
> dengan `CLOUDINARY_URL` terisi — atau lewat `php artisan cloudinary:cek`.

| # | Pekerjaan |
|---|-----------|
| 1.3.1 | **A5 Paket Layanan** `/admin/paket-layanan` (SA): CRUD + validasi kode unik, kategori, `applicable_series`, durasi 15–480, harga/gratis |
| 1.3.2 | **A4 Katalog Mobil** `/admin/katalog` (SA): CRUD model + slug otomatis + spesifikasi kunci–nilai |
| 1.3.3 | A4 varian: tabel dalam halaman (nama, harga, spesifikasi, aktif) |
| 1.3.4 | A4 galeri lewat Cloudinary: unggah banyak gambar, urut seret, tandai utama, **teks alt wajib**; brosur PDF |
| 1.3.5 | Aturan: model/paket yang sudah dirujuk hanya bisa dinonaktifkan, tidak dihapus |
| 1.3.6 | Policy `CarModelPolicy`, `ServicePackagePolicy` — **SA saja**, advisor ditolak di server |
| 1.3.7 | Uji: advisor ditolak di seluruh rute katalog & paket layanan; unggah menolak berkas > 2 MB dan MIME palsu |

**Selesai bila:** Super Admin bisa menambah satu model mobil baru berikut varian dan galerinya
tanpa menyentuh database, dan gambarnya tampil dari Cloudinary.

## Tahap 5 · Booking End-to-End (Customer)  `F1.4` — ✅ SELESAI

> **Selesai 3 Agustus 2026.** 272 uji Pest hijau — 81 di antaranya baru di `tests/Feature/Booking/`,
> ditambah uji slot Sabtu di `BookingConfigTest` dan uji aturan 1.3.5 yang baru bisa dijalankan
> setelah tabel `bookings` ada. Pint, PHPStan, ESLint, `tsc --noEmit`, dan `npm run build` bersih.
> Kedua migration diuji `migrate` **dan** `migrate:rollback` terhadap database Railway bersama.
>
> **Penyimpangan dari rencana, disengaja:**
> 1. **`App\Support\SlotTime` ditambahkan**, dan `booking_date` memakai cast `immutable_date:Y-m-d`.
>    MySQL mengembalikan kolom TIME sebagai `09:00:00` sedangkan SQLite (dipakai uji) mengembalikan
>    apa yang ditulis; tanpa bentuk kanonis, kueri kuota lulus di uji lalu diam-diam gagal di
>    produksi. Cacat ini nyata ditemukan saat uji pertama dijalankan, bukan diantisipasi.
> 2. **`estimated_finish_at` diisi sejak booking dibuat**, bukan menunggu status `in_progress`
>    seperti tertulis di [05 §5.3](05-alur-bisnis.md#53-state-machine-status-booking). Rumusnya
>    memang `booking_datetime + estimated_duration_minutes` ([04 §4.2](04-skema-database.md)), dan
>    halaman pelacakan publik membutuhkannya sejak awal. F1.5 tinggal menghitung ulang saat
>    kendaraan benar-benar mulai dikerjakan.
> 3. **Dua Rule object, bukan `NotSunday` + `AvailableSlot`** seperti dicontohkan
>    `.claude/rules/10`. `BookableDate` menggabungkan hari tutup + H-1 + batas 60 hari karena
>    ketiganya menyoroti kolom yang sama dan hanya satu pesan yang perlu tampil; namanya juga tidak
>    menyebut "Sunday" karena hari tutup datang dari config, bukan dari kode.
> 4. **`SlotUnavailableException` ditangani terpusat** di `bootstrap/app.php` menjadi galat inline
>    di bawah kolom jam — polanya sama dengan `MasterDataInUseException` di F1.3.
> 5. **Penerbitan kode booking mengulang transaksi** bila nomor urutnya keburu dipakai. Baris yang
>    belum ada tidak bisa dikunci, sehingga dua permintaan pertama pada tanggal yang sama bisa
>    menyusun `-0001` bersamaan; unique index menangkapnya dan yang kalah mengulang.
> 6. **`App\Support\BookingPresenter` ditambahkan** untuk membentuk props. Alasannya keamanan, bukan
>    kerapian: bentuk publik (`publicTracking`) dan bentuk pemilik (`detail`) berdiri berdampingan
>    sehingga sulit tanpa sengaja membocorkan `admin_note` atau identitas pemesan.
> 7. **`ServicePackage::isReferenced()` kini benar-benar memeriksa `bookings`** — janji yang
>    ditinggalkan F1.3 sudah ditepati, beserta ujinya.
>
> **Sisa yang belum terbukti:** penguncian baris (`lockForUpdate`) hanya berlaku nyata di MySQL;
> SQLite yang dipakai uji mengabaikan klausa itu. Yang terbukti otomatis adalah **keatomikannya** —
> kueri hitung kuota berjalan saat transaksi sudah terbuka — dan bahwa tiga permintaan beruntun
> pada satu slot tidak pernah menembus kuota. Perilaku dua permintaan yang benar-benar bersamaan
> perlu satu kali pembuktian manual terhadap MySQL.

| # | Pekerjaan |
|---|-----------|
| 1.4.1 | Migration `bookings` + `booking_status_histories` |
| 1.4.2 | `config/booking.php`: `slots_by_weekday` untuk Sabtu berhenti 13:00 (**R7**) |
| 1.4.3 | `SlotService`: daftar slot, sisa kuota, aturan H-1, hari tutup, batas 60 hari |
| 1.4.4 | Endpoint `GET /booking/slots?date=` (throttle 60/menit) + komponen `SlotPicker` |
| 1.4.5 | Form booking 4 langkah + `BookingService::create()` dengan `lockForUpdate` |
| 1.4.6 | Generator kode booking `CA-YYYYMMDD-NNNN` di dalam transaksi |
| 1.4.7 | Halaman sukses, detail booking, timeline status, riwayat customer |
| 1.4.8 | Jadwal ulang & pembatalan mandiri (`BookingPolicy` + batas H-1) |
| 1.4.9 | Pelacakan publik via kode booking (throttle 10/menit, **hanya** kode + jadwal + status) |
| 1.4.10 | Uji: kuota penuh, dua permintaan bersamaan, hari Minggu, H-1, > 60 hari, Sabtu 13:30 ditolak, akses booking milik orang lain |

**Selesai bila:** pelanggan dapat memesan, melihat status, menjadwal ulang, dan membatalkan —
dan seluruh aturan slot terbukti lewat uji otomatis, bukan lewat pemeriksaan manual.

## Tahap 6 · Admin Operasional: A2, A3, A6  `F1.5` — ✅ SELESAI

> **Selesai 3 Agustus 2026.** 363 uji Pest hijau — 91 di antaranya baru di
> `tests/Feature/Admin/` (`AdminAccessTest`, `BookingListTest`, `BookingStatusTest`,
> `WalkInBookingTest`, `ScheduleTest`, `CustomerDirectoryTest`). Pint, PHPStan, ESLint,
> `tsc --noEmit`, dan `npm run build` bersih. Tidak ada migration baru — seluruh kolomnya
> (`admin_note`, `handled_by`, `started_at`, `completed_at`) sudah lahir di F1.4.
>
> **Penyimpangan dari rencana, disengaja:**
> 1. **Transisi status ditegakkan di Service, bukan di Policy maupun Form Request.**
>    `BookingPolicy::updateStatus` hanya menjawab "siapa" (staf); "boleh ke mana" dijawab
>    `App\Enums\BookingStatus::canTransitionTo()` yang diperiksa `BookingService::changeStatus()`
>    **di dalam transaksi dengan barisnya terkunci**. Percobaan transisi tidak sah berujung
>    `App\Exceptions\InvalidStatusTransitionException` — ditangani terpusat di `bootstrap/app.php`
>    menjadi **422**, polanya sama dengan `SlotUnavailableException` di F1.4. Sengaja 422 dan bukan
>    403: advisornya memang berhak, perpindahannyalah yang tidak ada. Konsekuensinya, booking yang
>    sudah berakhir tetap mengirim `canUpdateStatus: true` dengan `statusOptions` kosong; panelnya
>    yang menjelaskan, bukan otorisasinya yang menolak.
> 2. **Tiga service baru** — `WalkInBookingService`, `CustomerAccountService`, `WhatsAppNotifier`.
>    Yang terakhir sudah memakai nama dari [03 §3.6](03-arsitektur-teknis.md) supaya F2.3 cukup
>    mengubah isinya menjadi implementasi interface tanpa menyentuh satu pun controller.
>    `WalkInBookingService` membungkus akun + kendaraan + booking dalam **satu** transaksi: slot
>    yang ternyata penuh tidak boleh meninggalkan akun setengah jadi di daftar pelanggan (diuji).
> 3. **`App\Support\BookingFilters`** — objek nilai yang dipakai untuk tiga hal sekaligus tanpa
>    ditulis ulang: menyusun kueri, mengisi kembali form saringan di React, dan menempel di query
>    string agar tautannya bisa dibagikan.
> 4. **`GET /booking/slots` menerima `?sumber=walk_in`.** Form walk-in butuh slot **hari ini**,
>    sedangkan endpoint itu semula selalu memakai aturan H-1. `SlotAvailabilityRequest::source()`
>    hanya menghormati `walk_in` bila pemanggilnya staf yang login — pengunjung yang mengetiknya
>    di URL tetap mendapat aturan web. Sekalian, `$request->validate()` di controllernya diganti
>    Form Request sesuai `.claude/rules/10`.
> 5. **Pencarian pelanggan di form walk-in memakai kunjungan Inertia parsial**, bukan endpoint
>    JSON baru. [Aturan 20](../.claude/rules/20-frontend-react-inertia.md) hanya mengecualikan
>    ketersediaan slot, dan daftar pelanggan justru data yang paling tidak boleh punya endpoint
>    terbuka sendiri (temuan S8).
> 6. **`BookingPolicy::view` kini mengizinkan staf**, sesuai contoh di
>    [09 §9.3](09-keamanan-hak-akses.md). `reschedule`/`cancel` tetap milik pemiliknya saja.
> 7. **A6 hanya menampilkan akun ber-role `customer`.** Akun staf ditolak 403 di
>    `/admin/customers/{id}` — pengelolaannya milik A11 di Tahap 9, yang punya pengaman
>    berbeda (Super Admin terakhir tidak boleh dinonaktifkan).
> 8. **Total nilai invoice di detail customer belum ada**, meski disebut [07 §A6](07-modul-admin.md).
>    Tabel invoice baru lahir di F2.4; menampilkan "Rp 0" untuk sesuatu yang belum dihitung lebih
>    menyesatkan daripada tidak menampilkannya. Dilengkapi di F2.4.6.
> 9. **Teks WhatsApp ada di `config/company.php` (`wa_messages`), berkunci status booking.**
>    Sementara, sesuai R6 — tabel `whatsapp_templates` yang bisa disunting Super Admin, log
>    pengiriman, dan penanda "sudah dikirim" menyusul di F2.3.
>
> **Sisa yang belum terbukti:**
> - **`must_reset_password` belum ditegakkan saat login.** Kolomnya diisi dengan benar (akun
>   walk-in baru dan akun yang direset Super Admin), tetapi belum ada pemaksaan ganti password di
>   alur masuk. Selama isinya hanya akun customer dampaknya kecil — akun walk-in dibuat dengan
>   password acak yang tidak diketahui siapa pun, jadi pemiliknya memang harus lewat jalur reset.
>   **Penegakannya ada di Tahap 9** (**R9**), bersama A11: yang membuatnya mendesak adalah
>   password sementara yang bisa membuka **panel internal**, dan itu baru ada begitu akun staf
>   bisa dibuat dari panel.
> - **Password sementara hasil reset tampil di flash message.** Tanpa notifikasi email
>   (keputusan #4) tidak ada jalur lain; nilainya tidak tersimpan di mana pun dalam bentuk
>   terbaca, tetapi ia melewati session. Perlu ditinjau ulang bila SMTP kelak tersedia
>   ([09 §9.2](09-keamanan-hak-akses.md)).
> - **Penguncian baris pada `changeStatus`** memakai `lockForUpdate`, yang hanya berlaku nyata di
>   MySQL — sama seperti catatan F1.4. Yang terbukti otomatis adalah keatomikannya.

| # | Pekerjaan |
|---|-----------|
| 1.5.1 | `/admin` → redirect ke `/admin/bookings`; hapus `pages/admin/dashboard.tsx` (**R8**) |
| 1.5.2 | **A2** daftar booking: filter **di server** (cari nama/plat/kode, status, rentang tanggal, paket, advisor), paginasi 25, kartu di `< md` |
| 1.5.3 | **A2** detail booking + panel ubah status memakai state machine ([05 §5.3](05-alur-bisnis.md#53-state-machine-status-booking)); transisi tidak sah ditolak server |
| 1.5.4 | **A2** booking walk-in: cari/buat customer (`must_reset_password`), pilih/tambah kendaraan, **H-1 dilewati**, kuota tetap berlaku, `source = walk_in`, status `confirmed` |
| 1.5.5 | **A3** jadwal harian: 11 baris slot × kapasitas 2, navigasi ← hari →, penanda slot penuh |
| 1.5.6 | **A6** `/admin/customers` (daftar, detail, riwayat) & `/admin/vehicles`; nonaktifkan akun + reset password = **SA saja** |
| 1.5.7 | Tombol "Chat via WhatsApp" di detail booking — `wa.me` + teks dari `config/company.php` (**R6**, bukan A7 penuh) |
| 1.5.8 | Navigasi `AdminLayout` untuk A2–A6 saja; menu yang belum ada tidak dirender (A13 sebagian) |
| 1.5.9 | Uji otorisasi: customer & tamu ditolak di **setiap** rute `/admin`; advisor ditolak pada aksi khusus SA |

**Selesai bila:** advisor dapat menjalankan satu hari kerja penuh — menerima booking telepon,
mengubah status, mencari riwayat unit — tanpa menyentuh database.

## Tahap 7 · Landing Page Publik  `F1.6` — ✅ SELESAI

Menampilkan data yang sudah dikelola admin di F1.3 (katalog, paket layanan) dan data seeder
untuk yang belum punya layar admin (fasilitas, FAQ, testimoni — **R4**).

> **Selesai 3 Agustus 2026.** 418 uji Pest hijau — 55 di antaranya baru di
> `tests/Feature/Public/` (`LandingPageTest`, `CatalogTest`, `ContentPageTest`,
> `ContactFormTest`, `PublicPrivacyTest`, `SitemapTest`). Pint, PHPStan, ESLint,
> `tsc --noEmit`, dan `npm run build` bersih. Tidak ada migration baru — seluruh tabelnya
> (`facilities`, `faqs`, `testimonials`, `contact_messages`) sudah lahir di F1.2 sesuai **R4**.
>
> **Penyimpangan dari rencana, disengaja:**
> 1. **JSON-LD `schema.org/AutoRepair` disusun server di `resources/views/app.blade.php`**,
>    bukan di komponen React. Menyisipkan `<script type="application/ld+json">` dari React
>    menuntut `dangerouslySetInnerHTML`, yang dilarang keras di proyek ini
>    ([aturan 20](../.claude/rules/20-frontend-react-inertia.md)). Isinya dibentuk
>    `App\Support\StructuredData` dari `config/company.php`.
> 2. **Peta lokasi berupa tautan Google Maps, bukan `iframe`.** Embed pihak ketiga menabrak
>    kebijakan CSP yang dikerjakan di F2.5, dan di ponsel tautan justru membuka aplikasi peta
>    yang lebih berguna. URL-nya dari `config('company.social.maps_url')`.
> 3. **`SlotService::nextBookableDate()` ditambahkan** untuk kartu ketersediaan di hero.
>    "Besok" bukan jawaban yang benar bila besok jatuh pada hari tutup, dan menghitungnya di
>    React akan menyalin aturan H-1 ke tempat kedua (temuan B2).
> 4. **Shared prop `appUrl` ditambahkan** di `HandleInertiaRequests`. Tag `og:url` dan
>    `<link rel="canonical">` butuh URL absolut yang sudah benar sebelum JavaScript jalan,
>    jadi asalnya diambil dari permintaan yang sedang berjalan — bukan dari `window`.
> 5. **Katalog dipaginasi 12 per halaman** meski isinya masih sedikit. Aturan
>    [30-database](../.claude/rules/30-database.md) melarang daftar tanpa paginasi, dan
>    memasangnya sekarang lebih murah daripada menambahkannya setelah katalog membesar.
> 6. **FAQ accordion ditulis tangan**, tidak memakai `@radix-ui/react-accordion` (paketnya
>    memang belum terpasang). Pasangan `aria-expanded` + `aria-controls` sudah cukup; tidak
>    ada penguncian fokus yang menuntut Radix di sini.
> 7. **Halaman `/cek-service` ikut memakai `<Seo>`** meski lahir di F1.4 — ia ada di
>    `sitemap.xml`, jadi meninggalkannya tanpa deskripsi dan Open Graph tidak konsisten.
>
> **Sisa yang belum terbukti:** butir **1.6.6** (uji responsif 360/768/1280 + pemeriksaan
> kontras) dikerjakan lewat kelas Tailwind mobile-first dan token warna yang kontrasnya sudah
> ditetapkan di [06 §6.2](06-desain-ui-ux.md), tetapi **belum ada pembuktian manual di peramban
> sungguhan**. Dengan F1.8 dicabut, pembuktian itu bergabung ke **F2.5.6** — artinya berkas
> responsif tahap-tahap awal baru benar-benar diverifikasi menjelang go-live.

| # | Pekerjaan |
|---|-----------|
| 1.6.1 | Beranda: hero, 4 keunggulan, layanan, katalog ringkas, cara booking, fasilitas, testimoni, FAQ, lokasi, footer |
| 1.6.2 | Halaman katalog + detail model (galeri, varian, spesifikasi, CTA) — sumber: A4 |
| 1.6.3 | Halaman layanan (sumber: A5), fasilitas (lightbox), tentang, FAQ, kontak (+ form, throttle 5/jam, tersimpan di `contact_messages`) |
| 1.6.4 | Tombol WhatsApp mengambang (nomor dari `config/company.php`) |
| 1.6.5 | SEO: judul per halaman, Open Graph, `schema.org/AutoRepair`, sitemap, robots |
| 1.6.6 | Uji responsif 360/768/1280 + pemeriksaan kontras |
| 1.6.7 | Uji: halaman publik tidak membocorkan nama lengkap, nomor WA, atau plat pelanggan |

**Selesai bila:** seluruh konten sistem lama tampil kembali dengan tampilan baru, halaman bisa
dipakai penuh di layar 360px, dan CTA "Booking Servis" benar-benar mengantar ke form F1.4.

> **Catatan jujur:** pesan dari form kontak tersimpan di `contact_messages` tetapi **belum ada
> layar untuk membacanya** sampai A10 dibangun di Tahap 9. Sampai saat itu pesan hanya bisa
> dilihat lewat DBeaver. Ini konsekuensi langsung dari menunda A10, dan tercatat di
> [tabel risiko](#risiko).

---

> **Tonggak yang sudah lewat — sistem inti berjalan penuh.** Sesudah Tahap 7, satu alur penuh
> berhasil di lingkungan Railway: daftar → tambah kendaraan → pilih paket → pilih slot →
> booking dibuat → advisor mengonfirmasi → status berubah → customer melihatnya di riwayat.
> Advisor yang dipakai pada alur itu akun bawaan `UserSeeder` — pembuatan akun staf dari panel
> baru ada di F2.2.3 (**R9**).
>
> **Konsekuensi yang diterima sadar:** tonggak itu dilewati tanpa satu pun putaran verifikasi
> menyeluruh yang berdiri sendiri, karena F1.8 dicabut. Paritas terhadap prototipe lama,
> kelengkapan seeder, dan matriks hak akses baru terbukti di Tahap 9–12. Selama sistem dipakai
> developer saja (**R5**) itu dapat diterima; yang tidak boleh terjadi adalah go-live
> mendahului Tahap 12.

**Sisanya diurutkan panel admin dulu, invoice dan WhatsApp belakangan.** Alasannya, yang
dipakai setiap hari oleh orang bengkel adalah panelnya — dashboard, laporan, konten, dan
pengelolaan akun. Notifikasi WhatsApp sudah punya jalur sementara yang berfungsi (tombol
klik-to-chat, **R6**), dan invoice belum pernah ada di sistem lama sehingga tidak ada yang
menunggunya. Keduanya boleh menyusul.

Satu ketergantungan yang harus dijaga: **dashboard Tahap 8 menampilkan hitungan draft
WhatsApp**, padahal tabelnya baru lahir di Tahap 10. Kartu itu karena itu dibangun di F2.3.5 —
dashboard tidak boleh menampilkan "0 draft" untuk tabel yang belum ada.

> **Sudah ditutup di Tahap 10**, dan bentuknya berubah saat dikerjakan: kartunya menghitung
> **booking yang belum dikabari**, bukan jumlah draft. Baris draft hanya lahir ketika advisor
> menekan tombolnya, sehingga menghitung draft justru melewatkan advisor yang tidak membuka
> WhatsApp sama sekali — persis kelalaian yang hendak ditangkap.

## Tahap 8 · Dashboard & Laporan  `F2.1` — ✅ SELESAI

| # | Pekerjaan |
|---|-----------|
| 2.1.1 | **A1** Dashboard: KPI, grafik tren 30 hari (Recharts), okupansi slot hari ini, tabel booking hari ini, peringatan calon `no_show` |
| 2.1.2 | A1 aksi cepat (Konfirmasi · Mulai · Selesai) lewat Inertia partial reload |
| 2.1.3 | `/admin` dikembalikan ke dashboard, redirect F1.5.1 dicabut |
| 2.1.4 | **A9** Laporan: rekap booking, okupansi, customer baru (pendapatan menyusul di **2.4.7**) |
| 2.1.5 | A9 export mengikuti filter aktif: unduh CSV dibuat server + Salin untuk Spreadsheet (TSV) |

> **Selesai 4 Agustus 2026.** 475 uji Pest hijau — 60 di antaranya menyentuh F2.1
> (`DashboardTest`, `ReportTest`, `BookingExportTest`, `AdminAccessTest`). Pint, PHPStan,
> ESLint, `tsc --noEmit`, dan `npm run build` bersih. Tidak ada migration baru: kedua index
> yang dibutuhkan agregasi — `bookings(status, booking_date)` dan
> `(booking_date, booking_time)` — sudah ada sejak `2026_08_03_100010`.
> Rancangannya di [`docs/prds/prd-dashboard-laporan.md`](prds/prd-dashboard-laporan.md).
>
> **Penyimpangan dari rencana, disengaja:**
> 1. **Export menghasilkan CSV, bukan `.xlsx`; `maatwebsite/excel` tidak dipasang.** Paket itu
>    menuntut PhpSpreadsheet beserta ekstensi PHP `zip`, yang ketersediaannya di runtime
>    Railway belum terbukti — dan `composer install` yang gagal menjatuhkan seluruh deploy,
>    bukan hanya export. CSV ber-BOM UTF-8 dibuka Excel secara langsung tanpa dependensi baru.
>    Ini jalur cadangan yang sudah disepakati di PRD (Pertanyaan Terbuka #1).
> 2. **Laporan Pendapatan dipisahkan keluar** menjadi butir **2.4.7**; tabel `invoices` baru
>    lahir di F2.4.1. Tab-nya **tidak dirender**, bukan ditampilkan lalu dinonaktifkan.
> 3. **`app/Support/ReportPeriod.php` ditambahkan** di luar daftar service semula. Definisi
>    "30 hari" dan "bulan lalu" harus hidup di satu tempat, kalau tidak angkanya tertulis di
>    service dan di label antarmuka sekaligus — persis pola cacat B2 sistem lama.
> 4. **Recharts di-*code-split*** ke chunk `booking-trend-chart-*.js`; bundel `app.js` tidak
>    memuatnya, sehingga halaman publik tidak ikut menanggung ±100 KB itu.
>
> **Utang yang tercatat, bukan bagian F2.1:** redirect setelah login masih mengarah ke
> `/dashboard` milik customer tanpa memandang role, dan grup rute customer belum dijaga
> `role:customer` — akibatnya staf yang login mendarat di layar customer. Keduanya cacat
> warisan F1.2, di luar lingkup PRD ini — **diusulkan masuk F2.2**, keputusannya diambil di
> sesi grill F2.2.

## Tahap 9 · Konten, Pengguna & Audit  `F2.2` — ✅ SELESAI

Menutup sisi panel admin: layar konten yang tabelnya sudah terisi sejak F1.2.2, pengelolaan
akun staf yang sampai kini hanya bisa lewat DBeaver, dan jejak audit atas keduanya.

| # | Pekerjaan |
|---|-----------|
| 2.2.1 | **A10** layar CRUD `/admin/fasilitas`, `/admin/faq`, `/admin/testimoni` (tabelnya sudah ada sejak F1.2.2 — tidak ada migrasi data) |
| 2.2.2 | **A10** `/admin/pesan-masuk`: tandai dibaca, balas via WA, hapus spam; lencana jumlah belum dibaca di `AdminLayout` |
| 2.2.3 | **A11** `/admin/users` (SA saja) utuh (**R9**): daftar akun staf (nama, email, role, status aktif, login terakhir; saringan role & status; paginasi 25); tambah & ubah akun — password **tidak** diisi admin, server membuatnya acak dan menandai `must_reset_password`; nonaktifkan/aktifkan + reset password |
| 2.2.4 | Pengaman A11 di `UserService`, di dalam transaksi: Super Admin tidak bisa menurunkan role atau menonaktifkan **dirinya sendiri**; **Super Admin aktif terakhir** tidak bisa diturunkan maupun dinonaktifkan (dihitung dengan baris terkunci, bukan dibaca lalu ditulis); akun ber-role `customer` ditolak di seluruh rute ini — pengelolaannya tetap milik A6. `UserPolicy` diperluas untuk sasaran staf; grup rute `role:super_admin` |
| 2.2.5a | ✅ **Tujuan setelah login mengikuti peran** — `App\Support\HomeRoute` menentukannya satu tempat, dipakai lima berkas Auth. Staf ke `admin.dashboard`, customer ke `dashboard`. `intended()` tetap menang supaya tautan dalam yang memicu login tidak hilang |
| 2.2.5b | ✅ **Grup rute customer dijaga `role:customer`**, sejajar dengan penjagaan grup admin. Efek samping yang disengaja: `is_active` ikut diperiksa di grup ini, yang sebelumnya tidak sama sekali |
| 2.2.5c | Penegakan `must_reset_password`: middleware pada rute ber-auth mengalihkan ke `settings/password` sampai password diganti — kecuali rute ganti password itu sendiri dan logout. Menutup sisa F1.5 |
| 2.2.6 | **A12** activity log (`spatie/laravel-activitylog`), retensi 12 bulan — termasuk perubahan akun staf dari 2.2.3 |
| 2.2.7 | **A13** navigasi panel lengkap (termasuk grup "Sistem" → Pengguna Internal) + **A14** `tests/Feature/Admin/AccessMatrixTest.php` dibuat **sekaligus lengkap**: satu uji per baris matriks untuk seluruh modul yang sudah ada saat itu (A2–A6, master data, A10, A11) — SA boleh, advisor ditolak pada baris SA-saja, customer & tamu ditolak di seluruhnya. Baris invoice & template WA menyusul di F2.4.6 dan F2.3.6 |
| 2.2.8 | Uji A11: SA gagal menurunkan/menonaktifkan diri sendiri; SA aktif terakhir tidak bisa dijatuhkan; akun staf baru tidak bisa membuka menu apa pun sebelum menetapkan password; `UserSeeder` tetap idempoten dan tetap menghasilkan SA + advisor yang bisa dipakai masuk |

> **Selesai 4 Agustus 2026.** 546 uji Pest hijau — 62 di antaranya baru
> (`AccessMatrixTest`, `ContentCrudTest`, `UserManagementTest`, `ActivityLogTest`,
> `MustResetPasswordTest`). Pint, PHPStan, ESLint, `tsc --noEmit`, dan `npm run build` bersih;
> ketiga migration `activity_log` diuji `migrate` **dan** `rollback`.
> Rancangannya di [`docs/prds/prd-konten-pengguna-audit.md`](prds/prd-konten-pengguna-audit.md).
>
> **A14 dikerjakan lebih dulu** sesuai keputusan grill #1: `AccessMatrixTest` ditulis sebagai
> kerangka lengkap dengan 10 baris merah, lalu menghijau seiring modulnya lahir. Mekanismenya
> terbukti bekerja — enam baris berubah hijau begitu backend A10 selesai, dan ia menangkap satu
> rute yang controllernya belum di-import.
>
> **Penyimpangan dari rencana, disengaja:**
> 1. **`spatie/laravel-activitylog` ternyata mendukung Laravel 12** (v4.12.3) — pertanyaan
>    terbuka PRD terjawab, jalur cadangan tabel buatan sendiri tidak jadi dipakai.
> 2. **`App\Models\Concerns\RecordsActivity` ditambahkan** di luar daftar berkas PRD. Pengecualian
>    `password`/`remember_token` harus berlaku untuk seluruh model sekaligus; menyalinnya ke
>    delapan model berarti cukup satu terlewat untuk membocorkannya.
> 3. **`FacilityService` dan `ContentOrderService` dipisah**, bukan satu service konten. Hanya
>    fasilitas yang menyentuh Cloudinary; FAQ dan testimoni tidak butuh lapis yang tidak
>    memutuskan apa pun.
> 4. **`LastSuperAdminException` ditambahkan** beserta penanganannya di `bootstrap/app.php`,
>    supaya penolakan "SA aktif terakhir" tampil sebagai galat inline yang menjelaskan jalan
>    keluarnya — bukan 403 yang tidak menjelaskan apa pun.
>
> **Bug warisan yang ikut ditemukan dan diperbaiki:** `PasswordController::update` tidak pernah
> melepas `must_reset_password`. Tidak berakibat apa-apa selama tandanya belum ditegakkan —
> tetapi begitu middleware 2.2.5c hidup, orang yang baru menetapkan passwordnya sendiri akan
> terjebak selamanya di halaman ganti password. Ditemukan oleh ujinya, bukan setelah rilis.

## Tahap 10 · Notifikasi WhatsApp Penuh  `F2.3` — ✅ SELESAI

Sampai titik ini tombol klik-to-chat sederhana dari F1.5.7 masih yang dipakai (**R6**) — jalur
manualnya berfungsi, hanya tanpa template yang bisa disunting dan tanpa jejak pengiriman.

| # | Pekerjaan |
|---|-----------|
| 2.3.1 | Migration `whatsapp_templates`, `whatsapp_messages` + seeder **7** template |
| 2.3.2 | Interface `WhatsAppNotifier` + implementasi `ClickToChatNotifier`; tombol sederhana F1.5.7 diganti |
| 2.3.3 | Panel draft di detail booking + tandai terkirim + penanda "belum dikirim" yang menonjol |
| 2.3.4 | **A7** `/admin/template-wa` (SA): daftar + sunting dengan validasi placeholder — **bukan CRUD** |
| 2.3.5 | Kartu "Belum dikabari" ditambahkan ke dashboard A1 — ditunda ke sini karena tabelnya baru lahir di 2.3.1 |
| 2.3.6 | **A14** baris template WA ditambahkan ke `AccessMatrixTest` (F2.2.7) |

> **Selesai 5 Agustus 2026.** 592 uji Pest hijau — 46 di antaranya baru
> (`WhatsAppDraftTest`, `WhatsAppTemplateCrudTest`, `WhatsAppPendingTest`,
> `WhatsAppPlaceholderTest`, plus baris WA di `AccessMatrixTest`). Pint, PHPStan, ESLint,
> `tsc --noEmit`, dan `npm run build` bersih. Kedua migration diuji `migrate` **dan**
> `migrate:rollback` terhadap database Railway bersama, lalu dipasang kembali dan di-seed —
> ketujuh kunci terbukti terisi urut.
> Rancangannya di [`docs/prds/prd-notifikasi-whatsapp.md`](prds/prd-notifikasi-whatsapp.md),
> keputusannya di [`docs/grills/grill-notifikasi-whatsapp.md`](grills/grill-notifikasi-whatsapp.md).
>
> **Penyimpangan dari rencana, disengaja** — seluruhnya diputuskan saat grill:
> 1. **Baris log ditulis saat advisor menekan "Buka WhatsApp"**, bukan saat status berubah seperti
>    tertulis di [08 §8.2](08-notifikasi-whatsapp.md). Aksi cepat dashboard (F2.1.2) akan
>    meninggalkan tiga baris per booking, dan kartu 2.3.5 tidak akan pernah bisa dikosongkan.
>    Konsekuensinya kartu itu menghitung dari sisi **booking**, bukan dari sisi baris draft.
> 2. **Pesan tidak bisa disunting di panel**, berbeda dari [08 §8.2](08-notifikasi-whatsapp.md).
>    Kotak ketik WhatsApp sendiri sudah menyediakannya, dan `rendered_message` harus tetap bisa
>    dipercaya sebagai buatan server — POST pencatatnya hanya membawa `template_key`.
> 3. **Tujuh template, bukan enam** — `booking_no_show` ditambahkan. Ia satu-satunya akhir yang
>    punya jalur pemulihan, teksnya sudah hidup sejak F1.5, dan menghapusnya akan ditandai
>    `/audit-paritas` di 2.5.10 sebagai fitur yang hilang.
> 4. **Kunci ke-6 bernama `booking_reminder`**, bukan `reminder_h1` seperti di
>    [04 §4.2](04-skema-database.md): "H-1" hidup di `config/booking.php`, dan menyalinnya ke nama
>    kunci membuat namanya berbohong begitu aturannya bergeser.
> 5. **`{{ringkasan_biaya}}` ditolak validasi**, bukan dirender kosong — sumbernya tabel
>    `invoices` yang baru lahir di 2.4.1. Placeholder yang sah tetapi selalu kosong membuat Super
>    Admin menyangka penyuntingnya rusak. Dihidupkan di **2.4.8**. `{{status}}` dibuang sama
>    sekali.
> 6. **Bukan CRUD template** — tanpa tambah dan tanpa hapus (butir 2.3.4 sudah dikoreksi di atas).
> 7. **`send()` tidak dibuat** meski [08 §8.7](08-notifikasi-whatsapp.md) menuliskannya sebagai
>    no-op; ia kode mati yang tidak dipanggil siapa pun.
> 8. **Enam berkas baru di luar daftar PRD** — `App\Enums\WhatsAppTemplateKey` (menggantikan
>    `config('whatsapp.templates')` supaya himpunan kuncinya tidak hidup di dua tempat),
>    `WhatsAppMessageStatus`, `App\Support\WhatsAppPlaceholders`, `WhatsAppLink`, `WhatsAppDraft`,
>    dan dua exception yang ditangani terpusat di `bootstrap/app.php` — polanya sama dengan
>    `InvalidStatusTransitionException` di F1.5.
> 9. **`WhatsAppNotifier` diikat `bind`, bukan `singleton`.** Implementasinya menyimpan template
>    yang sudah dibaca agar layar jadwal tidak memicu satu kueri per booking; singleton akan
>    membuat simpanan itu bertahan antar-permintaan di dalam satu uji.
> 10. **Balasan pesan kontak pindah ke accessor `ContactMessage::whatsapp_reply_url`**, bukan ikut
>    di interface: ia tidak punya template, tidak punya booking, dan `booking_id` tidak nullable.
>
> **Bug warisan yang ikut ditemukan:** `App\Models\WhatsAppTemplate` dan `WhatsAppMessage`
> membutuhkan `$table` eksplisit — Laravel menurunkan nama `WhatsAppTemplate` menjadi
> `whats_app_templates`, dan tanpa itu seluruh kueri menabrak tabel yang tidak ada. Ditemukan oleh
> uji, bukan setelah rilis.
>
> **Sisa yang belum terbukti:**
> - **DoD #9** — belum ada push ke `main` sejak perubahan ini, jadi yang teruji baru lingkungan
>   lokal terhadap database Railway.
> - **Uji responsif 360/768/1280** untuk panel WA, halaman template, dan kartu dashboard kelima
>   dikerjakan lewat kelas Tailwind mobile-first tetapi **belum dibuktikan di peramban sungguhan**
>   — bergabung ke **F2.5.6** bersama sisa tahap-tahap sebelumnya.
> - **Kartu dashboard akan lahir dengan tunggakan kecil**: booking yang sudah dikabari lewat
>   tombol R6 tidak punya catatan apa pun, jadi ikut terhitung "belum dikabari" pada hari pertama.
>   Habis sendiri dalam sepekan karena rentang 7 hari; tombol **Lewati** membersihkan sisanya.

## Tahap 11 · Invoice  `F2.4`

| # | Pekerjaan |
|---|-----------|
| 2.4.1 | Migration `invoices`, `invoice_items` + `InvoiceService` (seluruh perhitungan di server) |
| 2.4.2 | **A8** penyunting invoice, terbitkan, tandai lunas, void (SA saja, wajib alasan) |
| 2.4.3 | PDF invoice (`barryvdh/laravel-dompdf`) |
| 2.4.4 | Halaman invoice untuk customer + unduh PDF |
| 2.4.5 | Uji: total dihitung server, invoice `issued` tidak bisa disunting, advisor tidak bisa `void` |
| 2.4.6 | **A14** baris invoice ditambahkan ke `AccessMatrixTest` (F2.2.7); total nilai invoice di detail customer (A6) yang tertunda sejak F1.5 ikut dilengkapi |
| 2.4.7 | **A9** laporan **Pendapatan** (total invoice `issued`/`paid` per periode, **SA saja**) ditambahkan sebagai tab keempat di `/admin/laporan` — ditunda ke sini dari F2.1.4 karena tabel `invoices` baru lahir di 2.4.1 |
| 2.4.8 | **A7** placeholder `{{ringkasan_biaya}}` dihidupkan: satu entri di `config('whatsapp.allowed_placeholders')` + satu di `App\Support\WhatsAppPlaceholders`, lalu teks awal `booking_completed` di seeder diperbarui — ditunda ke sini dari Tahap 10 karena sumbernya tabel `invoices` |

## Tahap 12 · Pengerasan & Go-Live  `F2.5`

| # | Pekerjaan |
|---|-----------|
| **2.5.1** | **Pisahkan database produksi dari database development** — akhir dari keputusan R2. Service MySQL kedua di Railway, `.env` developer diarahkan ke DB dev; sejak titik ini `migrate:fresh` hanya boleh menyentuh DB dev |
| 2.5.2 | Backup terjadwal + **uji restore** (tanpa uji restore, backup hanya asumsi) |
| 2.5.3 | Halaman galat 403/404/419/500 berbahasa Indonesia |
| 2.5.4 | Header keamanan, CSP, rate limit menyeluruh sesuai [09 §9](09-keamanan-hak-akses.md) |
| 2.5.5 | Optimasi: transformasi Cloudinary (`f_auto,q_auto`) responsif, lazy-load, `config:cache`, `route:cache`, `view:cache` di build Railway |
| 2.5.6 | Uji lintas peramban (Chrome, Safari iOS, Firefox) & perangkat nyata, **pada 360/768/1280** — menyerap bekas 1.8.7 dan sisa F1.6.6 |
| 2.5.7 | Domain kustom + SSL; `APP_ENV=production`, `APP_DEBUG=false` diverifikasi |
| 2.5.8 | *Opsional:* perintah `booking:import-firebase` bila data lama perlu dibawa |
| 2.5.9 | Panduan singkat untuk admin (PDF 2 halaman) + serah terima |
| 2.5.10 | `/audit-paritas` terhadap prototipe lama untuk **seluruh** fitur yang sudah dibangun — bekas 1.8.4, kini menjangkau **seluruh** tahap sekaligus |
| 2.5.11 | Satu putaran `migrate:fresh --seed` terhadap DB **dev** (setelah 2.5.1 memisahkannya), lalu telusuri alur penuh — membuktikan seeder masih lengkap. Bekas 1.8.6 |
| 2.5.12 | **Aktifkan penjadwal di Railway** (proses `schedule:work` atau cron), lalu buktikan `activitylog:clean` benar-benar berjalan. Perintahnya sudah didaftarkan di `routes/console.php` sejak Tahap 9, tetapi tanpa penjadwal ia tidak pernah jalan — retensi 12 bulan (`docs/07 §A12`, `docs/09 §9.7`) sampai saat itu masih janji di dokumen |

**Selesai bila:** seluruh daftar periksa [09 §9.10](09-keamanan-hak-akses.md#910-daftar-periksa-sebelum-rilis) tercentang.

---

## Definition of Done

Berlaku untuk **setiap tugas**, di tahap mana pun. Inilah satu-satunya alat ukur "selesai" —
menggantikan estimasi hari yang sengaja dibuang dari roadmap ini.

1. Validasi berada di server; versi klien hanya untuk kenyamanan.
2. Otorisasi diperiksa lewat Policy, bukan sekadar disembunyikan di UI.
3. Ada uji Pest untuk jalur sukses **dan** jalur ditolak.
4. Tampil benar pada 360px, 768px, dan 1280px.
5. Tidak ada `console.log`, `dd()`, atau data contoh yang tertinggal.
6. Props Inertia punya tipe TypeScript.
7. Pint, ESLint, dan `php artisan test` lulus.
8. Teks antarmuka berbahasa Indonesia dan konsisten dengan istilah yang dipakai dokumen ini.
9. **Terbukti hidup setelah di-deploy ke Railway**, bukan hanya jalan di komputer lokal.
10. Bila skema berubah, **seeder ikut diperbarui** — `migrate:fresh --seed` harus tetap
    menghasilkan sistem yang utuh dan bisa langsung dipakai.
11. Setiap migration baru diuji `migrate` **dan** `migrate:rollback` — `down()` yang tidak benar
    baru terasa sakitnya setelah ada data produksi. *(Diserap dari bekas F1.8.2.)*
12. `docs/` diperbarui bila perilaku atau skema berubah. Bila kode dan dokumen bertentangan,
    dokumen yang benar. *(Diserap dari bekas F1.8.5.)*

> Butir 11 dan 12 naik ke sini ketika F1.8 dicabut. Keduanya sudah tertulis di
> [`.claude/rules/30-database.md`](../.claude/rules/30-database.md) dan
> [`.claude/rules/60-testing-dan-git.md`](../.claude/rules/60-testing-dan-git.md); dinaikkan ke
> DoD supaya tidak ada satu pun bekas isi F1.8 yang hilang tanpa rumah.

## Risiko

| Risiko | Dampak | Penanganan |
|--------|--------|------------|
| **Database dev = database deploy (R2)** | Aman selama isinya data seeder; berubah jadi berbahaya begitu ada satu registrasi nyata | Seeder lengkap & idempoten sebagai jalur pulih; [batas pemakaian `migrate:fresh`](#kapan-migratefresh-berhenti-boleh) ditulis eksplisit. **Dipisahkan di F2.5.1 sebelum go-live** — tugas bernomor, bukan janji longgar |
| Seeder tidak ikut diperbarui saat skema berubah | `migrate:fresh --seed` menghasilkan sistem setengah isi, dan itu baru ketahuan di device satunya | DoD #10 pada setiap tugas; putaran pembuktiannya di F2.5.11 — dengan F1.8 dicabut, tidak ada lagi pemeriksaan terpusat di tengah jalan, jadi DoD #10 yang harus benar-benar ditegakkan per tugas |
| Kuota gratis Cloudinary terlampaui | Gambar katalog gagal tampil | Pantau pemakaian; transformasi `f_auto,q_auto` menekan bandwidth; kredensial terpusat di `ImageUploader` sehingga pindah penyedia hanya menyentuh satu berkas |
| Filesystem Railway ephemeral | Berkas yang tidak sengaja ditulis ke disk hilang saat redeploy | Seluruh unggahan lewat `ImageUploader` → Cloudinary. Session, cache, dan queue memakai driver `database`, bukan `file` |
| Pesan form kontak tak terbaca sampai F2.2 | Calon pelanggan mengira diabaikan | Halaman kontak menonjolkan tombol WhatsApp langsung sebagai jalur utama; form hanya jalur cadangan |
| Aturan slot lama (kuota 2/jam) ternyata tidak sesuai kapasitas bengkel | Slot penuh palsu atau bengkel kebanjiran | Nilai di `config/booking.php` — bisa diubah tanpa menyentuh kode; `SlotService` sudah membaca `slots_by_weekday` |
| Notifikasi WA bergantung kedisiplinan advisor menekan kirim | Pelanggan tidak menerima kabar | ✅ **Penangkalnya terpasang sejak Tahap 10.** Penanda "Belum dikabari" di detail booking, kartu berangka di dashboard yang bisa diklik ke daftar tersaring, dan tombol **Lewati** supaya angkanya mencerminkan keadaan sebenarnya. Ukurannya: bila sesudah dua minggu angka itu tidak pernah turun ke nol, definisinya yang salah — bukan advisornya |
| Seeder menimpa teks template yang sudah disunting Super Admin | Hasil kerja pemilik bengkel hilang tanpa jejak pada setiap `db:seed` | `WhatsAppTemplateSeeder` hanya membuat baris yang belum ada dan **tidak pernah** menyentuh yang sudah ada — satu-satunya seeder produksi yang bukan `updateOrCreate`. Diuji: seed → sunting → seed ulang → suntingan bertahan |
| Reset password tanpa SMTP ([09 §9.2](09-keamanan-hak-akses.md#92-autentikasi)) | Pelanggan terkunci dari akunnya | Pastikan SMTP tersedia, atau sediakan alur reset lewat admin sebelum go-live (F2.5) |
| Password sementara akun **staf** ditampilkan di flash message (F2.2.3) | Kredensial yang membuka panel internal melintas lewat session dan layar admin — lebih berat daripada kasus customer di F1.5 | `must_reset_password` ditegakkan sejak login pertama (F2.2.5), jadi masa berlakunya sependek satu kali masuk; nilainya tidak tersimpan terbaca di mana pun. Ditinjau ulang begitu SMTP tersedia (F2.5) |
| Akun staf hanya dari seeder sampai F2.2 (**R9**) | Menambah advisor sungguhan menuntut `tinker`/DBeaver; matriks hak akses hanya teruji terhadap akun contoh | Dapat diterima selama sistem dipakai developer saja (**R5**). Diperpendek oleh urutan sekarang: A11 datang di Tahap 9, bukan menjelang akhir. `AccessMatrixTest` menyusul bersamanya di F2.2.7 |
| Tidak ada putaran verifikasi tersendiri di tengah jalan (F1.8 dicabut) | Cacat paritas, seeder tak lengkap, atau regresi responsif baru ketahuan menjelang go-live — saat memperbaikinya paling mahal | Definition of Done ditegakkan **per tugas**, bukan ditumpuk ke akhir fase; F2.5.10–F2.5.11 tetap menjalankan `/audit-paritas` dan putaran `migrate:fresh --seed` sebelum rilis. Yang tidak boleh terjadi: go-live mendahului F2.5 |
| Scope melebar (stok sparepart, penugasan mekanik) | Jadwal meleset | Sudah ditetapkan Won't do di [02 §2.6](02-kebutuhan-produk.md#26-batas-scope-wont-do--fase-ini) |

---

## Tahap yang dicabut

Dua sub-fase pernah ada lalu dibatalkan. **Tidak satu pun fiturnya hilang** — seluruhnya
dipindahkan, dan tujuannya tercatat di tabel masing-masing. Riwayat ini disimpan supaya
rujukan `F1.7`/`F1.8` yang masih tersebar di komentar kode dan dokumen lain tetap bisa
ditelusuri. Nomornya sengaja tidak dipakai ulang.

### `F1.7` — dicabut

Sub-fase ini pernah berisi **A11 Pengguna Internal** beserta penegakan `must_reset_password`,
ditarik maju oleh keputusan R9 versi sebelumnya. **Seluruhnya dikembalikan ke Tahap 9** — lihat [R9](#keputusan-yang-membentuk-roadmap-ini) untuk alasan dan konsekuensinya.

| Bekas butir | Sekarang |
|-------------|----------|
| A11: layar `/admin/users`, `UserService`, `UserPolicy` staf, menu "Sistem" | **F2.2.3–F2.2.4** |
| Penegakan `must_reset_password` | **F2.2.5**, satu paket dengan A11 |
| `AccessMatrixTest` (A14) | **F2.2.7** — sempat singgah di F1.8 sebelum F1.8 ikut dicabut |

Nomornya sengaja tidak dipakai ulang: tidak ada sub-fase lain yang naik menjadi F1.7.

Yang perlu diingat selama tahap-tahap sebelum A11 ada:

- Akun staf hanya lahir dari `UserSeeder`. Menambah advisor berarti `tinker` atau DBeaver.
- `must_reset_password` tetap **ditulis** dengan benar oleh 1.5.4 dan 1.5.6, hanya belum
  dipaksakan saat login. Selama kolom itu hanya mengenai akun customer — yang password acaknya
  memang tidak diketahui siapa pun — dampaknya kecil.
- Alur demo ke pemilik bengkel memakai akun advisor bawaan seeder. Itu batas yang harus
  disebutkan saat mendemokan, bukan disembunyikan.

### `F1.8` — dicabut

Sub-fase stabilisasi tersendiri dihapus. Sebagian besar isinya menduplikasi
[Definition of Done](#definition-of-done) yang sudah berlaku pada
**setiap** tugas — gerbang kualitas (DoD #7) dan bersih dari `console.log`/`dd()` (DoD #5).
Menjadwalkannya ulang sebagai sub-fase berarti menghitung pekerjaan yang sama dua kali. Dua
butir yang **belum** ada di DoD dinaikkan ke sana sebagai #11 dan #12 supaya tidak hilang.

| Bekas butir | Sekarang |
|-------------|----------|
| 1.8.1 gerbang kualitas · 1.8.3 bersih dari sisa debug | **DoD #7 dan #5** — sudah ada, berlaku per tugas |
| 1.8.2 `migrate`/`rollback` · 1.8.5 pembaruan `docs/` | **DoD #11 dan #12** — butir baru |
| 1.8.4 `/audit-paritas` terhadap prototipe lama | **F2.5.10** |
| 1.8.6 satu putaran `migrate:fresh --seed` + telusur alur penuh | **F2.5.11** |
| 1.8.7 uji responsif 360/768/1280 | digabung ke **F2.5.6** (uji lintas peramban) |
| 1.8.8 `AccessMatrixTest` (A14) | **F2.2.7** — dibuat sekaligus lengkap dengan baris A11 |

`AccessMatrixTest` terpaksa ikut mundur karena F1.8 tidak ada lagi. Itu justru
menyederhanakannya: berkasnya dibuat **sekali** dengan seluruh baris matriks termasuk A11,
bukan dibuat sebagian lalu ditambahi.
