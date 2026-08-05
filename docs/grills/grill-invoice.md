# Grill — F2.4 Invoice (A8)

**Status:** ✅ selesai · dibuka dan ditutup 5 Agustus 2026
**Tahap:** 11 (`F2.4`) — lihat [roadmap](../10-roadmap-implementasi.md#tahap-11--invoice--f24)
**Keputusan:** **10 dari 10 terjawab.** Seluruhnya diputuskan mengikuti rekomendasi atas
permintaan pemilik proyek (5 Agustus 2026). Tidak ada rekomendasi yang ditolak.
**Berikutnya:** → [`docs/prds/prd-invoice.md`](../prds/prd-invoice.md) — **sudah ditulis dan
sudah diimplementasikan**; Tahap 11 selesai 5 Agustus 2026, catatan penyimpangannya di
[roadmap](../10-roadmap-implementasi.md#tahap-11--invoice--f24--selesai).

Sumber yang sudah dibaca: `docs/04-skema-database.md §4.2` (`invoices`, `invoice_items`,
`activity_log`), `§4.3` (`InvoiceStatus`), `§4.5` · `docs/05-alur-bisnis.md §5.3, §5.5, §5.6` ·
`docs/07-modul-admin.md §A6, §A8, §A9, §A13, §A14` · `docs/09-keamanan-hak-akses.md §9.3, §9.4,
§9.7` · `docs/03-arsitektur-teknis.md` (daftar rute, `InvoiceService`, dompdf) ·
`docs/02-kebutuhan-produk.md` (US-C7, US-A5) · `docs/10-roadmap-implementasi.md` (butir
2.4.1–2.4.8, DoD, tabel risiko) · `.claude/rules/10,20,30,40,50,60` · kode:
`App\Enums\InvoiceStatus`, `App\Services\BookingService::changeStatus`, `ReportService`,
`Admin\ReportController`, `Admin\CustomerController::ringkasan`, `BookingPolicy::viewReports`,
`App\Support\WhatsAppPlaceholders`, `config/whatsapp.php`, `WhatsAppTemplateSeeder`,
`routes/web.php`, `layouts/admin-layout.tsx`, `nixpacks.toml`, `composer.json`, migration
`create_service_packages_table` & `create_bookings_table`.

---

## Keputusan implisit — tidak ditanyakan

Sudah ditetapkan rancangan, aturan proyek, atau kode yang berjalan. Dicatat supaya tidak dibuka ulang.

| Hal | Ketetapan | Sumber |
|-----|-----------|--------|
| Dua tabel baru | `invoices`, `invoice_items` — kolomnya sudah ditetapkan | `docs/04 §4.2` |
| Status invoice | `draft` · `issued` · `paid` · `void` — enum `App\Enums\InvoiceStatus` **sudah ada sejak F1.0** | `docs/04 §4.3`, kode |
| Perhitungan | `subtotal = Σ(qty × unit_price)`, `total = subtotal − discount + tax` — **selalu di server**, tidak pernah dikirim browser | `docs/05 §5.6`, `.claude/rules/50` |
| Tipe uang | `decimal(12,2)`; `qty` `decimal(8,2)` agar 0,5 jam jasa bisa | `docs/04 §4.2`, `.claude/rules/30` |
| `issued` tidak bisa disunting | Sudah tertulis di enum (`isEditable()`) dan di tiga dokumen | `docs/05 §5.6`, `docs/07 §A8` |
| Void = SA saja, wajib alasan | Baris matriks sudah final | `docs/09 §9.3`, `docs/07 §A8` |
| Buat & terbitkan = SA **dan** advisor | Baris matriks sudah final | `docs/09 §9.3` |
| Invoice tidak pernah dihapus | Dibatalkan lewat status `void` | `docs/04 §4.5` |
| Nomor invoice | `INV/{tahun}/{bulan}/{urut 4 digit per bulan}`, terbit **di dalam transaksi** dengan penguncian | `docs/05 §5.6` |
| Pemicu draft | Transisi `in_progress → completed` membuat invoice `draft` berisi 1 item jasa dari paket | `docs/05 §5.3`, `§5.6` |
| Jenis item | `jasa` \| `part` — dua nilai, bukan lebih | `docs/04 §4.2` |
| Metode bayar | `cash` \| `transfer` \| `edc`, **pencatatan saja** — tanpa integrasi payment gateway | `docs/04 §4.2` |
| Rute admin | `/admin/invoices` (SA + ADV) | `docs/03`, `docs/07 §A8` |
| Rute customer | `GET /invoice/{invoice}` + `GET /invoice/{invoice}/pdf` | `docs/03` |
| Menu | Grup **Operasional** → Jadwal · Booking · **Invoice** | `docs/07 §A13` |
| Activity log | Model `Invoice` ikut dicatat; void invoice **wajib** tercatat | `docs/04 §4.2`, `docs/09 §9.7` |
| Retensi | Invoice disimpan **permanen** (kebutuhan garansi) — tidak ikut retensi 12 bulan | `docs/09 §9.7` |
| Laporan Pendapatan | Tab keempat di `/admin/laporan`, **SA saja** — bukan rute baru | `docs/07 §A9`, roadmap 2.4.7 |
| Total nilai invoice di A6 | Dilengkapi di 2.4.6 — janji yang ditinggalkan F1.5 | roadmap 2.4.6, kode `CustomerController::ringkasan` |
| Baris A14 | `AccessMatrixTest` ditambahi baris invoice (2.4.6) | roadmap 2.2.7, 2.4.6 |
| Layout & komponen | `AdminLayout`, `CustomerLayout`, `DataTable`, `ConfirmDialog`, `StatusBadge`, toast, `lib/format.ts` yang sudah ada | `.claude/rules/20`, `40` |
| Master sparepart / stok | **Won't do** — sudah ditetapkan batas scope | `docs/02 §2.6` |

---

## Pertentangan yang ditemukan — sudah diputuskan

`CLAUDE.md` menetapkan **dokumen yang benar** bila kode dan dokumen berselisih. Di sini yang
berselisih adalah **dokumen dengan dokumen** dan **dokumen dengan kenyataan runtime**, jadi
keputusannya diambil eksplisit lalu dokumennya diperbarui.

| # | Titik | Yang tertulis | Yang bertentangan | Diselesaikan di |
|---|-------|---------------|-------------------|-----------------|
| K1 | Satu booking berapa invoice | `docs/04 §4.2`: `booking_id` **FK unique** — "satu booking maksimal satu invoice" | `docs/05 §5.6`: invoice `issued` yang salah "hanya bisa di-`void` lalu **dibuat ulang**" — mustahil bila kolomnya unik | **Q1** |
| K2 | Koreksi setelah `paid` | `InvoiceStatus::canTransitionTo()` (kode): `Paid => []` — buntu | `docs/07 §A8` & `docs/04 §4.5`: koreksi hanya lewat `void`; tanpa jalur `paid → void` satu klik keliru hanya bisa diperbaiki lewat DBeaver | **Q2** |
| K3 | PDF | `docs/03`: `barryvdh/laravel-dompdf` 3.x | Paketnya **belum terpasang**; `nixpacks.toml` hanya menjamin `pdo_mysql, mbstring, openssl, tokenizer`, dan **R3** menyatakan `gd` "tidak lagi dibutuhkan" | **Q6** |
| K4 | `{{ringkasan_biaya}}` | roadmap 2.4.8: dihidupkan di tahap ini | Template `booking_completed` ditawarkan **saat status berubah**, ketika invoice masih `draft` dan totalnya masih bisa berubah | **Q8** |
| K5 | Invoice terlihat customer | Kode: `InvoiceStatus::isVisibleToCustomer()` → hanya `issued`/`paid` | Invoice yang sudah pernah diterbitkan lalu di-`void` akan **hilang jadi 404** dari mata pelanggan yang sudah memegang PDF-nya | **Q7** |

---

## Pertanyaan

### Q1 — Satu booking boleh punya berapa invoice? (K1)

**Pertanyaan.** `docs/04 §4.2` menetapkan `invoices.booking_id` sebagai **FK unique**. Tetapi
`docs/05 §5.6` menjanjikan jalur koreksi: invoice `issued` yang salah "hanya bisa di-`void` lalu
**dibuat ulang**". Kedua kalimat itu tidak bisa benar bersamaan — begitu invoice pertama di-void,
kolom unik menolak invoice kedua untuk booking yang sama, dan satu-satunya jalan keluar adalah
menghapus baris void-nya, yang dilarang `docs/04 §4.5`.

Ini bukan perkara gaya. Ia menentukan bentuk migration 2.4.1 — butir **pertama** tahap ini — dan
migration yang sudah jalan tidak boleh disunting.

**Rekomendasi.** Ganti `unique` menjadi **index biasa**, lalu tegakkan aturannya di aplikasi:

> Satu booking boleh punya **maksimal satu invoice yang belum di-void**. Invoice `void` boleh
> menumpuk sebagai jejak.

Alasannya:

1. **Jalur koreksi harus benar-benar ada.** Tanpa itu, invoice salah ketik yang sudah diterbitkan
   menjadi permanen — dan jalan pintas yang pasti diambil orang adalah membuka DBeaver. Itu persis
   pola yang membuat sistem lama tidak bisa dipercaya.
2. **Kolom unik memindahkan kegagalan ke tempat yang salah.** Ia akan muncul sebagai 500
   "Duplicate entry" di layar advisor, bukan sebagai penolakan yang menjelaskan.
3. **Riwayat void tetap terbaca.** Invoice pertama tetap ada beserta alasan pembatalannya; nomor
   yang sudah terbit tidak dipakai ulang.
4. Penegakannya sama persis dengan pola yang sudah terbukti di proyek ini: dihitung **di dalam
   transaksi dengan baris terkunci** (`lockForUpdate`), seperti kuota slot di `BookingService`
   dan "Super Admin aktif terakhir" di `UserService`.

Konsekuensi yang diterima: `Booking::invoice()` berubah dari `HasOne` polos menjadi relasi
"invoice aktif" (`hasOne()->whereNot('status', 'void')`), plus `invoices()` `HasMany` untuk
riwayat. Detail booking menampilkan yang aktif; daftar A8 menampilkan seluruhnya.

**Alternatif yang saya tolak:** mempertahankan `unique` dan memperbolehkan invoice `void`
disunting kembali menjadi `draft`. Itu membuat `void` bukan lagi keadaan akhir, mematahkan
`InvoiceStatus::canTransitionTo()`, dan membuat nomor invoice yang sudah terbit hidup kembali di
tangan pelanggan yang sudah menerima PDF-nya.

**Keputusan saya:** `[x]` disetujui — ikuti rekomendasi

**Catatan:** `docs/04 §4.2` wajib diperbarui: `booking_id` bukan lagi unik, dan aturan "maksimal
satu invoice aktif" ditulis eksplisit sebagai aturan aplikasi, bukan aturan kolom.

---

### Q2 — Boleh `paid → void`? (K2)

**Pertanyaan.** `App\Enums\InvoiceStatus::canTransitionTo()` yang sudah ada sejak F1.0 menyatakan
`Paid => []` — lunas adalah keadaan buntu. Artinya advisor yang salah klik "Tandai Lunas" pada
invoice orang lain tidak punya jalan kembali sama sekali. Sementara `docs/07 §A8` menyebut `void`
sebagai satu-satunya jalur pembatalan, dan `docs/04 §4.5` melarang penghapusan baris.

**Rekomendasi.** Buka jalur `paid → void`, **Super Admin saja**, wajib alasan. Enumnya diperbaiki:

```php
self::Draft  => [self::Issued, self::Void],
self::Issued => [self::Paid, self::Void],
self::Paid   => [self::Void],   // ← ditambahkan
self::Void   => [],
```

Alasannya:

1. **Salah klik itu pasti terjadi**, dan "Tandai Lunas" adalah satu tombol tanpa lawan. Sistem
   yang tidak punya jalan pulang memaksa orang membuka database — cacat yang sama seperti Q1.
2. **Refund dan pembatalan setelah bayar adalah kenyataan bengkel**, bukan kasus tepi karangan.
3. **Wewenangnya sudah pas.** Matriks `docs/09 §9.3` sudah menetapkan void milik Super Admin
   saja; advisor tetap tidak bisa membatalkan pembayaran yang ia catat sendiri.
4. `paid_at` dan `payment_method` **tetap disimpan** pada invoice yang di-void — itu jejak bahwa
   uangnya pernah tercatat masuk, dan menghapusnya membuat log audit berbohong.

**Keputusan saya:** `[x]` disetujui — ikuti rekomendasi

**Catatan:** Perubahan enum ini menyentuh berkas yang lahir di F1.0. `docs/04 §4.3` dan
`docs/05 §5.6` ikut diperbarui supaya transisinya tertulis di dokumen, bukan hanya di kode.

---

### Q3 — Draft dibuat di transaksi yang sama dengan `completed`? Dan booking `completed` yang sudah telanjur ada?

**Pertanyaan.** Dua hal yang saling terkait:

1. `docs/05 §5.3` menyatakan transisi `in_progress → completed` membuat invoice draft. Apakah
   pembuatan itu masuk **ke dalam** transaksi `BookingService::changeStatus()`, atau berjalan
   setelahnya?
2. Seluruh booking yang sudah berstatus `completed` sebelum tahap ini **tidak punya invoice**.
   Di-backfill, atau tidak?

**Rekomendasi.**

**(a) Satu transaksi.** `BookingService::changeStatus()` memanggil `InvoiceService::createDraftFor()`
di dalam `DB::transaction` yang sudah ada. Invoice yang gagal terbentuk berarti statusnya ikut
batal berubah — advisor melihat galat, bukan booking selesai tanpa tagihan yang tak seorang pun
sadari sampai akhir bulan. Polanya sama dengan `WalkInBookingService` di F1.5, yang membungkus
akun + kendaraan + booking dalam satu transaksi dengan alasan identik.

Ini **berbeda** dari keputusan WhatsApp di Tahap 10 (baris log baru lahir saat tombol ditekan), dan
perbedaannya disengaja: draft WA adalah catatan bahwa seseorang bertindak, sedangkan invoice draft
adalah data yang harus **sudah ada** untuk disunting. Menunda kelahirannya berarti menyuruh advisor
membuat sesuatu yang seharusnya otomatis.

**(b) Tanpa backfill; sediakan tombol.** Detail booking berstatus `completed` yang tidak punya
invoice aktif menampilkan tombol **"Buat Invoice"**. Satu jalur ini melayani tiga keadaan sekaligus:

- booking yang selesai sebelum Tahap 11 ada,
- invoice yang baru saja di-void dan perlu dibuat ulang (Q1),
- pemulihan bila pembuatan otomatis pernah gagal.

Backfill otomatis ditolak karena harga paket bisa sudah berubah sejak servisnya dikerjakan, dan
invoice yang dikarang mundur dengan harga hari ini adalah angka yang salah — mengulang pola
"mengarang jejak audit" yang sudah ditolak untuk activity log di Tahap 9.

**(c) Invoice hanya untuk booking `completed`.** Rute pembuatannya menolak status lain — bukan
disembunyikan di UI saja.

**Keputusan saya:** `[x]` disetujui — ikuti rekomendasi

**Catatan:** Layar A8 karena itu **tidak punya form "buat invoice kosong"**. Pintu masuknya selalu
sebuah booking; `resource /invoices` di `docs/03` menyusut menjadi index · show/edit · update ·
aksi status, tanpa `create`/`store` yang berdiri sendiri.

---

### Q4 — Paket gratis tetap dibuatkan invoice?

**Pertanyaan.** `service_packages` punya `is_free` yang dibedakan dari `price = 0` supaya
antarmuka bisa menulis "Gratis". Booking dengan paket gratis — servis berkala garansi — tetap
dibuatkan invoice draft senilai Rp 0, atau dilewati?

**Rekomendasi.** **Tetap dibuatkan.** Item jasa tetap lahir dengan `unit_price = 0` dan deskripsi
nama paketnya.

Alasannya:

1. **Servis gratis tetap sering menambah part.** Oli, filter, atau jasa tambahan di luar cakupan
   garansi ditagihkan — dan invoice yang tidak pernah lahir memaksa advisor menekan "Buat Invoice"
   secara manual justru pada kasus yang paling sering terjadi.
2. **Invoice Rp 0 adalah bukti servis garansi**, bukan kejanggalan akuntansi. Pelanggan yang kelak
   mengklaim garansi butuh dokumen bahwa servis berkalanya benar-benar dijalankan.
3. **Aturan "satu invoice per booking selesai" jadi tanpa pengecualian** — dan aturan tanpa
   pengecualian jauh lebih murah diuji dan dijelaskan.

Konsekuensi yang diterima: laporan Pendapatan (Q9) akan memuat sejumlah invoice bernilai 0. Itu
bukan gangguan — jumlah invoice dan jumlah rupiah memang dua angka berbeda, dan keduanya
ditampilkan.

**Keputusan saya:** `[x]` disetujui — ikuti rekomendasi

**Catatan:** Label "Gratis" di antarmuka tetap milik `is_free` pada paket layanan, **bukan**
diturunkan dari `total = 0` pada invoice. Invoice berbayar yang kebetulan berdiskon penuh bukan
servis gratis.

---

### Q5 — Diskon & pajak: nominal manual atau persen dari `config`?

**Pertanyaan.** Skema menetapkan `discount` dan `tax` sebagai `decimal(12,2)` — nominal. Tetapi
apakah advisor mengetiknya bebas, atau pajak dihitung dari tarif PPN di `config`?

**Rekomendasi.** **Keduanya nominal, diketik tangan, bawaan 0.** Tanpa tarif PPN di config.

Alasannya:

1. **Bengkel ini kemungkinan besar bukan PKP.** Menaruh tarif PPN di config berarti menuliskan
   asumsi pajak yang belum dikonfirmasi ke dalam kode, lalu menghitungnya otomatis di setiap
   invoice. Kolomnya sudah disebut "PPN **opsional**" di `docs/04 §4.2`.
2. **Diskon bengkel hampir selalu nominal** ("potong Rp 50.000"), bukan persentase.
3. Bila kelak tarif tetap dibutuhkan, menambahkannya ke `config/booking.php` adalah pekerjaan
   satu jam — sedangkan mencabut perhitungan otomatis yang sudah terlanjur dipakai tidak.

Aturan validasi yang menyertainya:

| Aturan | Alasan |
|--------|--------|
| `discount ≥ 0` dan `discount ≤ subtotal` | `total` tidak boleh negatif; invoice bernilai minus bukan invoice |
| `tax ≥ 0` | — |
| `qty > 0`, maksimal 2 desimal | 0,5 jam jasa sah; 0 jam tidak |
| `unit_price ≥ 0` | Item bernilai 0 sah (bonus, penggantian garansi) |
| Minimal 1 item saat **menerbitkan** | Invoice kosong yang terbit adalah nomor yang terbuang |

**Keputusan saya:** `[x]` disetujui — ikuti rekomendasi

**Catatan:** `discount ≤ subtotal` diperiksa **di server saat menyimpan**, terhadap subtotal hasil
hitungan server — bukan terhadap angka yang dikirim browser.

---

### Q6 — PDF: pasang dompdf atau halaman cetak? (K3)

**Pertanyaan.** `docs/03` menyebut `barryvdh/laravel-dompdf` 3.x, tetapi paketnya belum terpasang.
`nixpacks.toml` hanya menjamin `pdo_mysql, mbstring, openssl, tokenizer` ada di runtime Railway,
dan **R3** menyatakan `gd` "tidak lagi dibutuhkan" setelah gambar pindah ke Cloudinary. Ini
berbahaya persis seperti `maatwebsite/excel` di F2.1, yang batal dipakai karena menuntut ekstensi
`zip` yang ketersediaannya tidak terbukti.

**Rekomendasi.** **Pasang dompdf, tetapi rancang PDF-nya tanpa satu pun gambar raster.**

Alasannya:

1. **Kebutuhan keras dompdf adalah `ext-dom` + `ext-mbstring`**, keduanya standar. `ext-gd` hanya
   *suggest* — `composer install` tidak akan gagal tanpanya, jadi risiko "deploy jatuh total"
   seperti kasus `zip` tidak berlaku di sini.
2. **Tanpa `gd`, yang gagal hanya gambar raster** — dan kop surat berupa **teks** dari
   `config/company.php` (nama, alamat, telepon, email) sudah memadai untuk invoice bengkel.
   Tidak ada logo PNG, tidak ada stempel.
3. **Jalur cadangan disepakati di muka**, seperti CSV di F2.1: bila dompdf ternyata bermasalah di
   Railway, 2.4.3 berubah menjadi halaman cetak (`@media print`) + "Simpan sebagai PDF" dari
   peramban. Antarmuka dan datanya tidak berubah sama sekali — hanya cara berkasnya dihasilkan.

**Syarat selesai yang mengikat:** 2.4.3 tidak boleh dinyatakan selesai sebelum satu PDF sungguhan
berhasil diunduh **dari Railway**, bukan hanya dari komputer lokal (DoD #9).

**Keputusan saya:** `[x]` disetujui — ikuti rekomendasi

**Catatan:** PDF disusun sebagai Blade view, bukan React — dompdf tidak menjalankan JavaScript.
Ini pengecualian sah terhadap `.claude/rules/20` dengan pola yang sama seperti JSON-LD di F1.6.

---

### Q7 — Rute customer: bagaimana invoice diambil, dan apa yang terjadi pada draft? (K5)

**Pertanyaan.** `docs/03` menetapkan `GET /invoice/{invoice}` dan `/invoice/{invoice}/pdf`. ID
yang berurutan berarti pelanggan bisa mencoba nomor lain. Dan apa jawabannya bila invoice yang
diminta masih `draft` — atau sudah `void` padahal pelanggan sudah memegang PDF-nya?

**Rekomendasi.**

**(a) Diambil lewat relasi, bukan pencarian global.** `$request->user()->invoices()` (relasi
`hasManyThrough` lewat `bookings`), sesuai `.claude/rules/50` #2 — bukan `Invoice::findOrFail()`
lalu diperiksa belakangan. Invoice milik orang lain karena itu **tidak pernah terambil**, dan
`InvoicePolicy` menjadi lapis kedua, bukan satu-satunya.

**(b) Yang terlihat pelanggan = invoice yang `issued_at`-nya terisi.** Bukan `status ∈ {issued, paid}`.

Bedanya nyata: invoice yang pernah diterbitkan lalu di-`void` (Q1, Q2) akan **hilang menjadi 404**
bila patokannya status, padahal pelanggan sudah menerima PDF-nya dan berhak tahu bahwa tagihan itu
dibatalkan. Dengan patokan `issued_at`, ia tetap terbuka dengan lencana **"Dibatalkan"** yang jelas.

- `draft` (`issued_at` null) → **404**, bukan 403. Keberadaan draft yang belum diterbitkan bukan
  urusan pelanggan, dan 403 justru membocorkan bahwa ada sesuatu di sana.
- `void` yang tidak pernah terbit → 404, dengan alasan yang sama.

**(c) Pintu masuknya dari detail booking**, bukan menu tersendiri. Riwayat servis pelanggan sudah
ada; menambah menu "Invoice" berdampingan dengan "Riwayat" akan menampilkan dua daftar dari satu
kenyataan.

**Keputusan saya:** `[x]` disetujui — ikuti rekomendasi

**Catatan:** `InvoiceStatus::isVisibleToCustomer()` yang sudah ada di kode **tidak cukup** untuk
aturan (b) — enum tidak bisa membedakan void-yang-pernah-terbit dari void-yang-tidak-pernah.
Method itu digantikan pemeriksaan `issued_at !== null` di model/policy, dan enumnya dibersihkan
supaya tidak ada dua sumber kebenaran yang berselisih (cacat B2).

---

### Q8 — `{{ringkasan_biaya}}` merender apa, dan bila invoice belum terbit? (K4)

**Pertanyaan.** Butir 2.4.8 menghidupkan placeholder yang sengaja ditolak di Tahap 10. Tetapi
template `booking_completed` ditawarkan tepat **saat status berubah menjadi `completed`** — dan
pada detik itu invoicenya masih `draft`, totalnya belum final, dan pelanggan belum berhak
melihatnya (Q7).

**Rekomendasi.**

**(a) Satu baris, bukan rincian.** Formatnya:

```
Total biaya: Rp 450.000 (3 item)
```

Bukan daftar item. Batas `max_template_body_length` adalah 700 karakter dan jumlah item tidak
terbatas — rincian panjang akan memotong penutup pesan tanpa ada yang tahu, persis alasan batas itu
dibuat.

**(b) Bila invoice belum diterbitkan**, placeholder merender kalimat netral:

```
Rincian biaya menyusul
```

**Bukan** angka draft. Total draft masih bisa berubah, dan angka yang dikirim ke pelanggan lalu
berubah lebih buruk daripada tidak mengirim angka sama sekali.

**(c) Alurnya tidak diubah, hanya ditegaskan.** `docs/05 §5.5` sudah menggambarkan urutan yang
benar: selesai → invoice draft → tambah item → **terbitkan** → baru pesan WA "servis selesai".
Advisor yang mengikuti urutan itu selalu mendapat angka; yang melompatinya mendapat kalimat
netral — bukan galat.

**(d) Tiga tempat wajib diubah bersamaan**, dijaga `tests/Unit/WhatsAppPlaceholderTest.php` yang
sudah ada: `config('whatsapp.allowed_placeholders')`, `WhatsAppPlaceholders::forBooking()`, dan
`WhatsAppPlaceholders::sample()`.

**Keputusan saya:** `[x]` disetujui — ikuti rekomendasi

**Catatan — konsekuensi yang harus disebut di PRD:** `WhatsAppTemplateSeeder` **hanya membuat baris
yang belum ada** dan tidak pernah menyentuh yang sudah ada — satu-satunya seeder produksi yang
bukan `updateOrCreate`, dibuat begitu di Tahap 10 supaya suntingan Super Admin tidak hilang.
Akibatnya teks awal `booking_completed` yang diperbarui di seeder **hanya berlaku untuk instalasi
baru**. Pada database yang sudah berjalan, `{{ringkasan_biaya}}` harus disisipkan sekali lewat
layar A7. Itu satu langkah manual yang disengaja — menimpanya otomatis akan mematahkan jaminan
Tahap 10.

---

### Q9 — Laporan Pendapatan dihitung dari tanggal apa?

**Pertanyaan.** `docs/07 §A9` menyebut "Total invoice `issued`/`paid` per periode". Periode diukur
terhadap tanggal apa — `issued_at`, `paid_at`, atau tanggal booking? Dan apakah `issued` dan `paid`
dijumlahkan menjadi satu angka?

**Rekomendasi.** **Dikelompokkan menurut `issued_at`**, ditampilkan sebagai **dua angka
berdampingan**, bukan satu:

| Angka | Isi |
|-------|-----|
| **Diterbitkan** | Σ `total` invoice ber-`issued_at` di dalam periode, status `issued` **atau** `paid` |
| **Lunas** | Bagian dari angka di atas yang sudah `paid` |
| **Belum dibayar** | Selisih keduanya — piutang |

Alasannya:

1. **`issued_at` adalah tanggal pendapatannya diakui.** Memakai `paid_at` membuat invoice yang
   belum dibayar tidak muncul di mana pun, sehingga piutang menjadi tak terlihat — justru angka
   yang paling ingin diketahui pemilik bengkel.
2. **Satu angka gabungan menyesatkan.** "Rp 12 juta" yang ternyata separuhnya belum masuk kas
   adalah laporan yang salah dibaca.
3. **`void` dikeluarkan seluruhnya.** `draft` juga — ia belum pernah menjadi tagihan.
4. Pecahan per paket layanan dan per metode bayar ikut ditampilkan, mengikuti bentuk tiga tab
   laporan yang sudah ada — bukan bentuk baru yang harus dipelajari ulang.

Otorisasi: `BookingPolicy::viewReports` sekarang mengizinkan kedua role staf. Ditambahkan
kemampuan terpisah **`viewRevenueReport` — Super Admin saja**, dan tab-nya tidak dirender untuk
advisor (bukan dirender lalu dinonaktifkan), sejalan dengan pola A13. Export CSV untuk tab ini
tunduk pada pembatasan yang sama.

**Keputusan saya:** `[x]` disetujui — ikuti rekomendasi

**Catatan:** Komentar di `BookingPolicy::viewReports` yang berbunyi "laporan pendapatan … belum
ada: tabel `invoices` baru lahir di F2.4" ikut dicabut saat kemampuan barunya dipasang.

---

### Q10 — Apa yang sengaja **tidak** dikerjakan tahap ini?

**Pertanyaan.** Batas scope perlu dikunci sebelum PRD, supaya "sekalian saja" tidak masuk lewat
pintu belakang.

**Rekomendasi — seluruhnya Won't do di Tahap 11:**

| Tidak dikerjakan | Alasan |
|------------------|--------|
| **Pembayaran parsial / cicilan** | `paid_at` tunggal di skema; mendukung cicilan menuntut tabel pembayaran tersendiri. Bengkel satu cabang menerima pembayaran sekaligus |
| **Master sparepart & stok** | Sudah Won't do di `docs/02 §2.6`. Deskripsi item adalah teks bebas |
| **Tarif PPN otomatis** | Q5 |
| **Kirim invoice lewat email** | Tidak ada SMTP (keputusan final #4). Jalurnya: unduh PDF + kirim manual lewat WhatsApp |
| **Payment gateway / QRIS** | `payment_method` murni pencatatan (`docs/04 §4.2`) |
| **Nota kredit / retur** | Koreksi memakai `void` + buat ulang (Q1, Q2) |
| **Invoice untuk booking yang belum `completed`** | Q3(c) |
| **Layar invoice yang berdiri sendiri untuk customer** | Q7(c) — pintu masuknya detail booking |
| **Penomoran yang bisa dikonfigurasi** | Formatnya sudah ditetapkan `docs/05 §5.6` |

**Keputusan saya:** `[x]` disetujui — ikuti rekomendasi

**Catatan:** —

---

## Risiko

⚠️ **Potensi masalah: `ext-gd` mungkin tidak ada di runtime Railway** → dompdf memerlukannya untuk
menggambar JPEG/PNG. `gd` hanya *suggest* di composer, jadi `composer install` tetap lulus dan
kegagalannya baru muncul saat PDF pertama dirender di produksi — bukan saat deploy. → PDF dirancang
**tanpa gambar raster** (Q6); dibuktikan sekali di Railway sebelum 2.4.3 dinyatakan selesai.

⚠️ **Potensi masalah: nomor invoice per bulan bisa kembar** → dua advisor menerbitkan invoice pada
detik yang sama; baris yang belum ada tidak bisa dikunci, persis seperti kode booking di F1.4. →
Pakai pola yang sudah terbukti: unique index pada `invoice_number` + **transaksi diulang** bila yang
kalah menabraknya.

⚠️ **Potensi masalah: aturan "satu invoice aktif per booking" hanya hidup di aplikasi** → Setelah
Q1 mencabut kolom unik, tidak ada lagi jaring pengaman database. Dua permintaan bersamaan bisa
menyisipkan dua invoice draft. → Dihitung di dalam transaksi dengan `lockForUpdate` pada baris
**booking**-nya (bukan pada baris invoice yang belum ada), lalu diuji dengan tiga permintaan
beruntun — pola yang sama dengan kuota slot F1.4. Catatan jujur yang ikut ke roadmap:
`lockForUpdate` hanya berlaku nyata di MySQL, SQLite yang dipakai uji mengabaikannya.

⚠️ **Potensi masalah: advisor melihat total tiap invoice, padahal laporan Pendapatan SA-saja** →
Pembatasan di `docs/09 §9.3` itu soal kemudahan agregat, bukan kerahasiaan angka; advisor yang
menerbitkan invoice memang harus melihat totalnya. → Diterima sadar, dicatat di PRD supaya tidak
dibaca sebagai kebocoran saat `/audit-paritas` di 2.5.10.

⚠️ **Potensi masalah: `migrate:fresh` menghapus invoice** → **R2** masih berlaku sampai 2.5.1
memisahkan database produksi. Invoice adalah data pertama di proyek ini yang punya konsekuensi
akuntansi. → Selama isinya masih seeder, tidak apa-apa; begitu ada invoice sungguhan pertama,
[batas pemakaian `migrate:fresh`](../10-roadmap-implementasi.md#kapan-migratefresh-berhenti-boleh)
sudah terlewati dan 2.5.1 tidak boleh mundur lagi.

⚠️ **Potensi masalah: total dihitung di frontend** → Penyunting invoice menampilkan subtotal yang
berubah saat advisor mengetik qty. Menghitungnya di React adalah cacat "menghitung total harga di
frontend" (`.claude/rules/20`). → Angka di layar hanya **pratinjau**; yang disimpan selalu hasil
hitungan server, dan ujinya menembak rute dengan `total` palsu di payload untuk membuktikan server
mengabaikannya.

⚠️ **Potensi masalah: `{{ringkasan_biaya}}` tidak muncul di database yang sudah berjalan** →
Seeder template sengaja tidak menimpa baris yang sudah ada (jaminan Tahap 10). → Satu langkah manual
lewat layar A7 setelah deploy, ditulis eksplisit sebagai butir serah terima di PRD — bukan
diasumsikan orang akan menyadarinya sendiri.

---

## Perubahan dokumen yang harus ikut dilakukan

**Seluruhnya sudah dikerjakan** bersama implementasinya pada 5 Agustus 2026.

| Dokumen | Perubahan | Dari |
|---------|-----------|------|
| `docs/04 §4.2` `invoices` | ✅ `booking_id` **bukan unique** lagi; ditambah kolom `void_reason` dan catatan aturan aplikasi "maksimal satu invoice non-void per booking" | Q1 |
| `docs/04 §4.3` | `InvoiceStatus` ditambah transisi `paid → void` | Q2 |
| `docs/04 §4.5` | Baris Invoice diperjelas: `void` boleh menumpuk, penggantinya invoice baru dengan nomor baru | Q1 |
| `docs/05 §5.6` | Jalur koreksi ditulis utuh (void → buat ulang), termasuk dari `paid`; ditegaskan invoice draft lahir di dalam transaksi `completed` | Q1, Q2, Q3 |
| `docs/05 §5.3` | Baris `in_progress → completed`: "invoice draft dibuat **dalam transaksi yang sama**" | Q3 |
| `docs/03` daftar rute | `resource /invoices` disesuaikan — tanpa `create`/`store` berdiri sendiri; ditambah rute buat-dari-booking, terbitkan, lunas, void | Q3 |
| `docs/07 §A8` | Ditambah: tombol "Buat Invoice" di detail booking, aturan paket gratis, aturan diskon ≤ subtotal, minimal 1 item saat terbit | Q3, Q4, Q5 |
| `docs/07 §A9` | Baris Pendapatan diperjelas: dikelompokkan `issued_at`, tiga angka (diterbitkan · lunas · belum dibayar) | Q9 |
| `docs/07 §A13` | Menu Invoice mulai dirender; catatan "yang belum dirender tinggal Invoice" dicabut | — |
| `docs/09 §9.3` | Ditambah baris "Melihat laporan pendapatan" sudah ada; dipastikan konsisten dengan `viewRevenueReport` | Q9 |
| `docs/10` Tahap 11 | Catatan penyimpangan ditulis setelah tahap selesai, seperti tahap-tahap sebelumnya | semua |
| `config/whatsapp.php` + `WhatsAppPlaceholders` | `ringkasan_biaya` ditambahkan di **ketiga** tempat; komentar "sengaja belum ada" dicabut | Q8 |
| `App\Enums\InvoiceStatus` | `Paid => [Void]`; `isVisibleToCustomer()` dicabut, digantikan pemeriksaan `issued_at` | Q2, Q7 |
| `BookingPolicy` | Komentar "laporan pendapatan belum ada" dicabut; kemampuan `viewRevenueReport` ditambahkan | Q9 |
| `Admin\CustomerController::ringkasan` | Komentar "total nilai invoice sengaja belum ada" dicabut, angkanya diisi | 2.4.6 |
| `ReportService` / `Admin\ReportController` | Komentar "Pendapatan tidak ada di sini" dicabut | Q9 |

---

## Log Keputusan

| # | Keputusan | Hasil |
|---|-----------|-------|
| Q1 | Satu booking berapa invoice | `[x]` **Index biasa, bukan unique.** Maksimal satu invoice non-void per booking, ditegakkan dalam transaksi terkunci |
| Q2 | Boleh `paid → void`? | `[x]` **Ya, Super Admin saja, wajib alasan.** Enum diperbaiki; `paid_at` & `payment_method` tetap disimpan |
| Q3 | Kapan draft lahir & nasib booking lama | `[x]` **Satu transaksi dengan `completed`.** Tanpa backfill; tombol "Buat Invoice" jadi jalur pemulihan. Invoice hanya untuk booking `completed` |
| Q4 | Paket gratis dibuatkan invoice? | `[x]` **Ya, Rp 0.** Aturan tanpa pengecualian; label "Gratis" tetap dari `is_free` |
| Q5 | Diskon & pajak | `[x]` **Nominal, diketik tangan, bawaan 0.** Tanpa tarif PPN di config; `discount ≤ subtotal`; minimal 1 item saat terbit |
| Q6 | PDF | `[x]` **dompdf, tanpa gambar raster.** Kop surat teks dari `config/company.php`; cadangan print-CSS disepakati; wajib dibuktikan di Railway |
| Q7 | Akses customer | `[x]` **Lewat relasi; patokan `issued_at`, bukan status.** Draft → 404. Void yang pernah terbit tetap terlihat berlencana "Dibatalkan". Pintu masuk: detail booking |
| Q8 | `{{ringkasan_biaya}}` | `[x]` **Satu baris total + jumlah item.** Belum terbit → "Rincian biaya menyusul". Tiga tempat diubah bersamaan; baris template lama disunting manual sekali lewat A7 |
| Q9 | Laporan Pendapatan | `[x]` **Dikelompokkan `issued_at`, tiga angka** (diterbitkan · lunas · belum dibayar). `void` & `draft` dikecualikan. Kemampuan `viewRevenueReport` — SA saja |
| Q10 | Non-goal | `[x]` **Sembilan hal dikunci Won't do** — cicilan, master sparepart, PPN otomatis, email, payment gateway, nota kredit, invoice pra-`completed`, menu customer tersendiri, penomoran yang bisa dikonfigurasi |

**Sesi ditutup 5 Agustus 2026.** Berikutnya: `/write-prd` → `docs/prds/prd-invoice.md`.
