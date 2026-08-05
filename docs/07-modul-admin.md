# 07 — Modul Admin Internal

Legenda akses: **SA** = Super Admin · **ADV** = Service Advisor

## Pembagian Big Fase

Dokumen ini adalah spesifikasi **lengkap** tiap modul. Urutan pengerjaannya diatur
[roadmap](10-roadmap-implementasi.md#status-modul-admin):

| Big Fase 1 | Big Fase 2 |
|------------|------------|
| **A2** Booking · **A3** Jadwal · **A4** Katalog · **A5** Paket Layanan · **A6** Customer & Kendaraan | **A1** Dashboard · **A7** WhatsApp penuh · **A8** Invoice · **A9** Laporan · **A10** Konten · **A11** Pengguna Internal · **A12** Activity Log |

Tiga modul dikerjakan sebagian di Big Fase 1 — bagian sisanya menyusul:

- **A7** → hanya tombol "Chat via WhatsApp" (`wa.me` + teks dari `config/company.php`).
  Tabel template, penyunting template, dan log pengiriman menyusul. **Ditutup di Tahap 10:**
  teksnya kini di `whatsapp_templates`, dan `config('company.wa_messages')` sudah dihapus.
- **A10** → hanya tabel `facilities`, `faqs`, `testimonials`, `contact_messages` + seeder,
  supaya landing page punya sumber data. Layar CRUD-nya menyusul.
- **A13/A14** → menu untuk A2–A6 saja. Tetapi **Policy dan pembatasan SA-saja wajib lengkap
  sejak Big Fase 1** — otorisasi tidak pernah boleh ditunda (temuan S3).

**A11 tetap di Big Fase 2** (F2.2.3, keputusan
[R9](10-roadmap-implementasi.md#keputusan-yang-membentuk-roadmap-ini)). Sepanjang Big Fase 1
akun staf hanya lahir dari `UserSeeder`; menambah advisor sungguhan berarti membuka `tinker`
atau DBeaver. Itu diterima selama sistemnya dipakai developer saja — konsekuensinya adalah
matriks hak akses teruji terhadap akun contoh, bukan terhadap orang sungguhan.

## A1 — Dashboard  `/admin`  (SA, ADV) — ✅ F2.1

> Selama Big Fase 1, `/admin` **mengalihkan ke `/admin/bookings`** (**R8**). Redirect itu
> dicabut di F2.1.3; `/admin` kini merender dashboard.

**Tujuan:** menjawab "apa yang harus dikerjakan hari ini" dalam satu layar.

| Elemen | Isi | Sumber data |
|--------|-----|-------------|
| Kartu KPI | Booking hari ini · Perlu konfirmasi · Sedang dikerjakan · Selesai bulan ini · **Belum dikabari** | agregat `bookings` |
| Grafik tren | Jumlah booking 30 hari terakhir (garis) | `bookings` dikelompokkan per tanggal |
| Okupansi slot hari ini | 11 baris slot dengan bar terisi `n/2` | `SlotService::occupancy(today)` |
| Tabel booking hari ini | Jam, kode, customer, kendaraan, paket, status, aksi cepat | `bookings` hari ini, urut jam |
| Peringatan | Booking `confirmed` yang tanggalnya sudah lewat (calon `no_show`) | `bookings` |

Aksi cepat pada tabel: **Konfirmasi** · **Mulai** · **Selesai** — tanpa berpindah halaman
(Inertia partial reload).

### Kartu "Belum dikabari" (F2.3.5)

Menghitung **booking**, bukan baris draft — baris `whatsapp_messages` hanya lahir ketika advisor
menekan tombolnya, sehingga menghitung draft justru melewatkan kelalaian yang ditakutkan: advisor
yang tidak membuka WhatsApp sama sekali.

Sebuah booking terhitung bila ketiganya benar:

1. statusnya termasuk yang ditagih (`confirmed`, `in_progress`, `completed`, `cancelled`,
   `no_show` — `booking_created` dan `booking_reminder` bersifat sukarela) **dan** templatenya
   sedang aktif;
2. belum ada pesan berstatus `sent` maupun `skipped` untuk kunci template status itu;
3. statusnya berubah dalam `config('whatsapp.pending_window_days')` hari terakhir.

Syarat ketiga wajib: tanpanya seluruh booking sejak Tahap 5 ikut terhitung — tabelnya baru lahir
di Tahap 10 sehingga tak satu pun punya baris terkirim — dan kartunya mustahil dikosongkan.

Definisinya hidup di satu tempat, `Booking::scopeAwaitingWhatsApp()`, dan dipakai bersama saringan
`wa=belum` di [A2](#a2--manajemen-booking-adminbookings--sa-adv). Kartunya bisa diklik menuju
daftar itu.

## A2 — Manajemen Booking  `/admin/bookings`  (SA, ADV)

**Daftar**

- Kolom: Kode · Tanggal · Jam · Customer · Kendaraan · Paket · Status · Advisor · Aksi.
- Filter (dijalankan **di server**, mempertahankan fitur lama): pencarian nama/plat/kode,
  status, rentang tanggal, paket layanan, advisor penanggung jawab, dan **`wa=belum`** — hanya
  booking yang pelanggannya belum dikabari (F2.3.5, tujuan kartu dashboard).
- Urutan bawaan: tanggal & jam menaik untuk booking mendatang; terbaru dulu untuk riwayat.
- Paginasi 25 baris; filter tersimpan di query string agar bisa dibagikan/di-bookmark.
- Tombol: **Tambah Booking (Walk-in)** · **Export Excel** · **Salin untuk Spreadsheet**.
- Di layar `< md` tabel berubah menjadi kartu (mempertahankan pola sistem lama).

**Detail** — lihat wireframe di [06-desain-ui-ux.md §6.5](06-desain-ui-ux.md#65-wireframe-halaman-kunci).
Berisi: data customer & kendaraan, jadwal, keluhan, odometer, panel ubah status, panel WhatsApp,
riwayat status, dan tautan ke invoice.

**Ubah status** — hanya transisi yang sah ([05 §5.3](05-alur-bisnis.md#53-state-machine-status-booking));
opsi yang tidak sah tidak ditampilkan **dan** ditolak server. Catatan opsional yang diisi advisor
tersimpan di riwayat dan terlihat customer.

**Booking walk-in** — advisor mengisi atas nama pelanggan yang datang langsung:

1. Cari customer via nomor WA/email/nama → bila tidak ada, buat akun baru (password acak, ditandai `must_reset_password`).
2. Pilih/tambah kendaraan.
3. Pilih paket, tanggal, jam — **aturan H-1 dilewati** untuk walk-in (booking hari ini diizinkan), kuota slot tetap berlaku.
4. `source = walk_in`, status langsung `confirmed`.

**Validasi:** sama dengan [05 §5.8](05-alur-bisnis.md#58-aturan-validasi-ditegakkan-di-server).

## A3 — Jadwal / Okupansi  `/admin/jadwal`  (SA, ADV)

Tampilan harian: 11 baris slot × kapasitas 2, tiap sel berisi kartu booking (kode, customer,
kendaraan, status). Navigasi ← hari → dan pemilih tanggal. Slot penuh diberi penanda jelas.
Berguna saat menerima booking lewat telepon.

Sejak F2.3, tiap kartu booking juga membawa tombol **Kirim Pengingat** — tetapi hanya untuk
booking berstatus **Dikonfirmasi** yang jadwalnya masih di depan. Layar inilah satu-satunya yang
sudah menjawab "siapa saja yang servis besok", jadi di sinilah pengingat H-1 paling murah ditekan;
mengirimnya dari detail booking saja berarti membuka delapan halaman untuk delapan pengingat.

`pending` sengaja tidak ditawari: mengirim "sampai jumpa besok" untuk booking yang belum
dikonfirmasi adalah janji yang belum tentu ditepati bengkel.

Tampilan mingguan (opsional, Fase 5): matriks 7 hari × slot berisi angka okupansi.

## A4 — Katalog Mobil  `/admin/katalog`  (SA saja)

| Layar | Isi |
|-------|-----|
| Daftar | Thumbnail, nama, kategori, bahan bakar, harga mulai, status aktif, urutan |
| Form model | Nama · slug (otomatis, bisa disunting) · kategori · bahan bakar · `series_code` · harga mulai · deskripsi singkat · deskripsi · spesifikasi (pasangan kunci–nilai dinamis) · brosur PDF · aktif · urutan |
| Varian | Tabel dalam halaman: nama varian, harga, spesifikasi, aktif |
| Galeri | Unggah banyak gambar, seret untuk mengurutkan, tandai gambar utama, **teks alt wajib** |

Aturan: model yang sudah dirujuk `vehicles` tidak bisa dihapus — hanya dinonaktifkan.
`series_code` menentukan paket Free Maintenance mana yang relevan untuk model tersebut.

## A5 — Paket Layanan  `/admin/paket-layanan`  (SA saja)

Menggantikan dropdown yang dulu tertanam di HTML.

| Field | Aturan |
|-------|--------|
| `code` | unik, huruf/angka/garis bawah; kode lama dipertahankan agar data lama tetap terpetakan |
| `name` | wajib, maks 200 karakter |
| `category` | first_maintenance_ice / first_maintenance_ev / free_maintenance / other |
| `applicable_series` | pilihan ganda dari `car_models.series_code`; kosong = semua model |
| `estimated_duration_minutes` | 15–480 |
| `price` | ≥ 0; diabaikan bila `is_free` |
| `is_free` | tampil sebagai "Gratis" |
| `is_active`, `sort_order` | |

Efek langsung: pilihan paket di form booking publik dan estimasi biaya invoice.

## A6 — Customer & Kendaraan  (SA, ADV)

**`/admin/customers`** — daftar: nama, email, WA, jumlah kendaraan, jumlah booking, terakhir servis.
Detail: profil, daftar kendaraan, riwayat booking lengkap, **total nilai invoice** (dilengkapi di
F2.4.6; hanya menjumlahkan invoice `issued` + `paid` — `draft` belum pernah ditagihkan dan `void`
sudah dicabut, memasukkan keduanya membuat angkanya berbohong ke atas).
Aksi: nonaktifkan akun (SA), reset password (SA), buat booking untuk customer ini (ADV).
**Tidak ada** aksi hapus permanen.

**`/admin/vehicles`** — daftar seluruh unit terdaftar: plat, model, tahun, pemilik, odometer
terakhir, jumlah servis. Berguna untuk pertanyaan "unit ini terakhir servis kapan?".

## A7 — Notifikasi WhatsApp  (SA, ADV) — ✅ F2.3

Panel tertanam di halaman detail booking, ditambah `/admin/template-wa` (SA) untuk menyunting
teks template. Rincian mekanisme: [08-notifikasi-whatsapp.md](08-notifikasi-whatsapp.md).

| Layar | Isi | Hak akses |
|-------|-----|-----------|
| Panel di detail booking | Pratinjau pesan (tidak bisa disunting), nomor tujuan, **Buka WhatsApp**, **Salin Pesan**, penanda "Belum dikabari" | SA + ADV |
| Riwayat pesan di detail booking | Waktu, template, pelaku, status, kutipan pesan · aksi **Tandai Terkirim** / **Lewati** | SA + ADV |
| Tombol pengingat di [A3 Jadwal](#a3--jadwal--okupansi-adminjadwal--sa-adv) | Hanya untuk booking **Dikonfirmasi** yang jadwalnya masih di depan | SA + ADV |
| `/admin/template-wa` | Daftar 7 template + form sunting (`name`, `body`, `is_active`) | **SA saja** |

**Penyunting template bukan CRUD.** Tidak ada tambah dan tidak ada hapus: himpunan kuncinya milik
`App\Enums\WhatsAppTemplateKey` karena setiap kunci punya pemicunya sendiri di dalam kode.
Template buatan admin tidak akan pernah terpanggil — baris mati yang tampak seperti fitur — dan
template yang dihapus mematahkan jalur kirim di tengah advisor mengubah status.

Formnya menampilkan daftar placeholder yang bisa diklik untuk disisipkan, penghitung karakter, dan
**pratinjau terender memakai data contoh** (karangan, bukan pelanggan sungguhan). Placeholder di
luar daftar ditolak dengan pesan yang menyebut namanya.

`is_active = false` berarti **pesan untuk pemicu itu tidak ditawarkan sama sekali**: panel
menjelaskan keadaannya dan tidak merender tombol, dan bookingnya tidak ikut dihitung sebagai
"belum dikabari". Tidak ada jalur cadangan diam-diam ke teks bawaan — cadangan semacam itu membuat
tombol "nonaktifkan" tidak melakukan apa-apa.

> **Jejaknya baru lahir saat tombol ditekan**, bukan saat status berubah. Alasannya beserta
> konsekuensinya ada di [08 §8.2](08-notifikasi-whatsapp.md#82-cara-kerja).
>
> **Tidak ada layar log global.** Riwayat dibaca dari detail booking dan dari daftar booking yang
> disaring `wa=belum` ([A2](#a2--manajemen-booking-adminbookings--sa-adv)) — lihat
> [08 §8.8](08-notifikasi-whatsapp.md#88-aturan-privasi).

## A8 — Invoice  `/admin/invoices`  (SA, ADV) — ✅ Tahap 11

| Layar | Isi |
|-------|-----|
| Daftar | Nomor, tanggal terbit, customer, kode booking, total, status; saringan status + pencarian (nomor/kode booking/nama), paginasi 25 |
| Penyunting | Item jasa & part (deskripsi, qty, harga satuan, subtotal otomatis), diskon, pajak, catatan |
| Aksi | Simpan draft · Terbitkan · Tandai lunas (+ metode bayar) · Batalkan (void, wajib alasan) · Unduh PDF |

**Tidak ada tombol "Buat Invoice" di daftar.** Invoice selalu lahir dari sebuah booking yang sudah
`completed` — otomatis saat statusnya berubah, atau lewat tombol **Buat Invoice** di panel invoice
pada detail booking. Form invoice kosong akan menghasilkan tagihan tanpa pekerjaan yang bisa
ditelusuri.

Aturan: invoice `issued` tidak bisa disunting; hanya SA yang boleh mem-`void` (termasuk dari status
`paid`), dan alasannya wajib. Paket gratis tetap dibuatkan invoice Rp 0. Diskon tidak boleh
melebihi subtotal, dan menerbitkan menuntut minimal satu item. Seluruh perhitungan dilakukan di
server ([05 §5.6](05-alur-bisnis.md#56-alur-estimasi-biaya--invoice)).

PDF disusun sebagai Blade view (`resources/views/pdf/invoice.blade.php`) memakai
`barryvdh/laravel-dompdf`, **tanpa satu pun gambar raster** — kop suratnya teks dari
`config/company.php`. Ekstensi PHP `gd` tidak terpasang di lingkungan pengembangan dan belum
terbukti ada di runtime Railway; karena `gd` hanya *suggest* pada dompdf, ketiadaannya tidak
menjatuhkan `composer install` melainkan baru muncul saat render pertama di produksi.

## A9 — Laporan & Export  `/admin/laporan`  (SA, ADV melihat; export keduanya) — ✅ F2.1

Satu rute dengan tab, berbagi satu pemilih periode (7 hari · 30 hari · bulan ini · bulan lalu ·
rentang kustom; bawaan 30 hari, batas 1 tahun dari `config('booking.reports')`).

| Laporan | Isi | Kapan |
|---------|-----|-------|
| Rekap booking | Per periode: total, per status, per paket, per model mobil | ✅ F2.1 |
| Okupansi | Rata-rata pemakaian slot per hari & per jam — menunjukkan jam sibuk | ✅ F2.1 |
| Customer baru | Jumlah registrasi per periode | ✅ F2.1 |
| Pendapatan | **Tiga angka** per periode: diterbitkan · lunas · belum dibayar (SA saja) | ✅ F2.4.7 |

> **Pendapatan dikelompokkan menurut `issued_at`**, bukan `paid_at` dan bukan tanggal booking.
> Memakai tanggal bayar membuat invoice yang belum dibayar tidak muncul di mana pun, sehingga
> piutang menjadi tak terlihat — justru angka yang paling ingin diketahui pemilik bengkel.
>
> Tiga angka, bukan satu: "Rp 12 juta" yang ternyata separuhnya belum masuk kas adalah laporan
> yang salah dibaca. `belum dibayar` dihitung sebagai **selisih** diterbitkan − lunas, sehingga
> ketiganya mustahil berselisih satu sama lain. Invoice `draft` dan `void` dikecualikan.
> Pecahannya ditampilkan per paket layanan dan per metode pembayaran.
>
> **Tab-nya tidak dirender untuk advisor**, dan prop `revenue` bernilai `null` baginya — datanya
> tidak dikirim sama sekali, bukan dikirim lalu disembunyikan di React (temuan S3). Advisor yang
> mengetik `?tab=pendapatan` dikembalikan ke tab pertama, bukan dijawab 403: ia memang berhak
> membuka halaman laporan.
>
> **Catatan jujur:** advisor tetap melihat total tiap invoice satu per satu di A8, karena ia yang
> menerbitkannya. Pembatasan di sini soal kemudahan agregat, bukan kerahasiaan angka.

Export mempertahankan kedua fitur sistem lama:
- **Unduh CSV** — dibuat di server, dialirkan baris demi baris, mengikuti filter yang aktif.
- **Salin untuk Spreadsheet** — menyalin TSV ke clipboard agar bisa ditempel ke Google Sheets,
  sama seperti perilaku lama.

> **Kenapa CSV, bukan `.xlsx`.** Rencana semula memakai `maatwebsite/excel`, yang menuntut
> PhpSpreadsheet beserta ekstensi PHP `zip`. Ketersediaan ekstensi itu di runtime Railway
> belum terbukti, dan `composer install` yang gagal menjatuhkan **seluruh deploy**, bukan
> hanya fitur export. CSV ber-BOM UTF-8 dibuka Excel secara langsung — termasuk nama beraksen —
> tanpa satu pun dependensi baru. Bila `.xlsx` sungguhan kelak dibutuhkan, yang berubah hanya
> `BookingExportController`.

Kolom export sama dengan sistem lama: Date, Time, Name, Model, Plat Nomor, Service, Keluhan,
Phone, Status — ditambah Kode Booking dan Advisor.

## A10 — Konten Landing Page  (SA saja) — ✅ Tahap 9

> Tabelnya (`facilities`, `faqs`, `testimonials`, `contact_messages`) beserta seeder idempoten
> lahir lebih dulu di Tahap 3 (**R4**), supaya landing page punya sumber data nyata sejak awal.
> Keempat layar di bawah menyusul di Tahap 9 — **tanpa migrasi data**, karena tabelnya sudah
> terisi.
>
> Sampai layar itu ada, pesan dari form kontak masuk ke database tetapi tidak bisa dibaca dari
> panel. Halaman kontak karena itu menonjolkan tombol WhatsApp sebagai jalur utama.

| Modul | Isi |
|-------|-----|
| `/admin/fasilitas` | 8 fasilitas: judul, deskripsi, gambar, urutan, aktif |
| `/admin/faq` | Pertanyaan, jawaban, kategori, urutan, aktif |
| `/admin/testimoni` | Nama, model mobil, rating 1–5, isi, terbitkan/sembunyikan |
| `/admin/pesan-masuk` | Pesan dari form kontak: tandai dibaca, balas via WA (klik-to-chat), hapus spam |

Urutan fasilitas, FAQ, dan testimoni diatur dengan **menyeret**, memakai ulang pola galeri
katalog. Tombol naik/turun disediakan berdampingan, bukan sebagai pelengkap: seret-lepas HTML5
tidak bisa dijalankan dengan papan ketik sama sekali.

> **Testimoni diketik admin, tanpa alur moderasi.** Tidak ada form publik yang mengirimnya, jadi
> `is_published` hanyalah saklar tampil atau sembunyi di landing page — bukan antrean yang
> menunggu persetujuan. Bila kelak pelanggan boleh mengirim sendiri, alur moderasinya
> ditambahkan saat itu di atas kolom yang sudah ada.

> **"Hapus spam" menghapus permanen.** `contact_messages` tidak memakai soft delete, dan tidak
> ada layar pemulihan. Konsekuensinya diterima secara sadar: pesan pelanggan sungguhan yang
> salah ditandai spam hilang selamanya.
>
> Dua pengaman yang menyertainya: `ConfirmDialog` menampilkan **nama pengirim beserta cuplikan
> isi pesan** sebelum tombolnya ditekan — yang akan hilang harus terlihat lebih dulu, bukan
> sesudah — dan penghapusannya tercatat di activity log, sehingga isinya hilang tetapi fakta
> siapa menghapus pesan dari siapa tetap punya jejak.

## A11 — Pengguna Internal  `/admin/users`  (SA saja) — ✅ Tahap 9

Daftar akun staf: nama, email, role, status aktif, login terakhir. Saringan role & status,
paginasi 25.
Aksi: tambah advisor, ubah role, nonaktifkan, reset password.

Kolomnya sama dengan A6, tetapi **daftarnya terpisah dan tidak boleh saling menembus**: A6
hanya menampilkan akun ber-role `customer`, A11 hanya akun staf. Satu layar untuk keduanya
akan membuat penonaktifan sesama staf lewat jalur A6 yang pengamannya lebih longgar.

| Field | Aturan |
|-------|--------|
| `name` | wajib |
| `email` | wajib, unik lintas seluruh akun |
| `phone_wa` | wajib, dinormalisasi `62…`, unik |
| `role` | `super_admin` \| `service_advisor` — **bukan** `customer` |
| `is_active` | default aktif |
| Password | **tidak diisi admin.** Server membuat password acak dan menandai `must_reset_password` — pemiliknya menetapkan sendiri saat login pertama |

Pengaman:
- Super Admin tidak bisa menurunkan role **dirinya sendiri**, dan tidak bisa menonaktifkan
  dirinya sendiri.
- Super Admin **aktif terakhir** tidak bisa diturunkan maupun dinonaktifkan — dihitung di dalam
  transaksi dengan barisnya terkunci, bukan dibaca lalu ditulis.
- Akun customer tidak dikelola di sini (ada di A6); sasaran ber-role `customer` ditolak.
- Akun tidak pernah dihapus permanen — nonaktifkan (soft delete tersedia, tetapi bukan aksi
  layar ini).

> **Seluruhnya lahir di Tahap 9** (**R9**): layar dan pengamannya di F2.2.3–F2.2.4, penegakan
> `must_reset_password` di F2.2.5, pencatatan perubahan akun ke activity log di F2.2.6. Sampai
> saat itu `/admin/users` tidak terdaftar sebagai rute, dan akun staf lahir dari `UserSeeder`
> saja.

**Password sementara ditampilkan sekali di layar**, tepat setelah aksi berhasil, disertai
peringatan bahwa ia tidak bisa dilihat lagi. Tanpa notifikasi email (keputusan final #4) tidak
ada jalur lain untuk menyampaikannya; Super Admin meneruskannya lewat WhatsApp atau lisan.

Yang menjaga agar itu tidak menjadi kebocoran:

- Nilainya lewat **flash session** — hidup untuk satu tampilan, lalu hilang. Tidak pernah
  tersimpan dalam bentuk terbaca di mana pun.
- **Tidak pernah masuk activity log.** A12 mencatat *bahwa* password direset, bukan isinya.
- Masa berlakunya dipersempit `must_reset_password`: hanya sah untuk satu kali masuk.

Alternatif yang ditolak: membiarkan Super Admin mengetik sendiri password staf. Itu berarti ia
mengetahui password orang lain secara permanen.

## A12 — Activity Log  `/admin/activity-log`  (SA saja) — ✅ Tahap 9

Menjawab "siapa mengubah apa dan kapan" — kebutuhan yang sama sekali tidak terpenuhi sistem lama.
Kolom: waktu, pelaku, aksi, objek, perubahan (sebelum → sesudah). Filter: pelaku, jenis objek,
rentang tanggal. Retensi 12 bulan.

> **Pencatatan dimulai sejak Tahap 9, tanpa pengisian mundur.** Perubahan sebelumnya tidak punya
> jejak, dan mengarangnya akan menghasilkan log audit yang isinya tebakan — lebih berbahaya
> daripada log yang jujur mulai dari satu tanggal. Layar menyebutkan tanggal mulai pencatatan
> supaya kekosongan sebelumnya tidak dibaca sebagai "tidak ada yang berubah".
>
> **Perubahan status booking tidak ada di sini** — riwayatnya milik `booking_status_histories`.
> Rinciannya beserta kolom yang dikecualikan ada di
> [04 §4.2 `activity_log`](04-skema-database.md#activity_log).
>
> **Penjadwal retensi baru aktif di Tahap 12.** Perintah `activitylog:clean` sudah didaftarkan
> di `routes/console.php` dan batas harinya ada di `config/activitylog.php`, tetapi proses cron
> yang menjalankannya belum ada di Railway. Dengan volume satu bengkel, log yang menumpuk
> beberapa bulan tidak membahayakan apa pun.

## A13 — Navigasi Panel Admin

```
Dashboard
Operasional   → Jadwal · Booking · Invoice
Data          → Customer · Kendaraan
Laporan       → Rekap · Export
Master (SA)   → Katalog Mobil · Paket Layanan
Konten (SA)   → Fasilitas · FAQ · Testimoni · Pesan Masuk
Sistem (SA)   → Pengguna · Template WA · Activity Log
```

Menu yang tidak boleh diakses **tidak ditampilkan**, dan tetap ditolak di server bila URL-nya
diketik langsung — otorisasi tidak pernah bergantung pada UI (temuan S3).

> **Yang dirender selalu hanya menu yang modulnya sudah ada** — bukan
> ditampilkan-lalu-dinonaktifkan. Sejak Tahap 11 **seluruh menu A13 sudah dirender**; Invoice
> adalah yang terakhir menyusul.
>
> "Pesan Masuk" membawa **lencana jumlah belum dibaca**. Angkanya dihitung server dan hanya
> dikirim untuk Super Admin — peran lain menerima `null`, bukan angka nol, karena jumlah pesan
> pelanggan yang menunggu bukan urusan mereka.

## A14 — Ringkasan Matriks Hak Akses

> Otorisasi tiap modul berlaku dan teruji sejak modulnya lahir — menundanya berarti mengulang
> temuan S3 sistem lama. Yang menyusul di **F2.2.7** hanyalah penyusunannya sebagai satu berkas:
> `tests/Feature/Admin/AccessMatrixTest.php`, satu uji per baris matriks, supaya baris yang
> *hilang* ikut kelihatan.
>
> Berkas itu sengaja ditulis **lebih dulu**, sebelum modul A10/A11/A12 dibangun: baris untuk
> rute yang belum ada dibiarkan **merah**, bukan dilewati. Merah di sana adalah daftar pekerjaan
> yang tersisa, dan uji matriksnya tidak bisa jatuh sebagai korban saat pekerjaan dikejar cepat.
>
> Baris template WA menyusul di F2.3.6, baris invoice di F2.4.6. **Sejak Tahap 11 seluruh baris
> matriks di bawah sudah punya ujinya.**

| Modul | Super Admin | Service Advisor |
|-------|-------------|-----------------|
| Dashboard, Jadwal | ✅ | ✅ |
| Booking: lihat, buat, ubah status | ✅ | ✅ |
| Booking: hapus (soft delete) | ✅ | ❌ |
| Customer & Kendaraan: lihat | ✅ | ✅ |
| Customer: nonaktifkan / reset password | ✅ | ❌ |
| Invoice: buat, terbitkan | ✅ | ✅ |
| Invoice: void | ✅ | ❌ |
| Laporan: booking & okupansi | ✅ | ✅ |
| Laporan: pendapatan | ✅ | ❌ |
| Export | ✅ | ✅ |
| Katalog, Paket Layanan | ✅ | ❌ |
| Konten (fasilitas, FAQ, testimoni, pesan masuk) | ✅ | ❌ |
| Pengguna internal, Template WA, Activity Log | ✅ | ❌ |
