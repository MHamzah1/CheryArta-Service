# 12 — Panduan Instalasi, Database Bersama & Deploy Railway

Dokumen ini menutup tugas **F1.1** di [roadmap](10-roadmap-implementasi.md#f11--instalasi-database-bersama--deploy-railway--2-hari).
Tujuannya satu: **siapa pun, di komputer mana pun, menjalankan proyek ini dan melihat data yang
sama — dan setiap `git push` ke `main` langsung hidup di Railway.**

> **Jangan pernah menuliskan kredensial nyata di dokumen ini, di `.env.example`, atau di seeder.**
> Seluruh nilai rahasia hanya hidup di `.env` lokal (tidak di-commit) dan di panel Variables Railway.

---

## 12.1 Peta Lingkungan

```
GitHub (repo, branch main)
   │  push
   ▼
Railway Project ──────────────────────────────────────────┐
   ├── Service "web"    : Laravel + Vite  (auto-deploy)   │
   └── Service "MySQL"  : database                        │
          ├── jaringan privat  → dipakai service "web"    │
          └── TCP proxy publik → dipakai laptop developer │
                                  dan DBeaver             │
Cloudinary  : seluruh gambar & brosur PDF ────────────────┘
```

**Keputusan yang perlu diingat** (asalnya di [roadmap §Keputusan](10-roadmap-implementasi.md#keputusan-yang-membentuk-roadmap-ini)):

- **R2** — untuk sementara **hanya ada satu database**, dipakai bersama oleh development dan
  deployment. Proyek ini dikerjakan **satu orang yang berpindah laptop ↔ PC kantor**, jadi
  database bersama itulah yang membuat data seragam tanpa sinkronisasi apa pun.
  `migrate:fresh --seed` diperbolehkan — batasnya di [§12.6](#126-database-bersama-satu-developer-dua-device).
- **R3** — filesystem Railway *ephemeral*: apa pun yang ditulis ke disk hilang saat redeploy.
  Karena itu **tidak ada** `storage:link` dan tidak ada berkas unggahan di disk. Semua ke Cloudinary.

---

## 12.2 Instalasi di Komputer Baru

Prasyarat: **PHP 8.2+**, **Composer 2**, **Node.js 20+**, **Git**.
MySQL lokal **opsional** — hanya dibutuhkan untuk menguji migration (lihat §12.6.4).

```bash
git clone <url-repo> CheryArta-Service
cd CheryArta-Service

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Lalu buka `.env` dan isi bagian rahasianya. Nilainya diambil dari Railway (§12.3) dan
Cloudinary (§12.5) — **minta ke pemilik proyek, jangan menebak**:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta

# Database bersama di Railway — lewat TCP proxy publik
DB_CONNECTION=mysql
DB_HOST=<RAILWAY_TCP_PROXY_DOMAIN>
DB_PORT=<RAILWAY_TCP_PROXY_PORT>
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=<MYSQLPASSWORD>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

CLOUDINARY_URL=cloudinary://<api_key>:<api_secret>@<cloud_name>
CLOUDINARY_FAKE=false
```

> Belum punya akses Cloudinary? Setel `CLOUDINARY_FAKE=true`. Seluruh unggahan dialihkan ke
> `FakeImageUploader` sehingga aplikasi tetap jalan tanpa satu pun panggilan jaringan. Yang
> **tidak** dilakukan: diam-diam mundur ke Fake saat `CLOUDINARY_URL` kosong — unggahan yang
> "berhasil" tetapi tidak sampai ke mana pun jauh lebih sulit disadari daripada galat terang.

Verifikasi — **tanpa** menjalankan migration apa pun:

```bash
php artisan migrate:status     # harus menampilkan daftar migration beserta status "Ran"
php artisan tinker --execute="echo \App\Models\User::count();"
php artisan cloudinary:cek     # unggah 1 gambar uji ke Cloudinary, lalu hapus lagi
```

Bila dua komputer memberi angka yang sama, database bersama sudah bekerja. Jalankan:

```bash
npm run dev          # terminal 1
php artisan serve    # terminal 2  → http://localhost:8000
```

> **Yang membuat data seragam adalah koneksi ke database yang sama, bukan seeder** — di device
> kedua biasanya tidak ada yang perlu dijalankan sama sekali; datanya sudah ada di sana.
>
> Bila keadaannya terasa kacau (skema tertinggal, data percobaan menumpuk),
> `php artisan migrate:fresh --seed` boleh dipakai untuk mengembalikan keadaan bersih —
> selama syarat [§12.6.2](#1262-kapan-berhenti) masih terpenuhi.

---

## 12.3 Menyiapkan Railway

### 12.3.1 Project & service

1. Buat project baru di Railway → **Deploy from GitHub repo** → pilih repo ini, branch `main`.
2. Di project yang sama, **+ New → Database → MySQL**.
3. Buka service MySQL → tab **Variables** untuk melihat `MYSQLHOST`, `MYSQLPORT`,
   `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`.
4. Service MySQL → **Settings → Networking → Public Networking → enable TCP Proxy**.
   Railway memunculkan `RAILWAY_TCP_PROXY_DOMAIN` dan `RAILWAY_TCP_PROXY_PORT` — **ini yang
   dipakai laptop developer dan DBeaver**, bukan host internal.

### 12.3.2 Variables service `web`

Isi lewat panel Variables. Nilai database memakai **referensi antar-service** agar ikut berubah
sendiri bila Railway memutar kredensial:

```dotenv
APP_NAME="Chery Arta"
APP_ENV=production
APP_KEY=<hasil `php artisan key:generate --show`>
APP_DEBUG=false
APP_URL=https://<subdomain>.up.railway.app
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stderr
FILESYSTEM_DISK=local
PHP_CLI_SERVER_WORKERS=4

CLOUDINARY_URL=cloudinary://<api_key>:<api_secret>@<cloud_name>
COMPANY_WA_NUMBER=62xxxxxxxxxx
```

Catatan yang mudah terlewat:

- **`APP_KEY` wajib sama** dengan yang dipakai saat data terenkripsi dibuat. Jangan generate ulang
  di Railway setelah ada data.
- **`LOG_CHANNEL=stderr`** — log ke stdout container, bukan ke `storage/logs` yang akan hilang.
- **`SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` harus `database`**, bukan `file`.
  Driver `file` di filesystem ephemeral akan membuat pengguna ter-logout acak setiap redeploy.
- **`PHP_CLI_SERVER_WORKERS=4`** — start command memakai `php artisan serve`, dan server bawaan
  PHP hanya melayani satu permintaan pada satu waktu tanpa variabel ini. Cukup untuk Big Fase 1
  yang dipakai developer saja (**R5**); bila kelak ada trafik nyata, ganti ke Nginx + PHP-FPM
  lewat `Dockerfile` sendiri.
- **`CLOUDINARY_FAKE` tidak diisi di Railway.** Nilainya harus `false`/absen agar unggahan benar
  benar sampai ke Cloudinary.

### 12.3.3 Build & start

Dua berkas di akar repo, keduanya sudah ada. `nixpacks.toml` mengatur **bagaimana image dibangun**:

```toml
[phases.setup]
nixPkgs = ["php82", "php82Packages.composer", "nodejs_20"]

[phases.install]
cmds = [
  "composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist",
  "npm ci",
]

[phases.build]
cmds = ["npm run build"]

[start]
cmd = "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=$PORT"
```

Tiga hal yang disengaja:

- **`php82Packages.composer` ikut disebut.** Menuliskan `nixPkgs` berarti *mengganti* daftar
  bawaan Nixpacks, bukan menambahnya — tanpa baris itu fase install gagal di perintah pertama.
- **`config:cache` dijalankan di start, bukan di build.** Variabel Railway baru pasti tersedia
  saat runtime; men-cache config saat build berisiko membekukan nilai kosong.
- **`--host=0.0.0.0 --port=$PORT`** — Railway menyuntikkan `$PORT`; mengabaikannya membuat
  health check gagal.

`railway.json` mengatur **apa yang terjadi saat deploy**, termasuk pre-deploy command sebagai
config-as-code sehingga ikut ter-review lewat git:

```json
{
    "$schema": "https://railway.com/railway.schema.json",
    "build": { "builder": "NIXPACKS" },
    "deploy": {
        "preDeployCommand": "php artisan migrate --force",
        "healthcheckPath": "/up",
        "healthcheckTimeout": 120,
        "restartPolicyType": "ON_FAILURE",
        "restartPolicyMaxRetries": 5
    }
}
```

Migration berjalan sekali per deploy, sebelum kontainer baru menerima trafik. Nilai yang sama
dapat diisi lewat **Settings service `web` → Pre-deploy Command**; bila keduanya terisi, panel
Railway yang menang. Periksa sekali di log deploy pertama bahwa migration memang berjalan —
kalau tidak, isi manual lewat panel.

> Bila skema Nixpacks bawaan ternyata tidak melayani direktori `public/` dengan benar, jalur
> cadangannya adalah `Dockerfile` sendiri (PHP-FPM + Nginx). Coba nixpacks lebih dulu — lebih
> sedikit yang harus dirawat.

### 12.3.4 Trusted proxy — jangan dilewati

Railway menaruh aplikasi di balik reverse proxy. Tanpa penyetelan ini Laravel menyangka
koneksinya `http://`, sehingga aset Vite dan redirect menunjuk ke skema yang salah:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');
})
```

### 12.3.5 Verifikasi deploy

- [ ] Build hijau, service `web` berstatus running
- [ ] Domain Railway terbuka, halaman beranda tampil dengan CSS (bukan HTML polos → tanda `npm run build` gagal)
- [ ] `https://` di address bar, tanpa peringatan mixed content (tanda §12.3.4 sudah benar)
- [ ] Log deploy menampilkan migration berjalan
- [ ] Login berhasil dan **tetap login** setelah refresh (tanda `SESSION_DRIVER=database` benar)

---

## 12.4 Menyiapkan DBeaver

Dipakai untuk melihat isi database bersama, membaca `contact_messages` selama Big Fase 1, dan
memeriksa hasil seeder.

### Langkah

1. **Database → New Database Connection → MySQL → Next**
2. Isi tab **Main**:

   | Kolom | Nilai |
   |-------|-------|
   | Server Host | `RAILWAY_TCP_PROXY_DOMAIN` (mis. `xxx.proxy.rlwy.net`) |
   | Port | `RAILWAY_TCP_PROXY_PORT` (bukan 3306) |
   | Database | `railway` |
   | Username | `root` |
   | Password | `MYSQLPASSWORD` — centang *Save password* |

3. Buka tab **Driver properties**, ubah dua nilai:

   | Properti | Nilai | Alasan |
   |----------|-------|--------|
   | `allowPublicKeyRetrieval` | `true` | MySQL 8 memakai `caching_sha2_password`; tanpa ini koneksi ditolak "Public Key Retrieval is not allowed" |
   | `useSSL` | `false` | Koneksi sudah lewat TCP proxy Railway. Set `true` bila kebijakan Anda mensyaratkan TLS ke database |

4. **Test Connection** — DBeaver menawarkan mengunduh driver MySQL bila belum ada. Terima.
5. **Finish.**

### Dua penyetelan yang layak dinyalakan

Selama Big Fase 1 database ini masih boleh direset (§12.6.1), jadi keduanya bersifat kenyamanan —
bukan pengaman:

1. **Aktifkan mode read-only** bila Anda memang hanya ingin melihat data:
   *Edit Connection* → **Security → Read-only connection**. Mencegah `UPDATE` tak sengaja saat
   menelusuri tabel. Matikan saat perlu menulis.
2. **Beri nama koneksi yang jelas**, mis. `CheryArta — Railway (dev+deploy)`. Setelah **F2.5.1**
   akan ada dua koneksi berbeda; nama yang membedakan sejak sekarang mencegah salah sambung nanti.

> **Setelah F2.5.1**, koneksi ke database **produksi** wajib ditandai
> *Edit Connection* → **General → Connection type: Production** — DBeaver memberinya warna merah,
> meminta konfirmasi sebelum `UPDATE`/`DELETE`, dan mematikan auto-commit.

### Kesalahan yang sering muncul

| Gejala | Sebab |
|--------|-------|
| `Communications link failure` | Memakai host internal (`mysql.railway.internal`) — itu hanya bisa dari dalam Railway. Pakai domain TCP proxy |
| `Public Key Retrieval is not allowed` | `allowPublicKeyRetrieval` belum `true` |
| `Access denied for user` | Password lama; Railway sudah memutar kredensial. Ambil ulang dari tab Variables |
| Tersambung tapi tabel kosong | Salah database. Isi `railway`, bukan `cheryarta_dev` |

---

## 12.5 Menyiapkan Cloudinary

1. Buat akun Cloudinary → **Dashboard** → salin **API Environment variable**, bentuknya
   `cloudinary://<api_key>:<api_secret>@<cloud_name>`.
2. Pasang sebagai `CLOUDINARY_URL` di `.env` lokal **dan** di Variables Railway.
3. Dependensi: SDK resmi (`cloudinary/cloudinary_php`, sudah terpasang), dibungkus service sendiri.

   `App\Services\ImageUploader` adalah **interface** — itulah tipe yang di-inject ke controller
   dan service. Ada dua implementasi:

   | Berkas | Guna |
   |--------|------|
   | `app/Services/ImageUploader.php` | Kontrak: `upload`, `delete`, `transformedUrl` |
   | `app/Services/CloudinaryImageUploader.php` | Implementasi sungguhan; **satu-satunya** berkas yang memanggil SDK |
   | `app/Services/FakeImageUploader.php` | Dipakai uji Pest — mencatat unggahan & penghapusan, nol panggilan jaringan |
   | `app/Support/UploadedAsset.php` | Hasil unggahan (`publicId`, `url`) + `toColumns('brochure_')` |
   | `app/Support/CloudinaryUrl.php` | Penyusun URL transformasi — murni teks, karena itu bisa diuji sendiri |
   | `app/Support/UploadRules.php` | Aturan validasi unggahan, dibaca dari `config/cloudinary.php` |
   | `config/cloudinary.php` | Kredensial, daftar folder, batas ukuran, lebar transformasi |

   **Tidak ada** pemanggilan SDK Cloudinary di controller atau model. Alasannya sama dengan
   `WhatsAppNotifier`: ganti penyedia kelak cukup menyentuh `CloudinaryImageUploader`.

   Pemilihan implementasi terjadi di `AppServiceProvider::register()`: `FakeImageUploader` bila
   sedang menjalankan uji atau `CLOUDINARY_FAKE=true`, selain itu `CloudinaryImageUploader`.
   Bila `CLOUDINARY_URL` kosong sementara `CLOUDINARY_FAKE` tidak diaktifkan, container
   **melempar galat** — bukan diam-diam memakai Fake.

   Verifikasi kredensial tanpa perlu layar unggah (baru ada di F1.3):

   ```bash
   php artisan cloudinary:cek            # unggah 1 gambar uji, tampilkan URL, lalu hapus
   php artisan cloudinary:cek --simpan   # biarkan berkas ujinya ada di Media Library
   ```

4. **Konsekuensi skema** — kolom penyimpanan berkas menyimpan identitas Cloudinary, bukan path:

   | Tabel | Sebelum | Sesudah |
   |-------|---------|---------|
   | `car_model_images` | `path` | `public_id`, `url` |
   | `car_models` | `brochure_path` | `brochure_public_id`, `brochure_url` |
   | `facilities` | `image_path` | `image_public_id`, `image_url` |
   | `testimonials` | `image_path` | `image_public_id`, `image_url` |

   `public_id` disimpan agar berkas bisa dihapus/diganti; `url` disimpan agar render halaman tidak
   perlu memanggil API Cloudinary.

   > ⚠️ **[04-skema-database.md](04-skema-database.md) masih menuliskan kolom `*_path` yang lama
   > dan belum diperbarui.** Tabel di atas adalah bentuk yang benar; dokumen 04 menyusul sebelum
   > migration F1.2.1 ditulis. Bila keputusan Cloudinary (R3) dibatalkan, tabel inilah yang gugur —
   > bukan sebaliknya.

5. **Ukuran gambar tidak lagi diproses di server.** Rencana lama "simpan 2 ukuran webp lewat GD"
   digantikan transformasi Cloudinary di URL:

   ```
   .../upload/f_auto,q_auto,w_400/...     thumbnail
   .../upload/f_auto,q_auto,w_1600/...    penuh
   ```

   Efek sampingnya menyenangkan: **ekstensi PHP `gd` tidak lagi dibutuhkan** — peringatan di
   [README](README.md#keadaan-lingkungan) gugur dengan sendirinya.

   Lebar bakunya hidup di `config('cloudinary.widths')`, dan URL-nya disusun lewat
   `ImageUploader::transformedUrl($url, $width)` — bukan dengan menyambung teks di JSX.

6. **Brosur PDF diunggah sebagai `raw`**, bukan `image`. Akun Cloudinary gratis mematikan
   *PDF and ZIP files delivery* secara bawaan; mengunggah PDF sebagai `image` membuat berkasnya
   tersimpan tetapi tidak bisa diunduh. `AssetType::Document` sudah memetakannya.

7. Validasi unggahan **tetap di server** dan tidak berubah: `jpg|jpeg|png|webp` maks 2 MB
   (brosur `pdf` maks 5 MB), diperiksa lewat MIME sungguhan, nama berkas di-generate ulang
   (`Str::uuid()`, nama asli dibuang seluruhnya). Angkanya hanya hidup di
   `config('cloudinary.uploads')` dan dibaca lewat `UploadRules::for(AssetType::Image)`.
   Cloudinary bukan pengganti validasi.

---

## 12.6 Database Bersama: Satu Developer, Dua Device

Situasinya bukan tim yang berbagi database, melainkan **satu orang yang berpindah perangkat**.
Itu mengubah seluruh perhitungan risikonya.

### 12.6.1 Reset database: boleh

```
php artisan migrate:fresh --seed    ✅
php artisan migrate:refresh         ✅
php artisan db:wipe                 ✅
/db-segar                           ✅   (perintah proyek)
```

Tidak ada rekan yang datanya ikut hilang, dan seluruh isi database berasal dari seeder sehingga
bisa dibangun ulang kapan saja. **Justru inilah cara termudah menyamakan laptop dan PC kantor:**
satu perintah, keadaan seragam, tanpa dump-restore manual.

```bash
php artisan migrate:fresh --seed     # menyamakan keadaan dari device mana pun
```

### 12.6.2 Kapan berhenti

Kelonggaran §12.6.1 bergantung pada dua hal yang punya masa berlaku. **Berhenti** begitu salah
satunya tidak lagi benar:

| Pemicu | Sebabnya |
|--------|----------|
| Ada registrasi, booking, atau pesan kontak dari **orang lain** | Datanya tidak bisa dibuat ulang oleh seeder |
| Aplikasi mulai didemokan ke pemilik bengkel dan mereka mengisi data sungguhan | Sama — data nyata, sekali hilang tidak kembali |
| Masuk **F2.5.1** (pemisahan DB produksi) | Sejak itu `migrate:fresh` hanya untuk database development |

Sejak salah satu terjadi, pindah ke pola §12.6.4 (backup dulu) dan §12.6.5 (migration destruktif
dipecah dua).

### 12.6.3 Tiga akibat yang tetap perlu diingat

| Akibat | Penanganan |
|--------|------------|
| Tabel `sessions` ikut terhapus → siapa pun yang sedang login di situs Railway langsung ter-logout | Jalankan saat situs tidak sedang dipakai atau didemokan |
| Baris gambar hilang, **berkasnya tetap ada di Cloudinary** → menumpuk jadi berkas yatim | Sesekali bersihkan lewat Media Library Cloudinary — foldernya sudah terpisah per jenis (`car-models/`, `facilities/`, …) |
| Seeder menjadi satu-satunya jalan pulih | Lihat §12.6.6 — seeder wajib lengkap, bukan sekadar idempoten |

### 12.6.4 Backup — sekarang opsional, nanti wajib

Perintahnya disiapkan sejak sekarang supaya tidak perlu dicari saat panik:

```bash
mysqldump -h <proxy-host> -P <proxy-port> -u root -p railway \
  > storage/backups/pra-migrate-$(date +%F-%H%M).sql
```

`storage/` sudah ada di `.gitignore` — dump tidak ikut ter-commit. **Wajib dijalankan** sebelum
setiap `migrate` begitu §12.6.2 terpicu.

### 12.6.5 Migration destruktif — pola untuk nanti

Selama belum ada data nyata, `dropColumn`/`renameColumn` langsung saja. Setelah §12.6.2 terpicu,
pakai pola tiga langkah, karena selama pre-deploy migration berjalan kontainer lama masih
melayani trafik dengan kode lama:

1. Migration A — tambah kolom baru (nullable), isi datanya dari kolom lama.
2. Deploy, pastikan aplikasi sudah membaca kolom baru.
3. Migration B — hapus kolom lama.

### 12.6.6 Seeder — tulang punggung, bukan pelengkap

Karena `migrate:fresh --seed` menjadi cara normal menyamakan device, kualitas seeder menentukan
apakah Anda benar-benar bisa pulih:

- Seeder produksi (paket layanan, katalog, fasilitas, FAQ, testimoni) **wajib lengkap** —
  hasil `migrate:fresh --seed` harus sistem yang langsung bisa dipakai, bukan setengah isi.
- Tetap **idempoten** (`updateOrCreate`) supaya aman dijalankan ulang tanpa `fresh`.
- **Setiap kali skema berubah, perbarui seedernya di commit yang sama.** Kalau tidak, `fresh`
  di device satunya akan gagal atau menghasilkan data pincang — dan itu baru ketahuan saat Anda
  sudah berpindah tempat.
- Seeder demo (akun `admin@cheryarta.test` dkk) terkunci `if (app()->isLocal())`. Selama Big
  Fase 1 akun-akun itu memang yang dipakai; **hapus sebelum go-live**
  ([09 §9.10](09-keamanan-hak-akses.md#910-daftar-periksa-sebelum-rilis)).

### 12.6.7 Menguji `down()`

Aturan [30-database](../.claude/rules/30-database.md) tetap mewajibkan setiap migration diuji
`migrate` **dan** `rollback`. Sekarang uji itu boleh langsung di DB Railway. Yang tidak boleh
adalah melewatinya — `down()` yang keliru tidak terasa hari ini, tetapi menjadi jalan buntu saat
sudah ada data produksi dan sebuah deploy harus dibatalkan.

### 12.6.8 Kapan bab ini berakhir

Pada **F2.5.1**, sebelum go-live: dibuat service MySQL kedua di Railway, `.env` developer
dialihkan ke database development, dan database yang sekarang menjadi database produksi. Sejak
saat itu seluruh §12.6.1 hanya berlaku untuk database development.

---

## 12.7 Alur Kerja Harian

```bash
git pull origin main
composer install && npm install     # bila composer.lock / package-lock.json berubah
php artisan migrate:status          # cek ada migration baru dari rekan
npm run dev & php artisan serve
```

Sebelum push:

```bash
./vendor/bin/pint            # tambahkan --test untuk memeriksa tanpa mengubah berkas
./vendor/bin/phpstan analyse
npm run lint                 # atau lint:check
npm run types
npm run build
php artisan test
```

Semua harus lulus — sama seperti [aturan 60](../.claude/rules/60-testing-dan-git.md).
`npm run format` (Prettier) belum menjadi gerbang: 12 berkas warisan F1.0 masih belum terformat,
dan merapikannya layak jadi commit tersendiri agar tidak tercampur dengan perubahan perilaku.

Urutannya bukan selera: `npm run build` **wajib mendahului** `php artisan test`, karena
`resources/views/app.blade.php` memanggil `@vite`, sehingga tanpa `public/build/manifest.json`
setiap uji yang me-render halaman Inertia gagal dengan *Vite manifest not found*.

Workflow `.github/workflows/ci.yml` menjalankan gerbang yang sama pada setiap push ke `main` dan
setiap pull request — dalam **mode periksa** (`pint --test`, `lint:check`, `format:check`).
Workflow yang diam-diam memformat ulang kode selalu hijau dan karena itu tidak menjaga apa pun.
Basis data ujinya SQLite in-memory (`phpunit.xml`); MySQL tidak dibutuhkan di CI.

Push ke `main` memicu deploy Railway; pantau tab **Deployments** sampai hijau, lalu buka domainnya.

## 12.8 Daftar Periksa Selesai (F1.1)

Yang bertanda ✅ sudah ada di repo; sisanya menunggu tindakan di panel Railway, GitHub, dan
Cloudinary — tidak bisa dikerjakan dari dalam kode.

- [ ] Repo GitHub tertaut ke Railway, push ke `main` memicu deploy otomatis
- [ ] Branch `main` dilindungi (Settings → Branches → Require pull request + require `ci`)
- [ ] Service MySQL berjalan, TCP proxy aktif
- [x] ✅ `nixpacks.toml` + `railway.json` (pre-deploy `migrate --force`) ada di repo
- [ ] Deploy pertama membuktikan keduanya bekerja: log menampilkan migration berjalan
- [x] ✅ `trustProxies` terpasang di `bootstrap/app.php`, teruji di `tests/Feature/TrustedProxyTest.php`
- [ ] Situs Railway tampil ber-CSS lewat `https://`
- [x] ✅ `ImageUploader` + `CloudinaryImageUploader` + `FakeImageUploader` ada dan teruji
- [ ] `php artisan cloudinary:cek` hijau memakai `CLOUDINARY_URL` sungguhan
- [x] ✅ `.env.example` mutakhir dan **tanpa** kredensial nyata
- [ ] DBeaver tersambung dari minimal satu komputer, diberi nama `CheryArta — Railway (dev+deploy)`
      (penandaan *Production* baru berlaku setelah F2.5.1 — lihat §12.4)
- [ ] `php artisan migrate:fresh --seed` terhadap DB Railway berhasil dan menghasilkan sistem yang langsung bisa dipakai
- [ ] Laptop dan PC kantor menampilkan hasil `migrate:status` dan jumlah user yang identik
- [x] ✅ Workflow `.github/workflows/ci.yml` menjalankan seluruh gerbang kualitas
