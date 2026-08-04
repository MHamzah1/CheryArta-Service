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
  Tabel template, CRUD template, dan log pengiriman menyusul.
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
>
> Satu kartu masih menyusul: **hitungan draft WhatsApp belum dikirim**, karena tabel
> `whatsapp_messages` baru lahir di F2.3.1. Kartunya dibangun di F2.3.5 — dashboard tidak
> menampilkan "0 draft" untuk tabel yang belum ada.

**Tujuan:** menjawab "apa yang harus dikerjakan hari ini" dalam satu layar.

| Elemen | Isi | Sumber data |
|--------|-----|-------------|
| Kartu KPI | Booking hari ini · Perlu konfirmasi · Sedang dikerjakan · Selesai bulan ini | agregat `bookings` |
| Grafik tren | Jumlah booking 30 hari terakhir (garis) | `bookings` dikelompokkan per tanggal |
| Okupansi slot hari ini | 11 baris slot dengan bar terisi `n/2` | `SlotService::occupancy(today)` |
| Tabel booking hari ini | Jam, kode, customer, kendaraan, paket, status, aksi cepat | `bookings` hari ini, urut jam |
| Peringatan | Booking `confirmed` yang tanggalnya sudah lewat (calon `no_show`) | `bookings` |

Aksi cepat pada tabel: **Konfirmasi** · **Mulai** · **Selesai** — tanpa berpindah halaman
(Inertia partial reload).

## A2 — Manajemen Booking  `/admin/bookings`  (SA, ADV)

**Daftar**

- Kolom: Kode · Tanggal · Jam · Customer · Kendaraan · Paket · Status · Advisor · Aksi.
- Filter (dijalankan **di server**, mempertahankan fitur lama): pencarian nama/plat/kode,
  status, rentang tanggal, paket layanan, advisor penanggung jawab.
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
Detail: profil, daftar kendaraan, riwayat booking lengkap, total nilai invoice.
Aksi: nonaktifkan akun (SA), reset password (SA), buat booking untuk customer ini (ADV).
**Tidak ada** aksi hapus permanen.

**`/admin/vehicles`** — daftar seluruh unit terdaftar: plat, model, tahun, pemilik, odometer
terakhir, jumlah servis. Berguna untuk pertanyaan "unit ini terakhir servis kapan?".

## A7 — Notifikasi WhatsApp  (SA, ADV) — ⚠️ sebagian di Big Fase 1

Panel tertanam di halaman detail booking, ditambah `/admin/wa-template` (SA) untuk menyunting
teks template. Rincian mekanisme: [08-notifikasi-whatsapp.md](08-notifikasi-whatsapp.md).

> **Big Fase 1 hanya membuat tombol "Chat via WhatsApp"** di detail booking — membuka `wa.me`
> dengan teks dari `config/company.php`. Tabel `whatsapp_templates` & `whatsapp_messages`, panel
> draft, penanda "belum dikirim", dan CRUD template menyusul di F2.3.
>
> Terpasang di F1.5: `App\Services\WhatsAppNotifier::draft()` menyusun tautan dan teksnya di
> server (nomor selalu bentuk ternormalisasi `62…`, `rawurlencode`), lalu mengirimkannya ke
> React sebagai prop `whatsapp`. Teks per status ada di `config('company.wa_messages')` dan
> memakai placeholder yang sama dengan [08 §8.4](08-notifikasi-whatsapp.md). Bernilai `null`
> bila pelanggan tidak punya nomor WhatsApp — tombolnya tidak dirender sama sekali.

## A8 — Invoice  `/admin/invoices`  (SA, ADV) — ⏳ Big Fase 2

| Layar | Isi |
|-------|-----|
| Daftar | Nomor, tanggal, customer, kode booking, total, status, aksi |
| Penyunting | Item jasa & part (deskripsi, qty, harga satuan, subtotal otomatis), diskon, pajak, catatan |
| Aksi | Simpan draft · Terbitkan · Tandai lunas (+ metode bayar) · Batalkan (void, wajib alasan) · Unduh PDF |

Aturan: invoice `issued` tidak bisa disunting; hanya SA yang boleh mem-`void`. Seluruh perhitungan
dilakukan di server ([05 §5.6](05-alur-bisnis.md#56-alur-estimasi-biaya--invoice)).

## A9 — Laporan & Export  `/admin/laporan`  (SA, ADV melihat; export keduanya) — ✅ F2.1

Satu rute dengan tab, berbagi satu pemilih periode (7 hari · 30 hari · bulan ini · bulan lalu ·
rentang kustom; bawaan 30 hari, batas 1 tahun dari `config('booking.reports')`).

| Laporan | Isi | Kapan |
|---------|-----|-------|
| Rekap booking | Per periode: total, per status, per paket, per model mobil | ✅ F2.1 |
| Okupansi | Rata-rata pemakaian slot per hari & per jam — menunjukkan jam sibuk | ✅ F2.1 |
| Customer baru | Jumlah registrasi per periode | ✅ F2.1 |
| Pendapatan | Total invoice `issued`/`paid` per periode (SA saja) | ⏳ **F2.4.7** |

> **Kenapa Pendapatan menyusul.** Sumbernya tabel `invoices`, yang baru lahir di F2.4.1.
> Tiga laporan lain tidak bergantung padanya, jadi A9 dibangun tanpa Pendapatan dan tab-nya
> **tidak dirender sama sekali** — bukan ditampilkan lalu dinonaktifkan, karena tab mati
> membuat orang menyangka fiturnya rusak.

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

## A10 — Konten Landing Page  (SA saja) — ⚠️ sebagian di Big Fase 1

> **Big Fase 1 hanya membuat tabelnya** (`facilities`, `faqs`, `testimonials`,
> `contact_messages`) beserta seeder idempoten, supaya landing page F1.6 punya sumber data yang
> nyata sejak awal. Keempat layar di bawah dibangun di F2.2 — tanpa migrasi data, karena tabelnya
> sudah terisi.
>
> Akibatnya selama Big Fase 1: **pesan dari form kontak masuk ke database tetapi belum bisa
> dibaca dari panel admin.** Halaman kontak karena itu menonjolkan tombol WhatsApp sebagai jalur
> utama, dan form hanya jalur cadangan.

| Modul | Isi |
|-------|-----|
| `/admin/fasilitas` | 8 fasilitas: judul, deskripsi, gambar, urutan, aktif |
| `/admin/faq` | Pertanyaan, jawaban, kategori, urutan, aktif |
| `/admin/testimoni` | Nama, model mobil, rating 1–5, isi, terbitkan/sembunyikan |
| `/admin/pesan-masuk` | Pesan dari form kontak: tandai dibaca, balas via WA (klik-to-chat), hapus spam |

## A11 — Pengguna Internal  `/admin/users`  (SA saja) — ⏳ Big Fase 2 (F2.2.3)

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

> **Seluruhnya di Big Fase 2** (**R9**): layar dan pengamannya di F2.2.3–F2.2.4, penegakan
> `must_reset_password` saat login di F2.2.5, pencatatan perubahan akun ke activity log di
> F2.2.6. F2.2 adalah sub-fase **kedua** Big Fase 2 — sesudah dashboard, sebelum WhatsApp dan
> invoice.
>
> Selama Big Fase 1 layar ini **tidak ada**, dan `/admin/users` tidak terdaftar sebagai rute.
> Akun staf lahir dari `UserSeeder` saja.

## A12 — Activity Log  `/admin/activity-log`  (SA saja) — ⏳ Big Fase 2

Menjawab "siapa mengubah apa dan kapan" — kebutuhan yang sama sekali tidak terpenuhi sistem lama.
Kolom: waktu, pelaku, aksi, objek, perubahan (sebelum → sesudah). Filter: pelaku, jenis objek,
rentang tanggal. Retensi 12 bulan.

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

> **Big Fase 1** hanya merender menu yang modulnya sudah ada: Jadwal · Booking · Customer ·
> Kendaraan · Katalog Mobil (SA) · Paket Layanan (SA). Menu lain tidak dirender — bukan
> ditampilkan-lalu-dinonaktifkan. Item "Dashboard" juga belum ada karena `/admin` mengalihkan
> ke `/admin/bookings`, dan grup "Sistem" belum ada karena A11 mundur ke Big Fase 2 (**R9**).

## A14 — Ringkasan Matriks Hak Akses

> Baris yang menyangkut **modul yang sudah ada** — A2–A6, katalog, dan paket layanan — berlaku
> dan teruji sejak Big Fase 1 (F1.3.7, F1.5.9), lalu disusun ulang sebagai satu uji per baris di
> **F2.2.7** (`AccessMatrixTest`) supaya baris yang *hilang* ikut kelihatan — berkasnya dibuat
> sekaligus lengkap dengan baris konten dan pengguna internal. Baris template WA menyusul di
> F2.3.6, baris invoice di F2.4.6. Menunda otorisasi berarti mengulang temuan S3 sistem lama.

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
