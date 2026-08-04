# PRD: Dashboard Admin & Laporan (F2.1 — A1 + A9)

**Sumber keputusan:** [`docs/grills/grill-dashboard-laporan.md`](../grills/grill-dashboard-laporan.md) (ditutup 4 Agustus 2026)
**Sub-fase:** F2.1 — sub-fase pertama Big Fase 2 · estimasi 3 hari
**Tanggal:** 4 Agustus 2026
**Status:** ✅ **SELESAI** 4 Agustus 2026 — 475 uji Pest hijau, seluruh gerbang kualitas bersih.
Satu penyimpangan: export menghasilkan CSV, bukan `.xlsx` (lihat Pertanyaan Terbuka #1).

---

## Masalah

Staf bengkel tidak punya satu layar yang menjawab **"apa yang harus dikerjakan hari ini"**.

Hari ini `/admin` mengalihkan ke `/admin/bookings` — daftar 25 baris terpaginasi yang diurutkan
menurut saringan, bukan menurut urgensi. Untuk menjawab pertanyaan sesederhana "ada berapa
booking yang belum saya konfirmasi", advisor harus menyaring status `pending` secara manual,
lalu mengulanginya untuk `in_progress`, lalu berpindah ke `/admin/jadwal` untuk melihat slot mana
yang masih kosong. Tiga layar untuk satu pertanyaan.

Akibat kedua yang lebih berbahaya: **booking `confirmed` yang tanggalnya sudah lewat tidak
terlihat di mana pun.** Ia tidak muncul di jadwal hari ini, dan di daftar booking ia tenggelam
di antara ratusan baris lain. Booking itu seharusnya ditandai `no_show`, tetapi tidak ada yang
mengingatkan. Data statusnya membusuk diam-diam.

Sistem lama tidak punya dashboard sama sekali, dan pemiliknya juga tidak punya cara menjawab
"berapa booking bulan ini" selain menghitung baris di Firebase console. Kemampuan export yang ada
di prototipe lama — Excel dan Salin untuk Spreadsheet — hilang sejak migrasi dan belum
dikembalikan.

## Tujuan

Satu layar `/admin` yang menjawab "apa yang harus dikerjakan hari ini" tanpa berpindah halaman,
dan satu layar `/admin/laporan` yang menjawab "apa yang sudah terjadi selama periode ini" dengan
hasil yang bisa dibawa keluar ke Excel atau spreadsheet.

## Latar

`docs/07-modul-admin.md §A1` mendefinisikan A1 Dashboard; `§A9` mendefinisikan A9 Laporan &
Export. Keduanya ditunda ke Big Fase 2 oleh keputusan **R8** — `/admin` dialihkan ke
`/admin/bookings` dan `pages/admin/dashboard.tsx` dari Fase 0 dihapus, bukan diisi setengah.

Big Fase 1 telah ditutup di 21 hari setelah F1.7 dan F1.8 dicabut, sehingga F2.1 adalah
**pekerjaan berikutnya yang dikerjakan**. Big Fase 2 diurutkan dengan panel admin didahulukan:
F2.1 Dashboard & Laporan → F2.2 Konten, Pengguna & Audit → F2.3 WhatsApp → F2.4 Invoice →
F2.5 Pengerasan & Go-Live.

Urutan itu menimbulkan satu konsekuensi yang menentukan lingkup PRD ini: **laporan Pendapatan
tidak bisa dibangun sekarang.** `docs/07 §A9` mendefinisikannya sebagai total invoice
`issued`/`paid`, sedangkan tabel `invoices` baru lahir di F2.4. Keputusan grill #1 memisahkannya
keluar dari F2.1.

Fondasi yang sudah tersedia dan **dipakai ulang, bukan dibangun ulang**:

| Sudah ada | Dipakai untuk |
|-----------|---------------|
| `SlotService::availability()` | Okupansi slot hari ini |
| `BookingPresenter` | Baris tabel booking hari ini |
| `BookingFilters` | Saringan laporan & export |
| `BookingStatusController` + `UpdateBookingStatusRequest` | Aksi cepat di dashboard |
| `BookingPolicy` | Otorisasi dashboard & laporan |
| Index `bookings(status, booking_date)` dan `(booking_date, booking_time)` | Seluruh agregasi |

## Selesai Bila

DoD 12 butir di `docs/10-roadmap-implementasi.md` berlaku otomatis. Berikut kriteria khusus
fitur ini — **seluruhnya terpenuhi per 4 Agustus 2026**, dengan satu penyimpangan yang dicatat
di tempatnya:

- [x] Membuka `/admin` menampilkan dashboard, bukan mengalihkan ke `/admin/bookings`
- [x] Empat kartu KPI menampilkan angka yang cocok bila dihitung manual dari `/admin/bookings`
- [x] Grafik tren menampilkan 30 hari terakhir berdasarkan `booking_date`; hari tanpa booking
      tampil sebagai nol, bukan lubang di garis
- [x] Okupansi hari ini menampilkan seluruh slot dengan bar `n/2`, dan angkanya sama persis
      dengan yang tampil di `/admin/jadwal` untuk tanggal yang sama
- [x] Tabel booking hari ini menampilkan seluruh booking kecuali `cancelled`, urut jam
- [x] Menekan Konfirmasi / Mulai / Selesai mengubah status **tanpa berpindah halaman**, dan
      riwayat statusnya tercatat di `booking_status_histories`
- [x] Aksi cepat yang tidak sah untuk status baris itu **tidak dirender**, dan tetap ditolak
      server bila `PUT`-nya dipaksakan
- [x] Booking `confirmed` bertanggal kemarin muncul di panel peringatan dengan aksi
      "Tandai Tidak Hadir"
- [x] `/admin/laporan` menampilkan tiga tab: Rekap Booking, Okupansi, Customer Baru
- [x] Mengganti periode memperbarui ketiga tab, dan periode aktif ikut di query string
      sehingga tautannya bisa dibagikan
- [x] Tab Pendapatan **tidak dirender sama sekali** (bukan tampil lalu dinonaktifkan)
- [x] ~~Tombol "Export Excel" mengunduh `.xlsx`~~ → **tombol "Unduh CSV" mengunduh `.csv`**
      ber-BOM UTF-8 yang isinya mengikuti saringan aktif. Jalur cadangan yang sudah disepakati
      di Pertanyaan Terbuka #1; `maatwebsite/excel` tidak dipasang
- [x] Tombol "Salin untuk Spreadsheet" menyalin TSV, dan menampilkan toast bila peramban menolak
- [x] Kedua tombol export juga tersedia di `/admin/bookings` dan mengikuti saringan di sana
- [x] Menu "Dashboard" dan "Laporan" muncul di sidebar untuk kedua role staf
- [x] Advisor dan Super Admin melihat dashboard yang identik
- [x] Customer dan tamu ditolak di `/admin`, `/admin/laporan`, dan seluruh rute export
- [x] Dashboard benar pada 360px — kartu KPI menumpuk, tabel jadi kartu, grafik tetap terbaca

## Lingkup

**A1 Dashboard** di `/admin`:

- Empat kartu KPI: Booking hari ini · Perlu konfirmasi · Sedang dikerjakan · Selesai bulan ini
- Grafik tren jumlah booking 30 hari terakhir (Recharts, garis)
- Okupansi slot hari ini — versi ringkas baca-saja, dengan tautan ke `/admin/jadwal`
- Tabel booking hari ini beserta aksi cepat Konfirmasi · Mulai · Selesai
- Panel peringatan booking `confirmed` yang tanggalnya sudah lewat, dengan aksi
  "Tandai Tidak Hadir"
- Pencabutan redirect `/admin` → `/admin/bookings` (roadmap 2.1.3)

**A9 Laporan** di `/admin/laporan`, satu rute dengan tab:

- Rekap booking per periode: total, per status, per paket layanan, per model mobil
- Okupansi: rata-rata pemakaian slot per hari dan per jam
- Customer baru: jumlah registrasi per periode
- Pemilih periode bersama: 7 hari · 30 hari · bulan ini · bulan lalu · rentang kustom

**Export**, dipakai dua layar:

- Export Excel `.xlsx` dibuat di server, mengikuti saringan aktif
- Salin untuk Spreadsheet (TSV ke clipboard)
- Terpasang di `/admin/laporan` **dan** `/admin/bookings`

**Navigasi:** item "Dashboard" dan "Laporan" ditambahkan ke `AdminLayout`.

## Bukan Lingkup

1. **Laporan Pendapatan.** Butuh tabel `invoices` yang baru lahir di F2.4. Tab-nya tidak
   dirender sampai saat itu. Dipindahkan menjadi butir roadmap **2.4.7** (keputusan grill #1).
2. **Kartu "draft WhatsApp belum dikirim" di dashboard.** Tabel `whatsapp_messages` baru lahir
   di F2.3.1; kartunya sudah dijadwalkan di **F2.3.5**.
3. **Layar A3 Jadwal.** Sudah ada dan tidak disentuh. Dashboard hanya menampilkan ringkasan
   baca-saja dan menautkannya.
4. **Rute atau state machine status baru.** Aksi cepat memakai `admin.bookings.status` yang
   sudah ada apa adanya.
5. **Cache atau tabel agregat.** Tidak ada denormalisasi, tidak ada tabel ringkasan, tidak ada
   job terjadwal. Agregat dihitung langsung dari `bookings` setiap kunjungan.
6. **Export PDF.** `docs/07 §A9` hanya menetapkan Excel dan TSV. PDF baru muncul di F2.4 untuk
   invoice, bukan untuk laporan.
7. **Dashboard untuk customer.** `/dashboard` milik customer tidak disentuh PRD ini.

## Pengguna & Kebutuhannya

Yang menyentuh fitur ini hanya dua role staf. Customer dan tamu tidak punya akses sama sekali.

- Sebagai **service advisor**, saya ingin melihat satu layar berisi booking hari ini beserta
  jumlah yang perlu dikonfirmasi, supaya saya tahu apa yang harus dikerjakan tanpa menyaring
  daftar booking satu per satu.
- Sebagai **service advisor**, saya ingin mengubah status booking langsung dari dashboard, supaya
  saya tidak perlu membuka detail booking hanya untuk menekan satu tombol.
- Sebagai **service advisor**, saya ingin diingatkan tentang booking `confirmed` yang tanggalnya
  sudah lewat, supaya data statusnya tidak membusuk sebagai `confirmed` selamanya.
- Sebagai **service advisor**, saya ingin melihat okupansi slot hari ini sekilas, supaya saya bisa
  menjawab telepon "masih ada slot jam berapa?" tanpa berpindah layar.
- Sebagai **Super Admin**, saya ingin melihat rekap booking per periode dipecah menurut status,
  paket layanan, dan model mobil, supaya saya tahu layanan mana yang paling laku.
- Sebagai **Super Admin**, saya ingin melihat jam mana yang paling sibuk sepanjang periode, supaya
  saya bisa menilai apakah kuota 2 per slot masih sesuai kapasitas bengkel.
- Sebagai **Super Admin**, saya ingin tahu berapa customer baru mendaftar per periode, supaya saya
  bisa menilai pertumbuhan.
- Sebagai **staf**, saya ingin mengunduh hasil saringan sebagai Excel atau menyalinnya sebagai
  TSV, supaya saya bisa mengolahnya di spreadsheet seperti dulu.

## Kebutuhan Fungsional

### A. Dashboard — KPI

1. Kartu **Booking hari ini** menghitung seluruh booking dengan `booking_date` = hari ini,
   kecuali `cancelled`.
2. Kartu **Perlu konfirmasi** menghitung booking berstatus `pending` dengan
   `booking_date >= hari ini`. Tanggal yang sudah lewat tidak ikut.
3. Kartu **Sedang dikerjakan** menghitung booking berstatus `in_progress` tanpa batas tanggal.
4. Kartu **Selesai bulan ini** menghitung booking berstatus `completed` yang `booking_date`-nya
   jatuh di bulan kalender berjalan.
5. Setiap kartu menautkan ke `/admin/bookings` dengan saringan yang sudah terisi sesuai maknanya.
6. "Hari ini" dan "bulan ini" dihitung dari `SlotService::today()` (`Asia/Jakarta`), bukan
   `today()` telanjang.

### B. Dashboard — Grafik tren

7. Grafik menampilkan jumlah booking per tanggal untuk 30 hari terakhir, dikelompokkan menurut
   **`booking_date`**, bukan `created_at`.
8. Tanggal tanpa booking tampil bernilai nol — deret tanggalnya dibangun lengkap di server,
   tidak diserahkan ke Recharts.
9. Booking `cancelled` tidak dihitung.

### C. Dashboard — Okupansi hari ini

10. Menampilkan seluruh slot hari ini beserta jumlah terisi dan kuotanya, memakai
    `SlotService::availability()` yang sudah ada.
11. Slot penuh ditandai dengan **warna dan teks** "Penuh" beserta `aria-disabled`, tidak hanya
    warna (`.claude/rules/20 §Aksesibilitas`).
12. Kuota per slot dibaca dari `config('booking.quota_per_slot')` dan dikirim sebagai props —
    tidak ada angka kuota yang ditulis di React.
13. Bila hari ini hari tutup, panel menampilkan alasannya dari
    `SlotService::dateRejectionReason()`, bukan daftar slot kosong.
14. Panel menyediakan tautan "Buka Jadwal" ke `/admin/jadwal`. Panel ini **baca-saja** — tidak
    menampilkan daftar booking per slot.

### D. Dashboard — Tabel booking hari ini & aksi cepat

15. Tabel menampilkan seluruh booking hari ini kecuali `cancelled`, urut `booking_time` lalu `id`.
16. Kolom: jam, kode booking, customer, kendaraan, paket layanan, status, aksi.
17. Aksi cepat yang tersedia — Konfirmasi · Mulai · Selesai — ditentukan **di server** dari
    `BookingStatus::canTransitionTo()` dan dikirim sebagai props boleh/tidak per baris.
18. Aksi cepat memanggil rute `admin.bookings.status` yang sudah ada, lalu memuat ulang hanya
    bagian yang berubah lewat Inertia partial reload. Tidak ada rute status baru.
19. Kegagalan transisi menampilkan pesan galat dari server, bukan asumsi berhasil di klien.

### E. Dashboard — Peringatan calon `no_show`

20. Panel menampilkan booking berstatus `confirmed` dengan `booking_date` **lebih kecil dari**
    hari ini, urut tanggal terlama lebih dulu.
21. Setiap baris menyediakan aksi cepat "Tandai Tidak Hadir" yang memanggil rute status yang sama.
22. Bila tidak ada, panel menampilkan `EmptyState`, bukan tabel kosong.

### F. Laporan — kerangka

23. Satu rute `/admin/laporan` memuat tiga tab: Rekap Booking, Okupansi, Customer Baru.
24. Pemilih periode dipakai bersama seluruh tab: 7 hari · 30 hari · bulan ini · bulan lalu ·
    rentang kustom. Bawaan **30 hari**.
25. Rentang kustom divalidasi Form Request: tanggal akhir tidak boleh mendahului tanggal awal,
    dan rentangnya maksimal **1 tahun**.
26. Periode aktif dan tab aktif tersimpan di query string sehingga tautannya bisa dibagikan dan
    di-*refresh* tanpa kehilangan konteks.
27. Seluruh agregasi dijalankan di database dengan `groupBy`/`selectRaw` berbinding — dilarang
    mengambil seluruh baris lalu menjumlahkannya di PHP.

### G. Laporan — isi tiap tab

28. **Rekap Booking**: total booking pada periode, pecahan per status, per paket layanan, dan per
    model mobil.
29. **Okupansi**: rata-rata pemakaian slot per hari dan per jam sepanjang periode, menunjukkan jam
    tersibuk.
30. **Customer Baru**: jumlah registrasi per periode, dihitung dari `users` ber-role `customer`
    menurut `created_at`.
31. Tab **Pendapatan tidak dirender** — bukan ditampilkan lalu dinonaktifkan.
32. Periode tanpa data menampilkan `EmptyState` yang menyebut periodenya, bukan angka nol tanpa
    penjelasan.

### H. Export

33. **Export Excel** menghasilkan `.xlsx` yang dibangun di server dan mengikuti saringan yang
    sedang aktif.
34. Kolom export persis seperti `docs/07 §A9`: Date · Time · Name · Model · Plat Nomor · Service ·
    Keluhan · Phone · Status, ditambah **Kode Booking** dan **Advisor**.
35. **Salin untuk Spreadsheet** menyalin data yang sama sebagai TSV ke clipboard, dan menampilkan
    toast keberhasilan maupun kegagalan.
36. Kedua tombol terpasang di `/admin/laporan` **dan** `/admin/bookings`, masing-masing mengikuti
    saringan di layarnya sendiri.
37. Export dibatasi rate limit dan tidak boleh mengalirkan seluruh tabel tanpa batas periode.

### I. Navigasi & hak akses

38. `/admin` merender dashboard. Redirect ke `/admin/bookings` dari F1.5.1 dicabut.
39. `AdminLayout` menampilkan item "Dashboard" dan "Laporan" untuk `super_admin` dan
    `service_advisor`.
40. Dashboard yang dilihat advisor **identik** dengan yang dilihat Super Admin — tidak ada elemen
    dashboard yang menyentuh pendapatan.
41. Seluruh rute dashboard, laporan, dan export menegakkan otorisasi lewat `Gate::authorize`, di
    samping middleware `role`. Menyembunyikan menu bukan pengaman.

## Kebutuhan Non-Fungsional

**Keamanan**

- Setiap rute baru memanggil `Gate::authorize` — middleware `role` adalah lapis pertama, bukan
  satu-satunya (`docs/09 §9.3`).
- Parameter periode dan tab masuk lewat **Form Request**, bukan dibaca mentah dari `$request`.
  Rentang tanggal divalidasi batas atasnya.
- Export memakai rate limit; satu klik tidak boleh memicu pemindaian tanpa batas.
- Nomor telepon dan plat pelanggan hanya muncul di layar staf yang login. Tidak ada rute publik
  yang lahir dari PRD ini.
- Tidak ada endpoint JSON baru. Data halaman datang lewat props Inertia
  (`.claude/rules/20`, temuan S8).

**Waktu**

- Seluruh perhitungan tanggal memakai `Asia/Jakarta` lewat `SlotService`. Dilarang `date()`,
  `strtotime()`, atau `new DateTime()` telanjang (temuan B7).
- Batas "hari ini" ditentukan server dan dikirim ke React. React tidak menghitung sendiri
  "kemarin" dari zona waktu peramban.

**Konfigurasi**

- Kuota per slot, daftar slot, dan hari tutup dibaca dari `config/booking.php`. Panjang jendela
  tren (30 hari) dan batas rentang kustom (1 tahun) juga menjadi nilai konfigurasi, bukan angka
  yang ditulis di dua tempat.

**Antarmuka**

- Bahasa Indonesia, istilah konsisten: Booking · Servis · Kendaraan · Paket Layanan.
- Warna hanya dari design token; status hanya lewat `lib/status.ts` dan `<StatusBadge>`.
- Angka dan tanggal diformat lewat `lib/format.ts`, tidak ad-hoc di halaman.
- Benar pada 360 / 768 / 1280. Kartu KPI menumpuk di mobile, tabel jadi kartu, grafik tetap
  terbaca dan tidak menggeser badan halaman secara horizontal.
- Grafik memiliki alternatif tekstual — angka tetap terbaca tanpa mengandalkan warna saja.

**Kinerja**

- Seluruh agregat dijalankan di database; tidak ada N+1. Kueri dashboard dijaga tetap sedikit,
  memanfaatkan index `bookings(status, booking_date)` dan `(booking_date, booking_time)` yang
  sudah ada.
- Halaman dashboard dimuat dengan `React.lazy` supaya bundel Recharts tidak ikut ditanggung
  halaman publik.
- Tanpa cache dan tanpa tabel agregat — lihat Bukan Lingkup #5.

## Keputusan yang Sudah Disetujui

Terkunci pada sesi grill 4 Agustus 2026. **Jangan dibuka ulang.**

1. **A9 dibangun tanpa Pendapatan**; Pendapatan pindah ke roadmap 2.4.7. Tabel `invoices` baru
   lahir di F2.4, sedangkan tiga laporan lain tidak bergantung padanya.
2. **Tren 30 hari memakai `booking_date`**, bukan `created_at` — dashboard menjawab beban bengkel,
   bukan tren permintaan masuk.
3. **KPI "Perlu konfirmasi" mencakup seluruh `pending` yang belum lewat.** Angka yang
   menyembunyikan booking minggu depan menyesatkan.
4. **Peringatan `no_show` memakai ambang tanggal, bukan jam** — `confirmed` dengan `booking_date`
   sudah lewat — beserta aksi cepat "Tandai Tidak Hadir".
5. **Aksi cepat memakai rute `admin.bookings.status` yang sudah ada.** State machine tidak boleh
   hidup di dua tempat (cacat B2 sistem lama).
6. **Okupansi dashboard ringkas dan baca-saja**, dengan tautan ke A3. Menduplikasi layar kerja A3
   berarti dua tempat yang harus diperbarui bersamaan.
7. **Satu rute `/admin/laporan` dengan tab**, bukan empat rute terpisah.
8. **Periode: 4 preset + rentang kustom, bawaan 30 hari, batas 1 tahun.**
9. **Export terpasang di A2 juga**, bukan hanya di Laporan.

Berlaku pula tanpa dibahas ulang, dari `docs/` dan keputusan final proyek: Recharts sebagai
pustaka grafik (di-*bundle*, bukan CDN) · `maatwebsite/excel` untuk `.xlsx` · kolom export persis
sistem lama · dashboard dilihat kedua role staf · pendapatan SA saja · `/admin` kembali menjadi
dashboard.

## Pertanyaan Terbuka

1. ~~**Apakah ekstensi PHP `zip` tersedia di runtime Railway?**~~ — **ditutup 4 Agustus 2026.**
   Pertanyaannya tidak dijawab, melainkan **dihindari**: `maatwebsite/excel` tidak jadi dipasang,
   sehingga ketersediaan ekstensi `zip` tidak lagi menentukan apa pun. Risikonya tidak sepadan —
   `composer install` yang gagal menjatuhkan seluruh deploy, bukan hanya fitur export. Jalur
   cadangan yang sudah disepakati diambil sejak awal: CSV ber-BOM UTF-8 yang dibuat server tanpa
   dependensi apa pun, dan Salin untuk Spreadsheet berjalan seperti rencana.

Selain itu tidak ada. Sembilan pertanyaan sesi grill seluruhnya sudah terjawab.

## Dampak ke Rancangan `docs/`

**Ya — dua berkas harus ikut diperbarui.** Keduanya berasal dari keputusan grill #1.
**Seluruhnya sudah dikerjakan 4 Agustus 2026.**

| Berkas | Bagian | Perubahan | Status |
|--------|--------|-----------|--------|
| `docs/07-modul-admin.md` | `§A9 — Laporan & Export` | Pisahkan baris **Pendapatan** dari paket A9; tandai bahwa ia menyusul di F2.4.7 karena bergantung pada tabel `invoices`. Tiga laporan lain tetap di F2.1. | ✅ |
| `docs/10-roadmap-implementasi.md` | butir **2.1.4** | Hapus "pendapatan **SA saja**" dari lingkup laporan F2.1. | ✅ |
| `docs/10-roadmap-implementasi.md` | sub-fase **F2.4** | Tambahkan butir **2.4.7**: laporan Pendapatan (total invoice `issued`/`paid` per periode, SA saja) beserta baris matriksnya. | ✅ |
| `docs/07-modul-admin.md` · `docs/10` butir **2.1.5** | Export | **Tambahan, tidak direncanakan:** `.xlsx`/`maatwebsite/excel` diganti CSV dibuat server. Ikut diperbarui supaya dokumen tidak menjanjikan paket yang tidak dipasang. | ✅ |

Tidak ada perubahan pada skema database (`docs/04`), alur bisnis (`docs/05`), maupun matriks hak
akses (`docs/09 §9.3`) — baris "Laporan pendapatan | SA saja" tetap berlaku apa adanya, hanya
waktunya bergeser.

## Modul yang Tersentuh

**Tidak ada migration.** Kedua index yang dibutuhkan agregasi — `bookings(status, booking_date)`
dan `bookings(booking_date, booking_time)` — sudah ada sejak `2026_08_03_100010`.

| Berkas | Baru / Ubah | Yang berubah |
|--------|-------------|--------------|
| `app/Services/DashboardService.php` | **Baru** | Seluruh agregat A1: empat KPI, deret tren 30 hari, baris booking hari ini, daftar calon `no_show`. Tidak menyentuh `request()`/`auth()` |
| `app/Services/ReportService.php` | **Baru** | Agregat A9: rekap booking (per status/paket/model), okupansi rata-rata per hari & per jam, hitungan customer baru |
| `app/Support/ReportPeriod.php` | **Baru** | Menerjemahkan preset periode menjadi rentang `CarbonImmutable`; satu-satunya tempat definisi "30 hari"/"bulan lalu" hidup |
| `app/Exports/BookingsExport.php` | **Baru** | Kelas `maatwebsite/excel`; kolom persis `docs/07 §A9`. Dipakai rute laporan **dan** rute booking |
| `app/Http/Controllers/Admin/DashboardController.php` | **Baru** | `__invoke` — `Gate::authorize`, panggil `DashboardService`, `Inertia::render` |
| `app/Http/Controllers/Admin/ReportController.php` | **Baru** | `__invoke` — Form Request periode, panggil `ReportService`, render |
| `app/Http/Controllers/Admin/BookingExportController.php` | **Baru** | Unduhan `.xlsx` dan muatan TSV, memakai `BookingFilters` yang sudah ada |
| `app/Http/Requests/Admin/ReportFilterRequest.php` | **Baru** | Validasi preset, tanggal awal/akhir, batas 1 tahun, tab aktif |
| `app/Support/BookingFilters.php` | Ubah | Dipakai ulang oleh rute export; ditambah kemampuan membatasi rentang tanggal bila belum ada |
| `app/Policies/BookingPolicy.php` | Ubah | Tambah kemampuan `viewReports` (kedua role staf); pendapatan menyusul di F2.4 |
| `config/booking.php` | Ubah | Panjang jendela tren (30 hari) dan batas rentang laporan (1 tahun) |
| `routes/web.php` | Ubah | `/admin` → `DashboardController` (cabut redirect); tambah `admin/laporan`, `admin/laporan/export`, `admin/bookings/export` |
| `resources/js/pages/admin/dashboard.tsx` | **Baru** | Halaman A1, dimuat `React.lazy` |
| `resources/js/pages/admin/laporan/index.tsx` | **Baru** | Halaman A9 dengan tab |
| `resources/js/components/admin/kpi-card.tsx` | **Baru** | Kartu KPI dengan tautan bersaringan |
| `resources/js/components/admin/booking-trend-chart.tsx` | **Baru** | Grafik garis Recharts + alternatif tekstual |
| `resources/js/components/admin/slot-occupancy-panel.tsx` | **Baru** | Okupansi ringkas baca-saja + tautan ke A3 |
| `resources/js/components/admin/quick-status-actions.tsx` | **Baru** | Tombol Konfirmasi/Mulai/Selesai/Tidak Hadir, dikendalikan props boolean dari server |
| `resources/js/components/admin/period-picker.tsx` | **Baru** | Pemilih periode bersama seluruh tab laporan |
| `resources/js/components/admin/export-buttons.tsx` | **Baru** | Export Excel + Salin TSV; dipakai laporan dan daftar booking |
| `resources/js/pages/admin/bookings/index.tsx` | Ubah | Pasang `ExportButtons` di bilah aksi |
| `resources/js/layouts/admin-layout.tsx` | Ubah | Tambah item menu "Dashboard" dan "Laporan" |
| `resources/js/types/index.ts` | Ubah | Tipe props dashboard, laporan, periode, deret tren |
| `tests/Feature/Admin/DashboardTest.php` | **Baru** | Angka KPI, batas tanggal tren, aksi cepat, peringatan `no_show` |
| `tests/Feature/Admin/ReportTest.php` | **Baru** | Agregat per tab, validasi periode, batas 1 tahun |
| `tests/Feature/Admin/BookingExportTest.php` | **Baru** | Kolom export, kepatuhan pada saringan aktif, penolakan role |
| `tests/Feature/Admin/AdminAccessTest.php` | Ubah | Tambah baris rute baru — customer & tamu ditolak |
| `composer.json` | Ubah | `maatwebsite/excel` |
| `package.json` | Ubah | `recharts` |

## Risiko

**`maatwebsite/excel` bisa menggagalkan build Railway.** PhpSpreadsheet membutuhkan ekstensi PHP
`zip`. Belum diverifikasi tersedia. → Periksa ekstensinya **sebelum** menambahkan paket; jalur
cadangan CSV/TSV tanpa dependensi sudah disepakati, dan Salin untuk Spreadsheet tetap berfungsi
tanpa paket apa pun.

**Recharts menambah ±100 KB ke bundel.** Halaman publik tidak boleh ikut menanggungnya, karena
`docs/06` menetapkan muat cepat sebagai prinsip. → Muat dashboard dengan `React.lazy`; verifikasi
ukuran bundel halaman publik tidak berubah setelah `npm run build`.

**Agregat dihitung ulang setiap kunjungan dashboard.** Dengan volume satu bengkel dan index yang
sudah ada, ini sepele — tetapi menjadi masalah bila kelak tabel `bookings` tumbuh besar. → Jangan
menambahkan cache sebelum ada bukti lambat; cache yang basi pada dashboard operasional lebih
membingungkan daripada kueri yang sedikit lebih lambat. Ukur dulu.

**Definisi "30 hari" berpeluang ditulis di dua tempat** — di service dan di label antarmuka.
Persis pola cacat B2 sistem lama. → Nilainya hidup di `config/booking.php`, dibaca server, dan
label antarmukanya datang dari props.

**Aksi cepat memakai rute status yang sama dengan detail booking.** Bila kelak `BookingStatusController`
berubah, dashboard ikut terpengaruh tanpa ada yang menguji. → `DashboardTest` menembak rute
sungguhan, bukan memanggil service langsung, sehingga perubahan itu ketahuan.

**Clipboard API menolak diam-diam.** Hanya berjalan di konteks aman dan butuh gestur pengguna. →
Railway sudah HTTPS; kegagalan penyalinan wajib menampilkan toast, bukan gagal tanpa suara.

**Export bisa menjadi jalur kebocoran data pribadi.** Berkasnya memuat nama, nomor telepon, dan
plat. → Rutenya menegakkan `Gate::authorize`, dibatasi rate limit, dan wajib punya uji yang
membuktikan customer serta tamu ditolak.

## Ukuran Keberhasilan

1. **Advisor membuka `/admin` sebagai layar pertama**, bukan langsung ke `/admin/bookings` —
   terlihat dari kebiasaan pemakaian setelah dua minggu.
2. **Tidak ada lagi booking `confirmed` yang tanggalnya lewat lebih dari 3 hari.** Sebelum fitur
   ini, tidak ada yang menghitungnya sama sekali; sesudahnya, panel peringatan seharusnya kosong
   pada akhir setiap hari kerja.
3. **Perubahan status dari dashboard terpakai nyata** — sebagian transisi status tercatat berasal
   dari aksi cepat, bukan seluruhnya dari halaman detail. Bila nol, aksi cepatnya tidak berguna
   dan perlu ditinjau.
4. **Export dipakai minimal sekali per bulan** oleh pemilik bengkel. Bila tidak pernah, fitur
   yang dipertahankan dari sistem lama itu ternyata tidak dibutuhkan dan tidak perlu dikembangkan
   lebih jauh di F2.4.
5. **Pertanyaan "ada slot kosong jam berapa hari ini" terjawab tanpa membuka `/admin/jadwal`** —
   dikonfirmasi lewat satu sesi pengamatan langsung dengan advisor.
