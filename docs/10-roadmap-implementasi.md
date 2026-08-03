# 10 — Roadmap Implementasi

Pengerjaan dibagi menjadi **dua Big Fase**:

| Big Fase | Tujuan | Keadaan akhir |
|----------|--------|---------------|
| **Big Fase 1** | Sistem inti berjalan penuh dan hidup di Railway | Pelanggan bisa mendaftar → melihat katalog → memesan servis; advisor bisa mengelola booking, jadwal, customer, katalog, dan paket layanan. Dipakai **developer saja**, belum diumumkan ke pelanggan. |
| **Big Fase 2** | Pematangan operasional + go-live | Dashboard, invoice, laporan, notifikasi WA penuh, modul konten, activity log, pengerasan keamanan, pemisahan database produksi, rilis publik. |

Estimasi memakai satuan **hari kerja untuk satu pengembang**.

---

## Keputusan yang membentuk roadmap ini

Delapan keputusan berikut diambil pada revisi 3 Agustus 2026 dan menjadi dasar pembagian fase.
Bila salah satunya berubah, roadmap ini ikut berubah.

| # | Keputusan | Konsekuensi |
|---|-----------|-------------|
| R1 | **Booking masuk Big Fase 1 secara penuh** — customer memesan sendiri *dan* admin mengelola | Inti booking (`SlotService`, state machine, kuota) tidak bisa ditunda; F1.4 & F1.5 wajib. |
| R2 | **Satu database Railway** untuk development sekaligus deployment | Dikerjakan satu orang yang berpindah laptop ↔ PC kantor, jadi data otomatis seragam tanpa sinkronisasi apa pun. `migrate:fresh --seed` **boleh** selama isinya masih data seeder — batasnya di [§Kapan `migrate:fresh` berhenti boleh](#kapan-migratefresh-berhenti-boleh). Dipisahkan di F2.5.1. |
| R3 | **Gambar disimpan di Cloudinary**, bukan filesystem | Filesystem Railway ephemeral. Konsekuensi skema: kolom `*_path` diganti `*_public_id` + `*_url`. Ekstensi PHP `gd` tidak lagi dibutuhkan. |
| R4 | **Fasilitas, FAQ, testimoni: tabel DB + seeder idempoten sejak Big Fase 1** | Landing page membaca DB sejak awal; layar CRUD-nya (A10) menyusul di Big Fase 2 tanpa kerja ulang di landing page. |
| R5 | **Big Fase 1 dipakai developer saja** | Pengerasan (halaman galat, CSP, rate limit menyeluruh, backup, uji lintas peramban) tetap di Big Fase 2. |
| R6 | **Notifikasi WA di Big Fase 1 = tombol manual sederhana** | Tombol "Chat via WhatsApp" di detail booking, teks dari `config`. Tanpa tabel template, tanpa log kirim — itu A7 di Big Fase 2. |
| R7 | **Slot Sabtu berhenti di 13:00** — menutup [Q1](README.md#pertanyaan-terbuka) | `config/booking.php` memakai `slots_by_weekday` untuk hari Sabtu. Tidak ada perubahan kode `SlotService`. |
| R8 | **`/admin` mengalihkan ke `/admin/bookings`** | A1 Dashboard ditunda utuh; `pages/admin/dashboard.tsx` dari Fase 0 dihapus, bukan diisi setengah. |

## Modul admin: yang masuk dan yang ditunda

| Modul | Big Fase 1 | Big Fase 2 |
|-------|:----------:|:----------:|
| A1 — Dashboard | ❌ (`/admin` → redirect) | ✅ |
| A2 — Manajemen Booking + walk-in | ✅ | — |
| A3 — Jadwal / Okupansi | ✅ | — |
| A4 — Katalog Mobil | ✅ | — |
| A5 — Paket Layanan | ✅ | — |
| A6 — Customer & Kendaraan | ✅ | — |
| A7 — Notifikasi WhatsApp | ⚠️ tombol manual saja (R6) | ✅ penuh |
| A8 — Invoice | ❌ | ✅ |
| A9 — Laporan & Export | ❌ | ✅ |
| A10 — Konten landing page | ⚠️ tabel + seeder saja (R4) | ✅ layar CRUD |
| A11 — Pengguna Internal | ❌ | ✅ |
| A12 — Activity Log | ❌ | ✅ |
| A13 — Navigasi panel | ⚠️ menu untuk A2–A6 saja | ✅ menu penuh |
| A14 — Matriks hak akses | ⚠️ **Policy untuk A2–A6 wajib ada** | ✅ matriks penuh |

> **A13 dan A14 tidak bisa ditunda sepenuhnya.** A13 hanyalah spesifikasi menu — tanpa menu,
> layar A2–A6 tidak bisa dicapai. A14 hanyalah ringkasan matriks — otorisasinya sendiri
> (Policy tiap sumber daya, pembatasan SA-saja pada A4 & A5) **wajib** ikut Big Fase 1.
> Menunda otorisasi berarti mengulang temuan S3 sistem lama.

---

# Big Fase 1 — Sistem Inti Berjalan Penuh

## F1.0 — Fondasi  (2 hari) — ✅ SELESAI

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

## F1.1 — Instalasi, Database Bersama & Deploy Railway  (2 hari) — ⚠️ HAMPIR SELESAI

Fase ini didahulukan agar **setiap commit berikutnya langsung terbukti bisa di-deploy**, dan agar
semua komputer developer memandang data yang sama sejak hari pertama. Panduan langkah demi
langkah ada di [12-panduan-instalasi-deploy.md](12-panduan-instalasi-deploy.md).

> **Keadaan per 3 Agustus 2026.** Situs sudah hidup di Railway dan komputer developer sudah
> memandang database yang sama.
>
> **Selesai:** 1.1.2 service MySQL + TCP proxy · 1.1.3 berkas build · 1.1.4 Variables ·
> 1.1.5 `trustProxies` · 1.1.6 Cloudinary (akun + kredensial di Railway) · 1.1.7 `.env.example` ·
> 1.1.8 panduan DBeaver · 1.1.9 batas `migrate:fresh` · 1.1.10 workflow CI ·
> dan penautan Railway ↔ repo pada 1.1.1.
>
> **Belum selesai:**
> 1. **Proteksi branch `main`** (sisa 1.1.1) — masih bisa push langsung ke `main`.
> 2. **Seeder belum pernah dijalankan** terhadap DB Railway; tabel `users` masih 0 baris,
>    sehingga kriteria "sistem yang langsung bisa dipakai" belum terbukti.
> 3. **`cloudinary:cek` belum hijau** — `CLOUDINARY_URL` ada di Variables Railway tetapi
>    belum diisi di `.env` lokal.
> 4. **Baru satu komputer** yang diverifikasi; janji "dua komputer melihat hasil identik"
>    belum diuji.
>
> Daftar periksa beserta buktinya di
> [12 §12.8](12-panduan-instalasi-deploy.md#128-daftar-periksa-selesai-f11).
>
> **Penyimpangan dari rencana, disengaja:**
> 1. **`App\Services\ImageUploader` menjadi interface**, bukan kelas konkret seperti tertulis di
>    1.1.6. Tanpa itu `FakeImageUploader` tidak bisa menggantikannya di uji. Implementasi
>    sungguhannya `CloudinaryImageUploader`. Polanya sama dengan `WhatsAppNotifier` di F2.2.2.
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

## F1.2 — Autentikasi & Data Inti  (3 hari) — ✅ SELESAI

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
| 1.2.2 | Migration konten landing: `facilities`, `faqs`, `testimonials`, `contact_messages` (**R4** — tabel dulu, layar CRUD-nya di Big Fase 2) |
| 1.2.3 | Model + relasi + `$fillable` + casts enum + factory tiap model |
| 1.2.4 | Register (nama, email, WA, password) dengan normalisasi nomor + rate limit |
| 1.2.5 | Login/logout/reset password; blokir akun `is_active = false` |
| 1.2.6 | Seeder idempoten: paket layanan (10 kode lama), katalog mobil, fasilitas, FAQ, testimoni |
| 1.2.7 | CRUD kendaraan milik customer + komponen `PlateInput` (prefix 1–2 huruf) |
| 1.2.8 | Uji: registrasi, login, normalisasi nomor, kepemilikan kendaraan, plat 1 & 2 huruf |

**Selesai bila:** pelanggan bisa mendaftar, masuk, dan menyimpan kendaraannya; seluruh tabel
inti + konten sudah terisi seeder di database Railway bersama.

## F1.3 — Master Admin: Katalog & Paket Layanan  (3 hari) — ✅ SELESAI

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

## F1.4 — Booking End-to-End (Customer)  (4 hari) — ✅ SELESAI

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

## F1.5 — Admin Operasional: A2, A3, A6  (3 hari) — ✅ SELESAI

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
>    Yang terakhir sudah memakai nama dari [03 §3.6](03-arsitektur-teknis.md) supaya F2.2 cukup
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
>    `/admin/customers/{id}` — pengelolaannya milik A11 di Big Fase 2, yang punya pengaman
>    berbeda (Super Admin terakhir tidak boleh dinonaktifkan).
> 8. **Total nilai invoice di detail customer belum ada**, meski disebut [07 §A6](07-modul-admin.md).
>    Tabel invoice baru lahir di F2.3; menampilkan "Rp 0" untuk sesuatu yang belum dihitung lebih
>    menyesatkan daripada tidak menampilkannya.
> 9. **Teks WhatsApp ada di `config/company.php` (`wa_messages`), berkunci status booking.**
>    Sementara, sesuai R6 — tabel `whatsapp_templates` yang bisa disunting Super Admin, log
>    pengiriman, dan penanda "sudah dikirim" menyusul di F2.2.
>
> **Sisa yang belum terbukti:**
> - **`must_reset_password` belum ditegakkan saat login.** Kolomnya diisi dengan benar (akun
>   walk-in baru dan akun yang direset Super Admin), tetapi belum ada pemaksaan ganti password di
>   alur masuk. Selama Big Fase 1 dampaknya nihil — akun walk-in dibuat dengan password acak yang
>   tidak diketahui siapa pun, jadi pemiliknya memang harus lewat jalur reset. Penegakannya masuk
>   Big Fase 2 bersama A11.
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

## F1.6 — Landing Page Publik  (4 hari)

Menampilkan data yang sudah dikelola admin di F1.3 (katalog, paket layanan) dan data seeder
untuk yang belum punya layar admin (fasilitas, FAQ, testimoni — **R4**).

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
> layar untuk membacanya** sampai A10 dibangun di F2.4. Selama Big Fase 1 pesan hanya bisa
> dilihat lewat DBeaver. Ini konsekuensi langsung dari menunda A10, dan tercatat di
> [tabel risiko](#risiko).

## F1.7 — Stabilisasi Big Fase 1  (1 hari)

| # | Pekerjaan |
|---|-----------|
| 1.7.1 | Seluruh gerbang kualitas hijau: `php artisan test`, Pint, PHPStan, ESLint, `tsc --noEmit`, `npm run build` |
| 1.7.2 | Setiap migration baru diuji `migrate` **dan** `rollback` — `down()` yang tidak benar baru terasa sakitnya setelah ada data produksi |
| 1.7.3 | Bersih dari `console.log`, `dd()`, `dump()`, data contoh yang tertinggal |
| 1.7.4 | `/audit-paritas` terhadap prototipe lama untuk fitur yang masuk Big Fase 1 |
| 1.7.5 | Dokumen `docs/` diperbarui bila ada perilaku atau skema yang berubah selama pengerjaan |
| 1.7.6 | Satu putaran `migrate:fresh --seed` terhadap DB Railway, lalu telusuri alur penuh — membuktikan seeder masih lengkap dan kedua device bisa disamakan dengan satu perintah |

**Big Fase 1 selesai bila:** satu alur penuh berhasil di lingkungan Railway — daftar → tambah
kendaraan → pilih paket → pilih slot → booking dibuat → advisor mengonfirmasi → status berubah →
customer melihatnya di riwayat.

---

# Big Fase 2 — Pematangan Operasional & Go-Live

## F2.1 — Dashboard & Laporan  (3 hari)

| # | Pekerjaan |
|---|-----------|
| 2.1.1 | **A1** Dashboard: KPI, grafik tren 30 hari (Recharts), okupansi slot hari ini, tabel booking hari ini, peringatan calon `no_show` |
| 2.1.2 | A1 aksi cepat (Konfirmasi · Mulai · Selesai) lewat Inertia partial reload |
| 2.1.3 | `/admin` dikembalikan ke dashboard, redirect F1.5.1 dicabut |
| 2.1.4 | **A9** Laporan: rekap booking, okupansi, customer baru; pendapatan **SA saja** |
| 2.1.5 | A9 Export Excel (`maatwebsite/excel`, mengikuti filter aktif) + Salin untuk Spreadsheet (TSV) |

## F2.2 — Notifikasi WhatsApp Penuh  (2 hari)

| # | Pekerjaan |
|---|-----------|
| 2.2.1 | Migration `whatsapp_templates`, `whatsapp_messages` + seeder 6 template |
| 2.2.2 | Interface `WhatsAppNotifier` + implementasi `ClickToChatNotifier`; tombol sederhana F1.5.7 diganti |
| 2.2.3 | Panel draft di detail booking + tandai terkirim + penanda "belum dikirim" yang menonjol |
| 2.2.4 | **A7** CRUD template WA (SA) dengan validasi placeholder |

## F2.3 — Invoice  (3 hari)

| # | Pekerjaan |
|---|-----------|
| 2.3.1 | Migration `invoices`, `invoice_items` + `InvoiceService` (seluruh perhitungan di server) |
| 2.3.2 | **A8** penyunting invoice, terbitkan, tandai lunas, void (SA saja, wajib alasan) |
| 2.3.3 | PDF invoice (`barryvdh/laravel-dompdf`) |
| 2.3.4 | Halaman invoice untuk customer + unduh PDF |
| 2.3.5 | Uji: total dihitung server, invoice `issued` tidak bisa disunting, advisor tidak bisa `void` |

## F2.4 — Konten, Pengguna & Audit  (3 hari)

| # | Pekerjaan |
|---|-----------|
| 2.4.1 | **A10** layar CRUD `/admin/fasilitas`, `/admin/faq`, `/admin/testimoni` (tabelnya sudah ada sejak F1.2.2 — tidak ada migrasi data) |
| 2.4.2 | **A10** `/admin/pesan-masuk`: tandai dibaca, balas via WA, hapus spam; lencana jumlah belum dibaca di `AdminLayout` |
| 2.4.3 | **A11** pengguna internal + pengaman (SA tidak bisa menurunkan role sendiri; SA terakhir tidak bisa dinonaktifkan) |
| 2.4.4 | **A12** activity log (`spatie/laravel-activitylog`), retensi 12 bulan |
| 2.4.5 | **A13** navigasi panel lengkap + **A14** verifikasi matriks hak akses lewat uji otomatis per baris matriks |

## F2.5 — Pengerasan & Go-Live  (4 hari)

| # | Pekerjaan |
|---|-----------|
| **2.5.1** | **Pisahkan database produksi dari database development** — akhir dari keputusan R2. Service MySQL kedua di Railway, `.env` developer diarahkan ke DB dev; sejak titik ini `migrate:fresh` hanya boleh menyentuh DB dev |
| 2.5.2 | Backup terjadwal + **uji restore** (tanpa uji restore, backup hanya asumsi) |
| 2.5.3 | Halaman galat 403/404/419/500 berbahasa Indonesia |
| 2.5.4 | Header keamanan, CSP, rate limit menyeluruh sesuai [09 §9](09-keamanan-hak-akses.md) |
| 2.5.5 | Optimasi: transformasi Cloudinary (`f_auto,q_auto`) responsif, lazy-load, `config:cache`, `route:cache`, `view:cache` di build Railway |
| 2.5.6 | Uji lintas peramban (Chrome, Safari iOS, Firefox) & perangkat nyata |
| 2.5.7 | Domain kustom + SSL; `APP_ENV=production`, `APP_DEBUG=false` diverifikasi |
| 2.5.8 | *Opsional:* perintah `booking:import-firebase` bila data lama perlu dibawa |
| 2.5.9 | Panduan singkat untuk admin (PDF 2 halaman) + serah terima |

**Selesai bila:** seluruh daftar periksa [09 §9.10](09-keamanan-hak-akses.md#910-daftar-periksa-sebelum-rilis) tercentang.

---

## Ringkasan Jadwal

| Big Fase | Sub-fase | Hari | Kumulatif |
|----------|----------|-----:|----------:|
| **1** | F1.0 — Fondasi ✅ | 2 | 2 |
| **1** | F1.1 — Instalasi, DB bersama & Railway | 2 | 4 |
| **1** | F1.2 — Autentikasi & data inti | 3 | 7 |
| **1** | F1.3 — Master admin (A4, A5) | 3 | 10 |
| **1** | F1.4 — Booking end-to-end | 4 | 14 |
| **1** | F1.5 — Admin operasional (A2, A3, A6) | 3 | 17 |
| **1** | F1.6 — Landing page publik | 4 | 21 |
| **1** | F1.7 — Stabilisasi | 1 | **22** |
| **2** | F2.1 — Dashboard & laporan | 3 | 25 |
| **2** | F2.2 — WhatsApp penuh | 2 | 27 |
| **2** | F2.3 — Invoice | 3 | 30 |
| **2** | F2.4 — Konten, pengguna, audit | 3 | 33 |
| **2** | F2.5 — Pengerasan & go-live | 4 | **37** |

Big Fase 1 ≈ **4,5 minggu kerja** (2 hari sudah selesai → sisa 20 hari). Total ≈ 7,5 minggu untuk
satu pengembang. Bila dikerjakan dua orang, F1.6 dapat berjalan paralel dengan F1.4–F1.5 sesudah
F1.3 selesai.

Angka ini **lebih besar dari roadmap versi sebelumnya (25 hari)** karena bertambah dua pekerjaan
nyata yang dulu tidak ada: penyiapan Railway + Cloudinary + database bersama (F1.1, 2 hari) dan
pemisahan database produksi menjelang rilis (F2.5.1, bagian dari 4 hari).

## Definition of Done (berlaku untuk setiap tugas)

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

## Risiko

| Risiko | Dampak | Penanganan |
|--------|--------|------------|
| **Database dev = database deploy (R2)** | Aman selama isinya data seeder; berubah jadi berbahaya begitu ada satu registrasi nyata | Seeder lengkap & idempoten sebagai jalur pulih; [batas pemakaian `migrate:fresh`](#kapan-migratefresh-berhenti-boleh) ditulis eksplisit. **Dipisahkan di F2.5.1 sebelum go-live** — tugas bernomor, bukan janji longgar |
| Seeder tidak ikut diperbarui saat skema berubah | `migrate:fresh --seed` menghasilkan sistem setengah isi, dan itu baru ketahuan di device satunya | DoD #10; F1.7 memeriksa satu putaran `migrate:fresh --seed` sebelum Big Fase 1 ditutup |
| Kuota gratis Cloudinary terlampaui | Gambar katalog gagal tampil | Pantau pemakaian; transformasi `f_auto,q_auto` menekan bandwidth; kredensial terpusat di `ImageUploader` sehingga pindah penyedia hanya menyentuh satu berkas |
| Filesystem Railway ephemeral | Berkas yang tidak sengaja ditulis ke disk hilang saat redeploy | Seluruh unggahan lewat `ImageUploader` → Cloudinary. Session, cache, dan queue memakai driver `database`, bukan `file` |
| Pesan form kontak tak terbaca sampai F2.4 | Calon pelanggan mengira diabaikan | Halaman kontak menonjolkan tombol WhatsApp langsung sebagai jalur utama; form hanya jalur cadangan |
| Aturan slot lama (kuota 2/jam) ternyata tidak sesuai kapasitas bengkel | Slot penuh palsu atau bengkel kebanjiran | Nilai di `config/booking.php` — bisa diubah tanpa menyentuh kode; `SlotService` sudah membaca `slots_by_weekday` |
| Notifikasi WA bergantung kedisiplinan advisor menekan kirim | Pelanggan tidak menerima kabar | F2.2.3 menampilkan penanda "belum dikirim" yang menonjol; dashboard F2.1 menampilkan hitungan draft |
| Reset password tanpa SMTP ([09 §9.2](09-keamanan-hak-akses.md#92-autentikasi)) | Pelanggan terkunci dari akunnya | Pastikan SMTP tersedia, atau sediakan alur reset lewat admin sebelum go-live (F2.5) |
| Scope melebar (stok sparepart, penugasan mekanik) | Jadwal meleset | Sudah ditetapkan Won't do di [02 §2.6](02-kebutuhan-produk.md#26-batas-scope-wont-do--fase-ini) |
