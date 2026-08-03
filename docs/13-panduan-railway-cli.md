# 13 — Panduan Railway CLI

Panduan memakai Railway dari terminal, disusun menurut **apa yang ingin Anda kerjakan**, bukan
menurut urutan abjad perintahnya.

Panduan panel (klik-klik di dashboard) ada di
[12-panduan-instalasi-deploy.md](12-panduan-instalasi-deploy.md). Dokumen ini melengkapinya:
sebagian besar hal di dokumen 12 bisa dikerjakan lebih cepat dari terminal, tetapi ada beberapa
yang **tetap harus lewat panel** — ditandai di §13.9.

Ditulis untuk **Railway CLI 5.30.3**. Perintah yang berubah antar versi bisa diperiksa ulang
dengan `railway <perintah> --help`.

---

## 13.1 Pemasangan & Masuk

```bash
npm install -g @railway/cli     # Node sudah ada di mesin pengembangan
railway --version
railway login
```

`railway login` membuka peramban dan menunggu Anda menekan **Authorize**. Karena menunggu
interaksi, perintah ini **tidak bisa dijalankan oleh skrip atau agen AI** — jalankan sendiri.
Di mesin tanpa peramban, pakai `railway login --browserless`, lalu buka kode pairing yang
dicetaknya di perangkat lain.

```bash
railway whoami       # memastikan sudah masuk sebagai akun yang benar
railway logout
```

---

## 13.2 Menautkan Folder ke Project

Sekali saja per folder. Setelah tertaut, seluruh perintah lain tahu project, environment, dan
service mana yang dimaksud tanpa perlu diberi tahu lagi.

```bash
railway link
```

Bentuk interaktifnya menanyakan workspace → project → environment → service. Untuk skrip atau
agen AI, sebutkan ketiganya sekaligus supaya tidak ada prompt:

```bash
railway link --project spirited-sparkle --environment production --service CheryArta-Service
```

Memeriksa hasilnya:

```bash
railway status
railway list          # seluruh project di akun Anda
railway unlink        # melepas tautan folder ini
```

`railway status` mencetak Project ID, Environment ID, Service ID, status (`● Online`), repo
GitHub yang tertaut, dan URL publiknya — berguna saat menempelkan konteks ke laporan bug.

---

## 13.3 Melihat Log — perintah yang paling sering dipakai

Ini alasan terkuat memakai CLI. Membaca log dari terminal jauh lebih cepat daripada membuka
panel, dan hasilnya bisa disalin utuh alih-alih di-screenshot.

```bash
railway logs -d --lines 50     # log runtime (aplikasi berjalan)
railway logs -b --lines 50     # log build (composer install, npm run build)
railway logs --http --lines 50 # log permintaan HTTP
```

> **`--lines` itu penting.** Tanpa salah satu dari `--lines`, `--since`, atau `--until`, perintah
> ini **streaming** dan menggantung sampai Anda menekan Ctrl+C. Untuk sekadar melihat apa yang
> baru terjadi, selalu sertakan `--lines`.

Membedakan `-b` dan `-d` menghemat banyak waktu:

| Gejala | Log yang dibaca |
|--------|-----------------|
| Deploy merah, tetapi kontainer tidak pernah menyala | `-b` — kegagalannya saat build |
| Kontainer menyala lalu mati | `-d` — biasanya migration atau koneksi database |
| Situs hidup tetapi salah perilakunya | `-d`, dan pastikan `LOG_CHANNEL=stderr` |

Contoh nyata dari proyek ini: baris `WARN` di `railway logs -d` yang menyingkap bahwa
`PHP_CLI_SERVER_WORKERS` diabaikan karena start command belum memuat `--no-reload` — kegagalan
yang tidak pernah muncul sebagai deploy merah. Rinciannya di
[12 §12.3.2](12-panduan-instalasi-deploy.md#1232-variables-service-web).

---

## 13.4 Variabel Lingkungan

```bash
railway variables                    # tabel, nilai panjang terpotong
railway variables --kv               # KEY=VALUE, NILAI MENTAH
railway variables --json             # untuk diolah skrip
railway variable set KEY=value
railway variable delete KEY
```

> **`--kv` dan `--json` mencetak rahasia apa adanya** — `DB_PASSWORD`, `APP_KEY`,
> `CLOUDINARY_URL`. Jangan menyalurkannya ke chat, tiket, atau berkas yang ikut ter-commit.
> Bila hanya ingin memastikan sebuah variabel **sudah terisi** tanpa melihat isinya, sensor
> nilainya lebih dulu:
>
> ```powershell
> $rahasia = 'PASSWORD|APP_KEY|SECRET|TOKEN|CLOUDINARY_URL'
> railway variables --kv | Where-Object { $_ -match '=' } | Sort-Object | ForEach-Object {
>     $b = $_ -split '=', 2
>     if ($b[0] -match $rahasia) { "{0,-24} <terisi, {1} karakter>" -f $b[0], $b[1].Length }
>     else { "{0,-24} {1}" -f $b[0], $b[1] }
> }
> ```
>
> Panjang karakter saja sering cukup untuk memastikan sesuatu benar. `CLOUDINARY_URL` yang
> kehilangan `//` misalnya, hanya berbeda dua karakter dari yang benar.

`railway variable set` **memicu deploy ulang**. Bila ingin mengubah beberapa variabel sekaligus,
tambahkan `--skip-deploys` pada semuanya kecuali yang terakhir.

---

## 13.5 Database

```bash
railway connect MySQL
```

Membuka shell `mysql` yang tersambung ke service database, tanpa perlu DBeaver dan tanpa
menyalin kredensial ke mana pun. Cocok untuk pemeriksaan cepat:

```sql
SELECT COUNT(*) FROM bookings;
SHOW TABLES;
```

Untuk penelusuran data yang lebih panjang, DBeaver tetap lebih nyaman —
lihat [12 §12.4](12-panduan-instalasi-deploy.md#124-menyiapkan-dbeaver).

**Perintah `artisan` yang menyentuh database tetap dijalankan biasa**, memakai `.env` lokal yang
menunjuk TCP proxy:

```bash
php artisan migrate:status      # BUKAN: railway run php artisan migrate:status
```

Alasannya ada di §13.9.

---

## 13.6 Masuk ke Dalam Kontainer

```bash
railway ssh                              # shell interaktif
railway ssh php artisan about            # satu perintah lalu keluar
railway ssh php artisan migrate --force
```

Inilah jawaban atas pertanyaan "apakah Railway bisa SSH". Bisa — tetapi ke **kontainer
aplikasi**, bukan sebagai terowongan menuju database. Untuk menyambungkan DBeaver, yang dipakai
adalah TCP proxy, dan tab SSH di DBeaver harus tetap mati.

Perintah yang dijalankan lewat `railway ssh` berjalan **di dalam Railway**, sehingga
`mysql.railway.internal` bisa diresolusi. Ini bedanya dengan `railway run`.

> Filesystem Railway bersifat ephemeral. Apa pun yang Anda tulis lewat `railway ssh` hilang saat
> deploy berikutnya — jangan memakainya untuk menyimpan berkas atau menambal kode.

---

## 13.7 Deploy, Rollback, dan Restart

```bash
railway deployment list       # riwayat deploy + ID + status
railway redeploy              # bangun & jalankan ulang deployment terakhir
railway restart               # jalankan ulang TANPA membangun ulang
railway down                  # hapus deployment terakhir
railway up                    # unggah folder ini sebagai deployment baru
```

Bedanya `restart` dan `redeploy`: `restart` hanya menyalakan ulang kontainer dengan image yang
sama — cepat, dipakai saat proses menggantung. `redeploy` membangun ulang dari awal — dipakai
setelah mengubah variabel yang dibaca saat build, misalnya `VITE_APP_NAME`.

> **`railway up` melewati git sepenuhnya.** Ia mengunggah isi folder kerja Anda apa adanya,
> termasuk perubahan yang belum di-commit. Akibatnya kode yang hidup di Railway tidak lagi sama
> dengan isi repo, dan tidak ada catatan tentang apa yang sebenarnya berjalan.
>
> **Untuk proyek ini, deploy selalu lewat `git push` ke `main`.** `railway up` hanya untuk
> mencoba sesuatu yang memang tidak layak masuk repo, dan sesudahnya wajib `railway redeploy`
> agar Railway kembali sinkron dengan GitHub.

---

## 13.8 Perintah Lain yang Sesekali Berguna

| Perintah | Guna |
|----------|------|
| `railway open` | Membuka dashboard project di peramban |
| `railway docs` | Membuka dokumentasi Railway |
| `railway metrics` | Pemakaian CPU, memori, dan trafik HTTP service |
| `railway usage` | Pemakaian workspace terhadap kuota — relevan karena akun ini masih trial |
| `railway domain` | Menambah atau melihat domain milik service |
| `railway tcp-proxy` | Mengelola TCP proxy, yang dipakai DBeaver dan `.env` lokal |
| `railway service` | Berpindah service tertaut tanpa `railway link` ulang |
| `railway environment` | Membuat atau berpindah environment (relevan saat F2.5.1 memisahkan produksi) |
| `railway add` | Menambah service baru ke project |
| `railway upgrade --yes` | Memperbarui CLI |
| `railway setup agent -y` | Memasang skill + MCP server Railway untuk agen AI |

---

## 13.9 Yang Tidak Berlaku di Proyek Ini

Tiga hal yang tampak menggoda tetapi akan menyesatkan bila dipakai.

### `railway run` tidak bisa untuk perintah database

`railway run` menjalankan perintah **di komputer Anda** dengan variabel deployment disuntikkan.
Salah satu variabel itu adalah `DB_HOST=mysql.railway.internal` — domain jaringan privat Railway
yang hanya bisa diresolusi dari dalam Railway. Dari laptop, hasilnya:

```
SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for
mysql.railway.internal failed: No such host is known.
```

| Yang ingin dikerjakan | Perintah yang benar |
|-----------------------|---------------------|
| `artisan` yang menyentuh database, dari laptop | `php artisan …` biasa (`.env` lokal → TCP proxy) |
| `artisan` yang menyentuh database, di server | `railway ssh php artisan …` |
| Perintah yang tidak menyentuh database | `railway run …` boleh |

### Sebagian pekerjaan tetap harus lewat panel

- Mengaktifkan **TCP Proxy** pertama kali (Settings → Networking)
- **Proteksi branch `main`** — itu urusan GitHub, bukan Railway
- Membuat **service database** baru beserta kredensialnya

### CLI tidak menggantikan pemeriksaan yang sudah ada

`railway logs` memberi tahu apa yang terjadi **setelah** deploy. Ia tidak menggantikan gerbang
kualitas yang berjalan sebelum commit (`php artisan test`, Pint, PHPStan, ESLint, `tsc`,
`npm run build`). Deploy yang hijau bukan bukti kodenya benar.

---

## 13.10 Ringkasan Sehari-hari

Lima perintah yang menutupi hampir seluruh kebutuhan:

```bash
railway status                 # di mana saya sekarang
railway logs -d --lines 50     # apa yang baru terjadi
railway logs -b --lines 50     # kenapa build-nya gagal
railway connect MySQL          # intip isi database
railway ssh php artisan about  # jalankan sesuatu di server
```
