# PRD: Invoice (A8)

**Tahap 11 · `F2.4`** — [roadmap](../10-roadmap-implementasi.md#tahap-11--invoice--f24--selesai), butir 2.4.1–2.4.8
**Keputusan:** [`docs/grills/grill-invoice.md`](../grills/grill-invoice.md) — 10 dari 10 terjawab, 5 Agustus 2026
**Modul admin:** A8 (baru) · menyentuh A6, A7, A9, A13, A14
**Status:** ✅ **diimplementasikan 5 Agustus 2026** — 661 uji Pest hijau; Pint, PHPStan, ESLint,
`tsc --noEmit`, `npm run build` bersih; migration diuji `migrate` **dan** `rollback` terhadap DB
Railway. Sembilan penyimpangan yang muncul saat pengerjaan tercatat di
[roadmap Tahap 11](../10-roadmap-implementasi.md#tahap-11--invoice--f24--selesai).

---

## Masalah

Sistem lama **tidak punya invoice sama sekali**. Perbandingan di
[05 §5.9](../05-alur-bisnis.md) mencatatnya sebagai satu-satunya baris yang isinya "Tidak ada" →
"Estimasi + invoice + PDF". Akibatnya, tiga hal yang berbeda sama-sama tidak terselesaikan:

1. **Pelanggan tidak pernah tahu rincian biaya** sampai berdiri di kasir. US-C7 di
   [02 §2.3](../02-kebutuhan-produk.md) menyebutnya eksplisit: *"agar tidak ada kejutan saat
   membayar"*. Yang ada sekarang hanyalah estimasi harga paket di form booking — jasa tambahan
   dan sparepart tidak pernah muncul di mana pun sebelum ditagihkan.
2. **Bengkel tidak punya angka pendapatan.** Tab Pendapatan di `/admin/laporan` sengaja **tidak
   dirender** sejak Tahap 8 karena sumbernya belum ada.
3. **Advisor tidak punya tempat mencatat pekerjaan tambahan.** Yang tersedia hanya `admin_note`
   berupa teks bebas — tidak bisa dijumlahkan, tidak bisa dicetak, tidak bisa diaudit.

Selain itu, tahap ini melunasi **tiga utang yang sudah tercatat** dan seluruhnya menunggu tabel
`invoices`:

| Utang | Ditinggalkan di | Bukti di kode |
|-------|-----------------|---------------|
| Total nilai invoice di detail customer (A6) | F1.5 penyimpangan #8 | `Admin\CustomerController::ringkasan()` — komentar "sengaja BELUM ada" |
| Laporan Pendapatan (A9) | F2.1 penyimpangan #2 | `Admin\ReportController` & `ReportService` — komentar "tidak ada di sini" |
| Placeholder `{{ringkasan_biaya}}` (A7) | F2.3 penyimpangan #5 | `config/whatsapp.php` & `WhatsAppPlaceholders` — komentar "SENGAJA belum ada" |

Cacat sistem lama yang paling relevan untuk dihindari di sini adalah **perhitungan total di sisi
klien** — dirujuk `.claude/rules/20` sebagai "(temuan invoice)" — dan **B2**, angka aturan bisnis
yang ditulis di lebih dari satu tempat.

## Tujuan

Setiap booking yang selesai menghasilkan satu invoice yang totalnya **dihitung server**, bisa
disunting selagi draft, diterbitkan bernomor urut, ditandai lunas, dan dibatalkan dengan jejak —
lalu terbaca oleh pelanggan sebagai halaman dan PDF, serta terjumlah di laporan pendapatan yang
hanya dilihat Super Admin.

## Latar

Sepuluh tahap sudah selesai; Invoice adalah **satu-satunya modul admin yang belum ada sama
sekali** ([10 §Status modul admin](../10-roadmap-implementasi.md#status-modul-admin)) dan
satu-satunya menu di `AdminLayout` yang belum dirender ([07 §A13](../07-modul-admin.md)).

Sebagian fondasinya sudah berdiri sejak lama dan sampai kini **tidak dipakai siapa pun**:

- `App\Enums\InvoiceStatus` — lahir di F1.0 (butir 0.4), lengkap dengan `label()`, `tone()`,
  `isEditable()`, dan `canTransitionTo()`.
- `resources/js/lib/status.ts` — sudah memuat `INVOICE_STATUS` dan `invoiceStatusMeta()`.
- `App\Support\ReportPeriod` — pemilih periode laporan yang akan dipakai ulang tab keempat.

Yang belum ada: tabelnya, model, service, policy, layar, PDF, dan seluruh ujinya.

Urutan ini disengaja. [10 §Sisanya diurutkan panel admin dulu](../10-roadmap-implementasi.md)
menyatakan invoice boleh menyusul karena **belum pernah ada di sistem lama**, sehingga tidak ada
yang menunggunya — berbeda dari panel harian advisor yang dipakai setiap hari.

## Selesai Bila

DoD 12 butir di [10 §Definition of Done](../10-roadmap-implementasi.md#definition-of-done) berlaku
otomatis dan tidak diulang di sini. Yang di bawah khusus fitur ini.

- [ ] Advisor menekan **Selesai** pada booking `in_progress` → invoice `draft` lahir dengan satu
      item jasa berisi nama paket dan harganya; terlihat di detail booking tanpa refresh manual.
- [ ] Menambah satu item part (qty 2 × Rp 75.000) lalu menyimpan → subtotal tersimpan Rp 150.000
      lebih besar; angka di layar sebelum simpan hanya pratinjau.
- [ ] POST `total` palsu (mis. `total=1`) ke rute simpan **tidak** mengubah total tersimpan.
- [ ] Diskon melebihi subtotal ditolak dengan pesan inline berbahasa Indonesia, bukan 500.
- [ ] Menerbitkan invoice pertama bulan ini menghasilkan nomor `INV/2026/08/0001`; invoice itu
      tidak bisa disunting lagi (form berubah jadi baca-saja **dan** rute PUT-nya menolak).
- [ ] Advisor menekan **Batalkan (void)** → 403. Super Admin dengan alasan → berhasil, dan
      barisnya muncul di `/admin/activity-log`.
- [ ] Sesudah void, tombol **Buat Invoice** muncul kembali di detail booking; invoice pengganti
      terbit dengan nomor **baru**, nomor lama tidak dipakai ulang.
- [ ] Booking berstatus `completed` yang lahir sebelum tahap ini menampilkan tombol
      **Buat Invoice**; booking berstatus lain tidak, dan rutenya menolak bila URL-nya diketik.
- [ ] Menandai **Lunas** dengan metode `transfer` mengisi `paid_at` dan `payment_method`;
      Super Admin masih bisa mem-void sesudahnya, advisor tidak.
- [ ] Pelanggan membuka detail booking: tautan invoice **belum ada** selagi draft (URL langsung →
      **404**), muncul setelah diterbitkan.
- [ ] Invoice yang pernah terbit lalu di-void **tetap terbuka** bagi pemiliknya dengan lencana
      "Dibatalkan" — bukan 404.
- [ ] Pelanggan lain yang menebak ID invoice mendapat 404, bukan isi invoice orang lain.
- [ ] **Unduh PDF berhasil dari Railway** (bukan hanya lokal) dan memuat kop surat teks, rincian
      item, diskon, pajak, total, serta nomor invoice.
- [ ] Tab **Pendapatan** tampil untuk Super Admin dan **tidak dirender** untuk advisor; advisor
      yang mengetik `?tab=pendapatan` tetap ditolak server.
- [ ] Tiga angka laporan konsisten: **diterbitkan − lunas = belum dibayar**, dan invoice `void`
      maupun `draft` tidak ikut terhitung.
- [ ] Detail customer di `/admin/customers/{id}` menampilkan total nilai invoice.
- [ ] `{{ringkasan_biaya}}` lolos validasi di penyunting template `/admin/template-wa` dan
      pratinjaunya memakai nilai contoh, bukan data pelanggan sungguhan.
- [ ] Menu **Operasional → Invoice** dirender untuk kedua role staf.
- [ ] Penyunting invoice terpakai penuh pada layar 360px — baris item menjadi kartu, tabel tidak
      menggeser badan halaman.

## Lingkup

1. Migration `invoices` dan `invoice_items`, model, factory, enum `InvoiceItemType`, dan
   `InvoiceService` sebagai satu-satunya tempat perhitungan (butir 2.4.1).
2. **A8** — daftar invoice, penyunting, dan empat aksi status: terbitkan, tandai lunas, batalkan
   (void), unduh PDF (butir 2.4.2).
3. Pembuatan invoice draft otomatis pada transisi `in_progress → completed`, plus tombol
   **Buat Invoice** sebagai jalur pemulihan.
4. PDF invoice (butir 2.4.3).
5. Halaman invoice untuk pelanggan + unduh PDF, dijangkau dari detail booking (butir 2.4.4).
6. Uji Pest untuk seluruh aturan di atas (butir 2.4.5).
7. Baris invoice di `AccessMatrixTest`, dan total nilai invoice di detail customer A6
   (butir 2.4.6).
8. Tab keempat **Pendapatan** di `/admin/laporan`, Super Admin saja (butir 2.4.7).
9. Placeholder `{{ringkasan_biaya}}` dihidupkan di ketiga tempatnya (butir 2.4.8).
10. Menu **Operasional → Invoice** mulai dirender di `AdminLayout`.

## Bukan Lingkup

Sembilan hal berikut **sengaja tidak** dikerjakan — dikunci di grill Q10.

| Tidak dikerjakan | Alasan |
|------------------|--------|
| **Pembayaran parsial / cicilan** | Skema hanya punya `paid_at` tunggal; cicilan menuntut tabel pembayaran tersendiri. Bengkel satu cabang menerima pembayaran sekaligus |
| **Master sparepart & stok** | Sudah Won't do di [02 §2.6](../02-kebutuhan-produk.md). Deskripsi item adalah teks bebas, tanpa autocomplete |
| **Tarif PPN otomatis dari config** | Bengkel kemungkinan besar bukan PKP; `docs/04 §4.2` menyebut PPN "opsional". Pajak diketik nominal |
| **Kirim invoice lewat email** | Tidak ada SMTP (keputusan final #4). Jalurnya: unduh PDF lalu kirim manual lewat WhatsApp |
| **Payment gateway / QRIS** | `payment_method` murni pencatatan ([04 §4.2](../04-skema-database.md)) |
| **Nota kredit / retur** | Koreksi memakai `void` + buat ulang |
| **Invoice untuk booking yang belum `completed`** | Rutenya menolak status lain di server |
| **Menu invoice tersendiri untuk pelanggan** | Riwayat servis sudah ada; menu kedua akan menampilkan dua daftar dari satu kenyataan |
| **Format penomoran yang bisa dikonfigurasi** | Sudah ditetapkan [05 §5.6](../05-alur-bisnis.md) |

Juga di luar lingkup: pengisian mundur (backfill) invoice untuk booking `completed` lama, dan
`DemoBookingSeeder` yang disebut [04 §4.4](../04-skema-database.md) tetapi belum pernah dibuat.

## Pengguna & Kebutuhannya

Menyentuh **ketiga** peran. Pengunjung publik tidak menyentuhnya sama sekali — invoice tidak pernah
tampil tanpa login.

**Service Advisor** (US-A5)

- Sebagai advisor, saya ingin invoice draft **sudah ada** begitu saya menandai pekerjaan selesai,
  supaya saya tidak perlu mengingat langkah tambahan di tengah antrean.
- Sebagai advisor, saya ingin menambah jasa dan sparepart beserta qty dan harganya, supaya tagihan
  mencerminkan pekerjaan yang benar-benar dikerjakan.
- Sebagai advisor, saya ingin menerbitkan invoice dan langsung mengabari pelanggan lewat WhatsApp
  beserta totalnya, supaya tidak ada perdebatan angka di kasir.
- Sebagai advisor, saya ingin mencatat pembayaran beserta metodenya, supaya rekan shift berikutnya
  tahu tagihan mana yang sudah lunas.

**Super Admin** (US-S1)

- Sebagai Super Admin, saya ingin membatalkan invoice yang salah beserta alasannya, supaya koreksi
  tidak menuntut membuka database.
- Sebagai Super Admin, saya ingin melihat pendapatan per periode beserta piutangnya, supaya saya
  tahu berapa yang sudah ditagih dan berapa yang belum masuk.

**Customer** (US-C7)

- Sebagai pelanggan, saya ingin melihat rincian jasa dan sparepart beserta totalnya, supaya tidak
  ada kejutan saat membayar.
- Sebagai pelanggan, saya ingin mengunduh PDF-nya, supaya saya punya bukti untuk klaim garansi atau
  penggantian biaya kantor.
- Sebagai pelanggan, saya ingin tahu bila tagihan saya dibatalkan, supaya saya tidak membayar
  sesuatu yang sudah tidak berlaku.

## Kebutuhan Fungsional

### Data

**F1.** Tabel `invoices` sesuai [04 §4.2](../04-skema-database.md), dengan **satu perubahan**:
`booking_id` memakai **index biasa, bukan `unique`** (Keputusan 1). Kolom: `booking_id`,
`invoice_number` (unique, nullable sampai terbit), `subtotal`, `discount`, `tax`, `total`
(`decimal(12,2)`), `status` (`varchar(15)`), `issued_at`, `paid_at`, `payment_method`
(`varchar(30)` nullable), `void_reason` (`varchar(255)` nullable), `notes`, `created_by`,
`timestamps`.

**F2.** Kolom `void_reason` **ditambahkan** ke skema — [07 §A8](../07-modul-admin.md) mewajibkan
alasan saat void, tetapi [04 §4.2](../04-skema-database.md) tidak menyediakan kolomnya. Menaruhnya
di `notes` akan menimpa catatan advisor.

**F3.** Tabel `invoice_items` sesuai [04 §4.2](../04-skema-database.md): `invoice_id` (cascade),
`type` (`jasa`|`part`), `description` (`varchar(200)`), `qty` (`decimal(8,2)`), `unit_price`,
`subtotal` (`decimal(12,2)`), `sort_order`, `timestamps`.

**F4.** Index wajib: `invoices(booking_id)`, `invoices(status, issued_at)` untuk laporan
pendapatan, `invoices(invoice_number)` unik, `invoice_items(invoice_id, sort_order)`.

**F5.** `Invoice` dan `InvoiceItem` **tidak** memakai soft delete — invoice tidak pernah dihapus
([04 §4.5](../04-skema-database.md)). `Invoice` memakai trait `RecordsActivity`.

**F6.** `Booking` memperoleh dua relasi: `invoice()` (`HasOne` yang menyaring `status != void` —
invoice **aktif**) dan `invoices()` (`HasMany`, seluruh riwayat termasuk void).

### Aturan Bisnis

**F7.** Perhitungan **hanya** di `InvoiceService`:
`item.subtotal = qty × unit_price` → `invoice.subtotal = Σ item.subtotal` →
`invoice.total = subtotal − discount + tax`. Nilai `subtotal` dan `total` yang datang dari request
**diabaikan sepenuhnya**, tidak divalidasi lalu dipakai.

**F8.** Satu booking boleh punya **maksimal satu invoice yang belum di-void**. Diperiksa di dalam
`DB::transaction` dengan baris **booking**-nya dikunci `lockForUpdate` — bukan dibaca lalu ditulis.

**F9.** Invoice hanya boleh lahir dari booking berstatus `completed`. Status lain ditolak di server
dengan pesan yang menjelaskan, bukan 403 telanjang.

**F10.** Transisi `in_progress → completed` di `BookingService::changeStatus()` memanggil
`InvoiceService` **di dalam transaksi yang sama**. Invoice yang gagal terbentuk membatalkan
perubahan statusnya.

**F11.** Draft otomatis berisi **satu** item bertipe `jasa`: deskripsi = nama paket layanan,
`qty = 1`, `unit_price = service_packages.price`. Paket ber-`is_free` tetap menghasilkan invoice
dengan `unit_price = 0` (Keputusan 4).

**F12.** Aturan validasi penyunting:

| Aturan | Penolakan |
|--------|-----------|
| `qty > 0`, maksimal 2 desimal | "Jumlah harus lebih dari 0" |
| `unit_price ≥ 0` | — |
| `description` wajib, maks 200 karakter | — |
| `type ∈ {jasa, part}` | — |
| `discount ≥ 0` **dan** `discount ≤ subtotal` (subtotal hasil hitungan server) | "Diskon tidak boleh melebihi subtotal Rp …" |
| `tax ≥ 0` | — |
| Minimal 1 item saat **menerbitkan** | "Invoice tanpa item tidak bisa diterbitkan" |

**F13.** Invoice `draft` bisa disunting; `issued`, `paid`, dan `void` **tidak** — ditolak di rute,
bukan hanya disembunyikan di layar.

**F14.** Nomor invoice `INV/{YYYY}/{MM}/{NNNN}` terbit **saat status berubah menjadi `issued`**,
bukan saat draft dibuat — draft yang tidak pernah diterbitkan tidak boleh menghabiskan nomor.
Urutan dihitung ulang per bulan, di dalam transaksi; tabrakan unique index membuat transaksinya
**diulang**, mengikuti pola generator kode booking di F1.4.

**F15.** Transisi status invoice mengikuti `InvoiceStatus::canTransitionTo()`, dengan **satu
tambahan**: `paid → void` dibuka untuk Super Admin (Keputusan 2). Transisi tidak sah menghasilkan
**422**, bukan 403 — polanya sama dengan `InvalidStatusTransitionException` di F1.5.

**F16.** Void menyimpan `void_reason` dan **tidak** menghapus `paid_at` maupun `payment_method` —
keduanya jejak bahwa uangnya pernah tercatat masuk.

**F17.** Laporan Pendapatan dikelompokkan menurut **`issued_at`**, menghasilkan tiga angka:
**diterbitkan** (Σ `total` berstatus `issued` atau `paid`), **lunas** (bagian yang `paid`), dan
**belum dibayar** (selisihnya). `draft` dan `void` dikecualikan. Pecahan per paket layanan dan per
metode pembayaran mengikuti bentuk tiga tab yang sudah ada.

**F18.** `{{ringkasan_biaya}}` merender satu baris — `Total biaya: Rp 450.000 (3 item)` — dan
berubah menjadi `Rincian biaya menyusul` bila invoice belum diterbitkan. Bukan angka draft.

### Hak Akses

**F19.** `InvoicePolicy` baru. Barisnya mengikuti [09 §9.3](../09-keamanan-hak-akses.md) tanpa
tambahan:

| Kemampuan | Super Admin | Advisor | Customer | Tamu |
|-----------|:-----------:|:-------:|:--------:|:----:|
| Melihat daftar & detail invoice (panel) | ✅ | ✅ | ❌ | ❌ |
| Membuat & menyunting draft | ✅ | ✅ | ❌ | ❌ |
| Menerbitkan | ✅ | ✅ | ❌ | ❌ |
| Menandai lunas | ✅ | ✅ | ❌ | ❌ |
| Membatalkan (void) | ✅ | ❌ | ❌ | ❌ |
| Melihat invoice **milik sendiri** | — | — | ✅ | ❌ |
| Laporan pendapatan | ✅ | ❌ | ❌ | ❌ |

**F20.** Invoice pelanggan diambil **lewat relasi** (`$request->user()` → `bookings` → `invoices`),
bukan `Invoice::findOrFail()` lalu diperiksa belakangan — `.claude/rules/50` #2.

**F21.** Yang terlihat pelanggan dipatok **`issued_at !== null`**, bukan status (Keputusan 7).
Draft → **404**, bukan 403: 403 membocorkan bahwa ada sesuatu di sana. Void yang pernah terbit
tetap terbuka dengan lencana "Dibatalkan".

**F22.** Kemampuan baru `viewRevenueReport` — Super Admin saja. Tab-nya **tidak dirender** untuk
advisor, dan `?tab=pendapatan` tetap ditolak server.

**F23.** Baris invoice ditambahkan ke `tests/Feature/Admin/AccessMatrixTest.php` (butir 2.4.6):
satu uji per baris tabel F19, termasuk tamu dan customer yang ditolak di seluruh rute `/admin/invoices`.

### Antarmuka

**F24.** `/admin/invoices` — daftar berisi nomor, tanggal terbit, customer, kode booking, total,
status; saringan status + pencarian (nomor invoice, kode booking, nama customer) **di server**;
paginasi 25; kartu di `< md`.

**F25.** `/admin/invoices/{invoice}` — satu halaman yang menjadi penyunting saat `draft` dan
baca-saja setelahnya. Baris item bisa ditambah, dihapus, dan diurutkan. Subtotal per baris dan
total di layar adalah **pratinjau**; sumber kebenarannya server.

**F26.** Detail booking (`/admin/bookings/{booking}`) memperoleh panel invoice: ringkasan invoice
aktif + tautan, atau tombol **Buat Invoice** bila booking `completed` dan tidak punya invoice aktif.
Riwayat invoice yang sudah di-void ikut terlihat di sana.

**F27.** Aksi merusak memakai `ConfirmDialog` dengan teks tombol yang menyebut aksinya
("Terbitkan Invoice", "Batalkan Invoice") — bukan "OK". Void menuntut alasan diisi sebelum tombolnya
aktif, dan tetap divalidasi server.

**F28.** Detail booking **pelanggan** (`resources/js/pages/booking/show.tsx`) memperoleh tautan ke
invoice, muncul hanya bila `issued_at` terisi.

**F29.** `/invoice/{invoice}` — halaman pelanggan: kop bengkel, rincian item, subtotal, diskon,
pajak, total, status, tombol unduh PDF.

**F30.** PDF disusun sebagai **Blade view**, bukan React — dompdf tidak menjalankan JavaScript.
Pengecualian sah terhadap `.claude/rules/20`, pola yang sama seperti JSON-LD di F1.6. **Tanpa
gambar raster**: kop surat berupa teks dari `config/company.php` (Keputusan 6).

**F31.** Menu **Operasional → Invoice** dirender di `AdminLayout` untuk kedua role staf; catatan
"yang masih belum dirender tinggal Invoice" di [07 §A13](../07-modul-admin.md) dicabut.

**F32.** Lencana status memakai `INVOICE_STATUS` dan `invoiceStatusMeta()` yang **sudah ada** di
`resources/js/lib/status.ts` — tidak ada peta warna kedua.

## Kebutuhan Non-Fungsional

**Keamanan**

- `InvoicePolicy` untuk setiap aksi; `Gate::authorize` di controller, tanpa kecuali
  (`.claude/rules/50` #1–2).
- Seluruh masukan lewat Form Request. Tidak ada `$request->all()`, tidak ada `$request->validate()`
  di controller.
- Field yang **tidak pernah** diambil dari request: `status`, `invoice_number`, `subtotal`,
  `total`, `issued_at`, `paid_at`, `created_by`, `booking_id` (pada rute sunting).
- Halaman publik tidak menampilkan invoice dalam bentuk apa pun. Pelacakan publik `/cek-service`
  tidak berubah — tidak ada biaya di sana.
- PDF pelanggan tunduk pada policy yang sama seperti halamannya; tidak ada URL bertanda tangan
  yang bisa diteruskan.
- Rate limit unduhan PDF **10/menit**, sejajar dengan `admin.reports.export` yang sudah ada.

**Waktu & uang**

- Seluruh uang `decimal(12,2)`; `qty` `decimal(8,2)`. Dilarang float.
- `issued_at`, `paid_at` memakai `Carbon` zona `Asia/Jakarta` lewat helper yang sudah dipakai
  `SlotService` — dilarang `date()`/`strtotime()` telanjang.
- Nomor invoice memakai bulan **Asia/Jakarta**, bukan UTC. Invoice yang terbit pukul 00.30 WIB
  tanggal 1 September tidak boleh masuk urutan Agustus (cacat B7 sistem lama).

**Konfigurasi**

- Tidak ada angka aturan bisnis baru. Format nomor invoice hidup di satu kelas
  (`App\Support\InvoiceNumber`), bukan tersebar di service dan uji.
- Periode laporan tetap dari `config('booking.reports')` lewat `App\Support\ReportPeriod` yang
  sudah ada — tidak ada definisi "30 hari" kedua.

**Antarmuka**

- Bahasa Indonesia, sapaan "Anda". Istilah: **Invoice**, **Servis**, **Kendaraan**,
  **Paket Layanan**.
- Warna hanya dari design token; status hanya dari `lib/status.ts`.
- Benar pada 360 / 768 / 1280. Tabel item dibungkus `overflow-x-auto` dan menjadi kartu di `< md`.
- Uang diformat `formatRupiah()`, tanggal `formatTanggal()` dari `lib/format.ts` — tanpa
  pemformatan ad-hoc di halaman.

**Kinerja**

- Daftar invoice dipaginasi 25 di server; `with(['booking.user', 'booking.servicePackage'])` untuk
  mencegah N+1.
- Laporan pendapatan diagregasi **di database** (`selectRaw` dengan binding), bukan `get()` lalu
  dijumlahkan di PHP.
- Penyunting memuat item lewat satu relasi `with('items')`, bukan satu kueri per baris.

## Keputusan yang Sudah Disetujui

**Terkunci — jangan dibuka ulang.** Sumber: [`grill-invoice.md`](../grills/grill-invoice.md),
5 Agustus 2026.

1. **`invoices.booking_id` bukan `unique`.** Diganti index biasa; aturan "maksimal satu invoice
   non-void per booking" ditegakkan aplikasi dalam transaksi terkunci. *Menyimpang dari
   [04 §4.2](../04-skema-database.md) — lihat "Dampak ke Rancangan".* (grill Q1)
2. **`paid → void` dibuka, Super Admin saja, wajib alasan.** `paid_at` dan `payment_method` tetap
   disimpan. *Menyimpang dari `InvoiceStatus` yang lahir di F1.0.* (grill Q2)
3. **Invoice draft lahir di dalam transaksi `completed` yang sama**; tanpa backfill; tombol
   **Buat Invoice** menjadi jalur pemulihan; invoice hanya untuk booking `completed`. (grill Q3)
4. **Paket gratis tetap dibuatkan invoice Rp 0.** Label "Gratis" tetap milik
   `service_packages.is_free`, bukan diturunkan dari `total = 0`. (grill Q4)
5. **Diskon dan pajak nominal, diketik tangan, bawaan 0.** Tanpa tarif PPN di config;
   `discount ≤ subtotal`; minimal 1 item saat terbit. (grill Q5)
6. **PDF memakai dompdf tanpa gambar raster**; kop surat teks dari `config/company.php`. Jalur
   cadangan halaman cetak (`@media print`) disepakati **di muka**, seperti CSV di F2.1. (grill Q6)
7. **Yang terlihat pelanggan dipatok `issued_at`, bukan status.** Draft → 404. Void yang pernah
   terbit tetap terlihat berlencana "Dibatalkan". `InvoiceStatus::isVisibleToCustomer()` dicabut
   karena enum tidak bisa membedakan void-yang-pernah-terbit. (grill Q7)
8. **`{{ringkasan_biaya}}` merender satu baris total + jumlah item**; belum terbit → "Rincian biaya
   menyusul". Tiga tempat diubah bersamaan. (grill Q8)
9. **Laporan Pendapatan dikelompokkan `issued_at`, tiga angka** (diterbitkan · lunas · belum
   dibayar); `viewRevenueReport` Super Admin saja. (grill Q9)
10. **Sembilan hal dikunci Won't do** — lihat "Bukan Lingkup". (grill Q10)

Berlaku juga tanpa perlu diputuskan ulang, karena sudah ditetapkan rancangan: status invoice
(`docs/04 §4.3`), rumus total dan format nomor (`docs/05 §5.6`), matriks hak akses
(`docs/09 §9.3`), invoice tidak pernah dihapus (`docs/04 §4.5`), retensi permanen
(`docs/09 §9.7`), dan letak menu (`docs/07 §A13`).

## Pertanyaan Terbuka

1. **Apakah `ext-gd` tersedia di runtime Railway?** Belum diverifikasi. Tidak menghalangi
   `composer install` — `gd` hanya *suggest* pada dompdf — tetapi menentukan apakah gambar raster
   akan pernah bisa dipakai di PDF. Rancangan sudah tidak bergantung padanya (Keputusan 6);
   jawabannya hanya menentukan apakah logo bisa ditambahkan kelak. **Dibuktikan sekali saat 2.4.3
   di-deploy**, bukan sebelum mulai.
2. **Apakah bengkel berstatus PKP?** Bila ya, tarif PPN 11% perlu dihitung otomatis dan Keputusan 5
   ditinjau ulang. Sampai terjawab, kolom pajak diketik tangan dan bawaannya 0 — bentuk yang tetap
   benar untuk kedua jawaban. **Ditanyakan saat serah terima (2.5.9)**, tidak memblokir tahap ini.

## Dampak ke Rancangan `docs/`

**Ya — PRD ini menyimpang dari `docs/` di tiga titik**, seluruhnya diputuskan sadar saat grill dan
karena itu **dokumennya yang diperbarui**, bukan diselundupkan ke dalam kode.

| Dokumen / berkas | Perubahan | Asal |
|------------------|-----------|------|
| `docs/04 §4.2` `invoices` | `booking_id` **bukan `unique`** lagi; ditambah aturan aplikasi "maksimal satu invoice non-void per booking"; ditambah kolom `void_reason` | Keputusan 1, F2 |
| `docs/04 §4.3` | `InvoiceStatus` ditambah transisi `paid → void` | Keputusan 2 |
| `docs/04 §4.5` | Baris Invoice diperjelas: `void` boleh menumpuk, penggantinya invoice **baru** bernomor baru | Keputusan 1 |
| `docs/05 §5.3` | Baris `in_progress → completed`: invoice draft dibuat **dalam transaksi yang sama** | Keputusan 3 |
| `docs/05 §5.6` | Jalur koreksi ditulis utuh (void → buat ulang), termasuk dari `paid`; ditambah aturan paket gratis dan `discount ≤ subtotal` | Keputusan 1, 2, 4, 5 |
| `docs/03` daftar rute | `resource /invoices` disesuaikan — **tanpa** `create`/`store` berdiri sendiri; ditambah rute buat-dari-booking, terbitkan, lunas, void, pdf | Keputusan 3 |
| `docs/07 §A8` | Ditambah tombol "Buat Invoice", aturan paket gratis, `discount ≤ subtotal`, minimal 1 item saat terbit | Keputusan 3, 4, 5 |
| `docs/07 §A9` | Baris Pendapatan diperjelas: dikelompokkan `issued_at`, **tiga** angka | Keputusan 9 |
| `docs/07 §A13` | Catatan "yang masih belum dirender tinggal Invoice" dicabut | F31 |
| `docs/09 §9.3` | Dipastikan konsisten dengan kemampuan `viewRevenueReport`; baris invoice tidak berubah isinya | Keputusan 9 |
| `docs/10` Tahap 11 | Catatan penyimpangan ditulis **setelah** tahap selesai, mengikuti format tahap sebelumnya | semua |

## Modul yang Tersentuh

| Berkas | Baru / Ubah | Yang berubah |
|--------|-------------|--------------|
| `database/migrations/2026_08_05_*_create_invoices_table.php` | **Baru** | Tabel `invoices` + index `(status, issued_at)`, `(booking_id)`, unique `invoice_number` |
| `database/migrations/2026_08_05_*_create_invoice_items_table.php` | **Baru** | Tabel `invoice_items` + index `(invoice_id, sort_order)` |
| `app/Enums/InvoiceStatus.php` | Ubah | `Paid => [Void]`; `isVisibleToCustomer()` dicabut |
| `app/Enums/InvoiceItemType.php` | **Baru** | `jasa` \| `part` + `label()` |
| `app/Models/Invoice.php` | **Baru** | `$fillable`, casts `decimal:2` + enum + timestamp, relasi, `RecordsActivity`, scope `active()` |
| `app/Models/InvoiceItem.php` | **Baru** | `$fillable`, casts, relasi `belongsTo` |
| `app/Models/Booking.php` | Ubah | Relasi `invoice()` (aktif) dan `invoices()` (riwayat) |
| `app/Models/User.php` | Ubah | Relasi `invoices()` `HasManyThrough` lewat `bookings` |
| `app/Services/InvoiceService.php` | **Baru** | `createDraftFor`, `updateDraft`, `issue`, `markPaid`, `void` — seluruh perhitungan & penguncian |
| `app/Services/BookingService.php` | Ubah | `changeStatus()` memanggil `InvoiceService::createDraftFor()` di dalam transaksinya |
| `app/Services/ReportService.php` | Ubah | `revenue(ReportPeriod)`; komentar "Pendapatan tidak ada di sini" dicabut |
| `app/Support/InvoiceNumber.php` | **Baru** | Format `INV/{YYYY}/{MM}/{NNNN}` — satu-satunya tempat |
| `app/Support/InvoicePresenter.php` | **Baru** | Bentuk props staf vs bentuk pelanggan, pola `BookingPresenter` (F1.4 #6) |
| `app/Support/WhatsAppPlaceholders.php` | Ubah | `{{ringkasan_biaya}}` di `forBooking()` **dan** `sample()` |
| `app/Exceptions/InvalidInvoiceTransitionException.php` | **Baru** | 422, pola `InvalidStatusTransitionException` |
| `app/Exceptions/InvoiceUnavailableException.php` | **Baru** | Booking belum `completed` / sudah punya invoice aktif → galat inline |
| `bootstrap/app.php` | Ubah | Dua handler baru, pola yang sudah ada |
| `app/Policies/InvoicePolicy.php` | **Baru** | `viewAny`, `view`, `update`, `issue`, `markPaid`, `void` (SA), `download` |
| `app/Policies/BookingPolicy.php` | Ubah | `viewRevenueReport` (SA); komentar "laporan pendapatan belum ada" dicabut |
| `app/Http/Requests/Admin/InvoiceFilterRequest.php` | **Baru** | Status, pencarian, paginasi |
| `app/Http/Requests/Admin/UpdateInvoiceRequest.php` | **Baru** | Item, diskon, pajak, catatan — F12 |
| `app/Http/Requests/Admin/MarkInvoicePaidRequest.php` | **Baru** | `payment_method ∈ {cash, transfer, edc}` |
| `app/Http/Requests/Admin/VoidInvoiceRequest.php` | **Baru** | Alasan wajib, maks 255 |
| `app/Http/Requests/Admin/ReportFilterRequest.php` | Ubah | Tab `pendapatan` diterima |
| `app/Http/Controllers/Admin/InvoiceController.php` | **Baru** | `index`, `show`, `update`, `store` (dari booking) |
| `app/Http/Controllers/Admin/InvoiceStatusController.php` | **Baru** | `issue`, `markPaid`, `void` |
| `app/Http/Controllers/Admin/InvoicePdfController.php` | **Baru** | Unduh PDF (staf) |
| `app/Http/Controllers/Admin/ReportController.php` | Ubah | Prop `revenue`, dijaga `viewRevenueReport`; komentar penundaan dicabut |
| `app/Http/Controllers/Admin/BookingController.php` | Ubah | `show()` mengirim props invoice aktif + riwayat + `canCreateInvoice` |
| `app/Http/Controllers/Admin/CustomerController.php` | Ubah | `ringkasan()` mengisi total nilai invoice; komentar penundaan dicabut |
| `app/Http/Controllers/Customer/InvoiceController.php` | **Baru** | `show`, `download` — diambil lewat relasi |
| `app/Http/Controllers/Customer/BookingController.php` | Ubah | `show()` mengirim tautan invoice bila `issued_at` terisi |
| `routes/web.php` | Ubah | Grup `/admin/invoices` + `POST /admin/bookings/{booking}/invoice` + dua rute customer `/invoice/{invoice}` (throttle 10/menit pada PDF) |
| `config/whatsapp.php` | Ubah | `ringkasan_biaya` masuk `allowed_placeholders`; komentar "sengaja belum ada" dicabut |
| `database/seeders/WhatsAppTemplateSeeder.php` | Ubah | Teks awal `booking_completed` memuat `{{ringkasan_biaya}}` — **hanya berlaku untuk instalasi baru** |
| `database/factories/InvoiceFactory.php` | **Baru** | State `draft`, `issued`, `paid`, `void` |
| `database/factories/InvoiceItemFactory.php` | **Baru** | State `jasa`, `part` |
| `resources/views/pdf/invoice.blade.php` | **Baru** | Tata letak PDF, kop surat teks, tanpa gambar raster |
| `resources/js/pages/admin/invoices/index.tsx` | **Baru** | Daftar + saringan + paginasi |
| `resources/js/pages/admin/invoices/show.tsx` | **Baru** | Penyunting (draft) / baca-saja + panel aksi status |
| `resources/js/pages/invoice/show.tsx` | **Baru** | Halaman invoice pelanggan |
| `resources/js/components/admin/invoice-item-rows.tsx` | **Baru** | Baris item: tambah, hapus, urutkan; kartu di `< md` |
| `resources/js/components/invoice-summary.tsx` | **Baru** | Ringkasan subtotal/diskon/pajak/total — dipakai kedua sisi |
| `resources/js/components/admin/invoice-panel.tsx` | **Baru** | Panel invoice di detail booking + tombol Buat Invoice |
| `resources/js/pages/admin/bookings/show.tsx` | Ubah | Memasang `invoice-panel` |
| `resources/js/pages/admin/customers/show.tsx` | Ubah | Total nilai invoice |
| `resources/js/pages/admin/laporan/index.tsx` | Ubah | Tab keempat, dirender hanya bila prop-nya ada |
| `resources/js/pages/booking/show.tsx` | Ubah | Tautan invoice untuk pelanggan |
| `resources/js/layouts/admin-layout.tsx` | Ubah | Menu Operasional → Invoice |
| `resources/js/types/index.ts` | Ubah | Tipe `Invoice`, `InvoiceItem`, `InvoiceFilters`, `RevenueReport` |
| `tests/Feature/Invoice/CreateInvoiceTest.php` | **Baru** | Draft lahir saat `completed`; paket gratis; booking non-`completed` ditolak; tombol Buat Invoice |
| `tests/Feature/Invoice/EditInvoiceTest.php` | **Baru** | Total dihitung server; `total` palsu diabaikan; diskon > subtotal ditolak; `issued` tidak bisa disunting |
| `tests/Feature/Invoice/InvoiceStatusTest.php` | **Baru** | Terbitkan/lunas/void; advisor ditolak void; `paid → void` SA berhasil; transisi tidak sah 422; nomor urut per bulan; satu invoice aktif per booking |
| `tests/Feature/Invoice/CustomerInvoiceTest.php` | **Baru** | Draft 404; void-pernah-terbit terlihat; invoice orang lain 404 |
| `tests/Feature/Invoice/InvoicePdfTest.php` | **Baru** | PDF terunduh, `Content-Type` benar, throttle bekerja |
| `tests/Feature/Admin/ReportTest.php` | Ubah | Tab pendapatan: tiga angka konsisten, advisor ditolak, `void`/`draft` dikecualikan |
| `tests/Feature/Admin/AccessMatrixTest.php` | Ubah | Baris invoice (butir 2.4.6) |
| `tests/Feature/Admin/CustomerDirectoryTest.php` | Ubah | Total nilai invoice di detail customer |
| `tests/Unit/InvoiceStatusTest.php` | **Baru** | Seluruh transisi enum, termasuk `paid → void` |
| `tests/Unit/WhatsAppPlaceholderTest.php` | Ubah | `ringkasan_biaya` di ketiga tempat; nilai saat invoice belum terbit |

## Risiko

⚠️ **Aturan "satu invoice aktif per booking" hanya hidup di aplikasi** → Setelah Keputusan 1
mencabut kolom unik, tidak ada lagi jaring pengaman database; dua permintaan bersamaan bisa
menyisipkan dua draft. → Dihitung di dalam transaksi dengan `lockForUpdate` pada baris
**booking**-nya — baris invoice yang belum ada tidak bisa dikunci. Diuji dengan tiga permintaan
beruntun, pola yang sama dengan kuota slot F1.4. **Catatan jujur yang ikut ke roadmap:**
`lockForUpdate` hanya berlaku nyata di MySQL; SQLite yang dipakai uji mengabaikannya, jadi yang
terbukti otomatis adalah **keatomikannya**.

⚠️ **Nomor invoice per bulan bisa kembar** → Baris yang belum ada tidak bisa dikunci, persis
seperti kode booking di F1.4. → Unique index pada `invoice_number` + **transaksi diulang** bila yang
kalah menabraknya.

⚠️ **Total tergoda dihitung di frontend** → Penyunting menampilkan subtotal yang berubah saat
advisor mengetik qty; menyalin rumusnya ke React adalah cacat "menghitung total harga di frontend"
(`.claude/rules/20`, temuan invoice) **dan** B2 sekaligus. → Angka di layar hanya pratinjau;
ujinya menembak rute dengan `total` palsu di payload untuk membuktikan server mengabaikannya.

⚠️ **`ext-gd` mungkin tidak ada di runtime Railway** → dompdf memerlukannya untuk JPEG/PNG. Karena
`gd` hanya *suggest*, `composer install` tetap lulus dan kegagalannya baru muncul saat PDF pertama
dirender **di produksi**, bukan saat deploy. → PDF dirancang tanpa gambar raster; dibuktikan sekali
di Railway sebelum 2.4.3 dinyatakan selesai. Jalur cadangan halaman cetak sudah disepakati.

⚠️ **`{{ringkasan_biaya}}` tidak akan muncul di database yang sudah berjalan** →
`WhatsAppTemplateSeeder` sengaja **tidak pernah** menimpa baris yang sudah ada — satu-satunya
seeder produksi yang bukan `updateOrCreate`, dibuat begitu di Tahap 10 supaya suntingan Super Admin
tidak hilang. → Satu suntingan manual lewat layar A7 setelah deploy, **ditulis sebagai butir serah
terima**, bukan diasumsikan orang akan menyadarinya sendiri.

⚠️ **`migrate:fresh` menghapus invoice** → **R2** masih berlaku sampai 2.5.1 memisahkan database
produksi. Invoice adalah data pertama di proyek ini yang punya konsekuensi akuntansi. → Selama
isinya masih seeder, tidak apa-apa; begitu ada invoice sungguhan pertama,
[batas pemakaian `migrate:fresh`](../10-roadmap-implementasi.md#kapan-migratefresh-berhenti-boleh)
sudah terlewati dan 2.5.1 tidak boleh mundur lagi.

⚠️ **Advisor melihat total tiap invoice padahal laporan Pendapatan SA-saja** → Pembatasan di
[09 §9.3](../09-keamanan-hak-akses.md) itu soal kemudahan agregat, bukan kerahasiaan angka; advisor
yang menerbitkan invoice memang harus melihat totalnya. → Diterima sadar dan dicatat di sini,
supaya `/audit-paritas` di 2.5.10 tidak membacanya sebagai kebocoran.

⚠️ **`BookingService` sekarang bergantung pada `InvoiceService`** → Kegagalan di pembuatan invoice
menjatuhkan transisi status yang seharusnya berhasil, dan uji F1.5 yang sudah ada bisa ikut merah.
→ Ketergantungannya satu arah dan disuntik lewat konstruktor; uji `BookingStatusTest` yang ada
diperiksa ulang sebagai bagian dari 2.4.5, bukan setelah semuanya jadi.

## Ukuran Keberhasilan

1. **Setiap booking `completed` punya tepat satu invoice aktif.** Diperiksa satu kueri: booking
   `completed` tanpa invoice non-void harus **nol**. Bila tidak nol setelah dua minggu, pembuatan
   otomatisnya bocor di suatu tempat — bukan advisornya yang lalai.
2. **Nol invoice yang totalnya diperbaiki lewat database.** Bila ada satu saja yang harus disentuh
   DBeaver, berarti jalur koreksi (void → buat ulang) tidak cukup atau tidak ditemukan advisor.
3. **Angka "belum dibayar" di laporan turun ke nol setiap akhir bulan** — atau kalau tidak,
   selisihnya bisa dijelaskan per invoice. Angka piutang yang tidak pernah bergerak berarti
   "Tandai Lunas" tidak dipakai, dan laporannya berbohong.
4. **Pelanggan mengunduh PDF-nya sendiri.** Bila advisor tetap memfoto layar dan mengirimnya lewat
   WhatsApp, tautan invoicenya tidak cukup terlihat di halaman pelanggan.
5. **Nol keluhan "totalnya beda dengan yang di WhatsApp".** Itu ukuran langsung bahwa
   `{{ringkasan_biaya}}` hanya pernah mengirim angka yang sudah diterbitkan.
