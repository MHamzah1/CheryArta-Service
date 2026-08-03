# Grill — F2.1 Dashboard & Laporan (A1 + A9)

**Status:** ✅ selesai · dibuka dan ditutup 4 Agustus 2026
**Keputusan:** seluruh rekomendasi disetujui pemilik proyek dalam satu putaran
**Berikutnya:** `/write-prd` → `docs/prds/prd-dashboard-laporan.md`

Sumber yang sudah dibaca: `docs/07-modul-admin.md §A1, §A9, §A13, §A14` ·
`docs/10-roadmap-implementasi.md §F2.1` · `docs/09-keamanan-hak-akses.md §9.3` ·
`docs/06-desain-ui-ux.md` (wireframe Dashboard Admin) · `.claude/rules/10,20,30,40,50` ·
kode: `ScheduleController`, `BookingController@index`, `SlotService`, `Booking`, `BookingStatus`.

---

## Keputusan implisit — tidak ditanyakan

Sudah ditetapkan rancangan atau kode. Dicatat supaya tidak dibuka ulang.

| Hal | Ketetapan | Sumber |
|-----|-----------|--------|
| Sub-fase & estimasi | F2.1, 3 hari, sub-fase **pertama** Big Fase 2 | `docs/10 §Ringkasan Jadwal` |
| `/admin` | Redirect ke `/admin/bookings` dicabut; `/admin` kembali jadi dashboard | roadmap 2.1.3, **R8** |
| Siapa yang melihat dashboard | SA **dan** advisor | `docs/07 §A1`, matriks `docs/09 §9.3` |
| Siapa yang melihat laporan | SA & advisor melihat; export keduanya. **Pendapatan SA saja** | `docs/07 §A9`, matriks `docs/09 §9.3` |
| Elemen dashboard | KPI · tren 30 hari · okupansi slot hari ini · tabel booking hari ini · peringatan calon `no_show` | `docs/07 §A1` |
| Aksi cepat | Konfirmasi · Mulai · Selesai, tanpa pindah halaman (Inertia partial reload) | `docs/07 §A1`, roadmap 2.1.2 |
| Pustaka grafik | **Recharts**, di-bundle (bukan CDN — `.claude/rules/20` melarang impor CDN) | roadmap 2.1.1 |
| Pustaka export | **`maatwebsite/excel`**, `.xlsx` dibuat di server, mengikuti filter aktif | roadmap 2.1.5, `docs/07 §A9` |
| Kolom export | Date · Time · Name · Model · Plat Nomor · Service · Keluhan · Phone · Status + Kode Booking + Advisor | `docs/07 §A9` |
| "Salin untuk Spreadsheet" | TSV ke clipboard — mempertahankan perilaku sistem lama | `docs/07 §A9` |
| Kartu draft WhatsApp | **Tidak** dibangun di F2.1 — tabelnya baru lahir di F2.3.1, kartunya di F2.3.5 | `docs/10 §Big Fase 2` |
| Menu "Dashboard" & "Laporan" di sidebar | Ditambahkan ke `AdminLayout`; Laporan tampil untuk kedua role | `docs/07 §A13` |
| Layout | `AdminLayout` yang sudah ada | `.claude/rules/20` |
| Sumber okupansi | `SlotService::availability()` yang sudah ada — bukan hitungan baru | kode `SlotService:150` |
| Zona waktu | Seluruh "hari ini" dari `SlotService::today()` (`Asia/Jakarta`), bukan `today()` telanjang | `.claude/rules/10`, temuan B7 |
| Kuota per slot | Dari `config('booking.quota_per_slot')` — tidak ditulis di React | keputusan final #3 |
| Agregasi | Dihitung di database (`groupBy`/`selectRaw` dengan binding), bukan `->get()` lalu difilter di PHP | `.claude/rules/10`, temuan S8 |
| Otorisasi | `Gate::authorize` di tiap method + baris matriks A14 diuji | `.claude/rules/50`, temuan S3 |

---

## Keputusan yang diambil

### Q1 — Laporan **Pendapatan** butuh tabel `invoices` yang belum ada ✅

**Temuan.** `docs/07 §A9` mendefinisikan laporan Pendapatan sebagai "total invoice
`issued`/`paid` per periode (SA saja)". Tabel `invoices` **tidak ada** — `database/migrations/`
berhenti di `booking_status_histories`, dan `app/Models/Invoice.php` tidak ada. Enum
`InvoiceStatus` sudah ada, tetapi tanpa tabel ia belum berarti apa-apa.

Sebelum pengurutan ulang, invoice (F2.3 lama) datang **sebelum** laporan (F2.4 lama), jadi
urutannya masuk akal. Setelah invoice dibelakangkan, F2.1 Laporan kini berdiri **tiga sub-fase
sebelum** tabelnya lahir di F2.4.

**Keputusan: bangun A9 di F2.1 tanpa Pendapatan; pindahkan Pendapatan ke F2.4 sebagai butir baru
(2.4.7).**

Alasannya: tiga laporan lain — rekap booking, okupansi, customer baru — seluruhnya bisa dihitung
dari `bookings` dan `users` yang sudah ada. Hanya Pendapatan yang menggantung. Memindahkan satu
laporan jauh lebih murah daripada menarik invoice maju, dan menarik invoice maju justru
membatalkan keputusan pengurutan yang baru diambil.

Konsekuensi yang diterima: menu Laporan tampil tanpa tab Pendapatan sepanjang F2.1–F2.3. Tab itu
**tidak dirender**, bukan ditampilkan lalu dinonaktifkan — sesuai pola yang sudah dipakai
`AdminLayout` untuk menu yang modulnya belum ada.

- **Keputusan saya:** `[x]` disetujui

⚠️ Potensi masalah: `docs/07 §A9` menyatakan A9 sebagai satu paket empat laporan → bila
Pendapatan dipisah, dokumen itu harus ikut diperbarui supaya kode dan dokumen tidak bertentangan
→ PRD mencatatnya di bagian "Dampak ke Rancangan `docs/`"; suntingannya dilakukan saat
implementasi, bukan sekarang.

### Q2 — Grafik tren 30 hari dihitung dari `booking_date` ✅

Dua tafsir yang hasilnya berbeda jauh: `booking_date` menjawab "seberapa padat bengkel",
`created_at` menjawab "seberapa deras permintaan masuk".

**Keputusan: `booking_date`.** Dashboard ini dibuka untuk menjawab "apa yang harus dikerjakan" —
beban bengkel, bukan tren pemasaran. Tren permintaan masuk lebih tepat jadi laporan di A9, bukan
kartu di dashboard.

- **Keputusan saya:** `[x]` disetujui

### Q3 — KPI "Perlu konfirmasi" mencakup seluruh `pending` yang belum lewat ✅

**Keputusan: seluruh `pending` dengan `booking_date >= hari ini`.** Angka yang menyembunyikan
booking minggu depan membuat orang merasa sudah beres padahal belum. Bila angkanya terlalu besar
untuk dibaca, itu masalah nyata yang memang harus terlihat.

- **Keputusan saya:** `[x]` disetujui

### Q4 — Peringatan calon `no_show`: `booking_date` < hari ini, dengan aksi cepat ✅

**Keputusan: `confirmed` dengan `booking_date` lebih kecil dari hari ini** — jadi paling cepat
muncul keesokan harinya — **dengan aksi cepat "Tandai Tidak Hadir"**. Memakai ambang jam akan
membanjiri peringatan sepanjang hari kerja untuk booking yang advisornya memang belum sempat
menyentuh.

- **Keputusan saya:** `[x]` disetujui

### Q5 — Aksi cepat memakai rute `admin.bookings.status` yang sudah ada ✅

**Keputusan: pakai rute itu apa adanya.** Dashboard mengirim `PUT` lalu
`router.reload({ only: [...] })`. Rute baru berarti state machine hidup di dua tempat — persis
cacat B2 sistem lama. `BookingStatusController` sudah menegakkan transisi sah dan menulis riwayat
status.

- **Keputusan saya:** `[x]` disetujui

### Q6 — Okupansi di dashboard versi ringkas baca-saja ✅

**Keputusan: bar `n/2` per slot tanpa daftar booking, ditambah tautan "Buka Jadwal" ke A3.**
Menduplikasi layar kerja A3 di dashboard membuat dua tempat yang harus diperbarui setiap kali
aturan slot berubah.

- **Keputusan saya:** `[x]` disetujui

### Q7 — Laporan: satu rute dengan tab di dalamnya ✅

**Keputusan: satu rute `/admin/laporan`**, pemilih periode dipakai bersama seluruh tab, tab aktif
ikut di query string supaya tautannya bisa dibagikan. Empat rute terpisah berarti empat kali
menyalin pemilih periode dan tombol export.

- **Keputusan saya:** `[x]` disetujui

### Q8 — Periode: preset + rentang kustom, bawaan 30 hari ✅

**Keputusan: 7 hari · 30 hari · bulan ini · bulan lalu · rentang kustom. Bawaan 30 hari.**
Rentang kustom dibatasi maksimal 1 tahun supaya satu klik tidak memindai seluruh tabel.

- **Keputusan saya:** `[x]` disetujui

### Q9 — Export juga tersedia di daftar Booking (A2) ✅

**Keputusan: taruh kedua tombol export di A2 juga.** Advisor yang sudah menyaring daftar booking
akan mencari tombol export di situ, bukan berpindah ke Laporan lalu menyaring ulang. Biayanya
kecil: satu `Export` class yang sama, dipanggil dua rute.

- **Keputusan saya:** `[x]` disetujui

---

## Keputusan turunan

Butir kecil yang jatuh sendiri dari sembilan keputusan di atas. Diselesaikan tanpa ditanyakan;
PRD mengunci bentuk akhirnya.

| Hal | Ketetapan | Alasan |
|-----|-----------|--------|
| KPI "Selesai bulan ini" | `completed` dalam bulan kalender berjalan, berdasarkan `booking_date` | Konsisten dengan Q2 |
| Tabel "Booking hari ini" | Seluruh status kecuali `cancelled`, urut `booking_time` lalu `id` | Booking batal bukan pekerjaan hari ini; urutannya sama dengan A3 |
| Dashboard SA vs advisor | Identik — tidak ada elemen dashboard yang menyentuh pendapatan | Matriks `docs/09 §9.3` hanya membatasi laporan pendapatan |
| Daftar kosong | `EmptyState` untuk tabel booking hari ini dan peringatan `no_show`; grafik menampilkan garis nol, bukan disembunyikan | `.claude/rules/40` |
| Aksi cepat mana yang tampil | Hanya transisi yang sah dari status baris itu, dihitung server dan dikirim sebagai props boolean | `.claude/rules/20`, temuan S3 |

---

## Risiko yang dibawa ke PRD

⚠️ `maatwebsite/excel` bergantung pada PhpSpreadsheet dan ekstensi PHP `zip` → build Railway bisa
gagal bila ekstensinya tidak ada → verifikasi ekstensi sebelum menambahkan paketnya; TSV clipboard
tetap berjalan tanpa ekstensi apa pun sebagai jalur cadangan.

⚠️ Recharts menambah ±100 KB ke bundel → halaman publik tidak boleh ikut menanggungnya →
muat dashboard dengan `React.lazy` sesuai `.claude/rules/20 §Kinerja`.

⚠️ Agregat dashboard dihitung ulang setiap kunjungan → dengan indeks
`bookings(status, booking_date)` yang sudah ada, beban satu bengkel masih sepele → jangan
menambahkan cache sebelum ada bukti lambat; cache yang basi lebih membingungkan daripada kueri
yang cepat.

⚠️ "Salin untuk Spreadsheet" memakai Clipboard API → hanya jalan di konteks aman (HTTPS) dan
butuh gestur pengguna → Railway sudah HTTPS; sediakan umpan balik toast bila penyalinan ditolak
peramban.

---

## Log Keputusan

| # | Keputusan | Alasan |
|---|-----------|--------|
| 1 | A9 dibangun di F2.1 **tanpa** Pendapatan; Pendapatan pindah ke F2.4.7 | Tabel `invoices` baru lahir di F2.4; tiga laporan lain tidak bergantung padanya |
| 2 | Tren 30 hari memakai `booking_date` | Dashboard menjawab beban bengkel, bukan tren permintaan |
| 3 | KPI "Perlu konfirmasi" = seluruh `pending` yang belum lewat | Angka yang menyembunyikan booking mendatang menyesatkan |
| 4 | Peringatan `no_show` = `confirmed` dengan tanggal sudah lewat, + aksi "Tandai Tidak Hadir" | Ambang jam membanjiri peringatan di tengah hari kerja |
| 5 | Aksi cepat memakai `admin.bookings.status` yang sudah ada | State machine tidak boleh hidup di dua tempat (cacat B2) |
| 6 | Okupansi dashboard ringkas baca-saja + tautan ke A3 | Hindari dua layar yang harus diperbarui bersamaan |
| 7 | Satu rute `/admin/laporan` dengan tab | Pemilih periode & export tidak disalin empat kali |
| 8 | Periode: 4 preset + kustom, bawaan 30 hari, batas 1 tahun | Menghindari pemindaian tabel penuh dari satu klik |
| 9 | Export juga di A2 | Advisor mencarinya di tempat ia menyaring |

## Tindak lanjut yang belum dikerjakan

- [ ] `docs/07-modul-admin.md §A9` — pisahkan Pendapatan dari paket A9, tandai F2.4.7
- [ ] `docs/10-roadmap-implementasi.md` — hapus Pendapatan dari 2.1.4, tambahkan butir 2.4.7

Keduanya disunting saat implementasi F2.1, bukan sekarang. PRD mencatatnya di bagian
"Dampak ke Rancangan `docs/`".
