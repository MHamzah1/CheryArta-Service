# 07 — Modul Admin Internal

Legenda akses: **SA** = Super Admin · **ADV** = Service Advisor

## A1 — Dashboard  `/admin`  (SA, ADV)

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

## A7 — Notifikasi WhatsApp  (SA, ADV)

Panel tertanam di halaman detail booking, ditambah `/admin/wa-template` (SA) untuk menyunting
teks template. Rincian mekanisme: [08-notifikasi-whatsapp.md](08-notifikasi-whatsapp.md).

## A8 — Invoice  `/admin/invoices`  (SA, ADV)

| Layar | Isi |
|-------|-----|
| Daftar | Nomor, tanggal, customer, kode booking, total, status, aksi |
| Penyunting | Item jasa & part (deskripsi, qty, harga satuan, subtotal otomatis), diskon, pajak, catatan |
| Aksi | Simpan draft · Terbitkan · Tandai lunas (+ metode bayar) · Batalkan (void, wajib alasan) · Unduh PDF |

Aturan: invoice `issued` tidak bisa disunting; hanya SA yang boleh mem-`void`. Seluruh perhitungan
dilakukan di server ([05 §5.6](05-alur-bisnis.md#56-alur-estimasi-biaya--invoice)).

## A9 — Laporan & Export  `/admin/laporan`  (SA, ADV melihat; export keduanya)

| Laporan | Isi |
|---------|-----|
| Rekap booking | Per periode: total, per status, per paket, per model mobil |
| Okupansi | Rata-rata pemakaian slot per hari & per jam — menunjukkan jam sibuk |
| Pendapatan | Total invoice `issued`/`paid` per periode (SA saja) |
| Customer baru | Jumlah registrasi per periode |

Export mempertahankan kedua fitur sistem lama:
- **Export Excel** — `.xlsx` dibuat di server (`maatwebsite/excel`), mengikuti filter yang aktif.
- **Salin untuk Spreadsheet** — menyalin TSV ke clipboard agar bisa ditempel ke Google Sheets,
  sama seperti perilaku lama.

Kolom export sama dengan sistem lama: Date, Time, Name, Model, Plat Nomor, Service, Keluhan,
Phone, Status — ditambah Kode Booking dan Advisor.

## A10 — Konten Landing Page  (SA saja)

| Modul | Isi |
|-------|-----|
| `/admin/fasilitas` | 8 fasilitas: judul, deskripsi, gambar, urutan, aktif |
| `/admin/faq` | Pertanyaan, jawaban, kategori, urutan, aktif |
| `/admin/testimoni` | Nama, model mobil, rating 1–5, isi, terbitkan/sembunyikan |
| `/admin/pesan-masuk` | Pesan dari form kontak: tandai dibaca, balas via WA (klik-to-chat), hapus spam |

## A11 — Pengguna Internal  `/admin/users`  (SA saja)

Daftar akun staf: nama, email, role, status aktif, login terakhir.
Aksi: tambah advisor, ubah role, nonaktifkan, reset password.

Pengaman:
- Super Admin tidak bisa menurunkan role dirinya sendiri.
- Super Admin terakhir tidak bisa dinonaktifkan.
- Akun customer tidak dikelola di sini (ada di A6).

## A12 — Activity Log  `/admin/activity-log`  (SA saja)

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

## A14 — Ringkasan Matriks Hak Akses

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
