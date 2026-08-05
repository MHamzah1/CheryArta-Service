# Grill — F2.3 Notifikasi WhatsApp Penuh (A7)

**Status:** ✅ selesai · dibuka dan ditutup 4 Agustus 2026
**Tahap:** 10 (`F2.3`) — lihat [roadmap](../10-roadmap-implementasi.md#tahap-10--notifikasi-whatsapp-penuh--f23)
**Keputusan:** **9 dari 9 terjawab.** Q1 disetujui langsung; Q2–Q9 diputuskan mengikuti
rekomendasi atas permintaan pemilik proyek (4 Agustus 2026). Tidak ada rekomendasi yang ditolak.
**Berikutnya:** → [`docs/prds/prd-notifikasi-whatsapp.md`](../prds/prd-notifikasi-whatsapp.md)

Sumber yang sudah dibaca: `docs/08-notifikasi-whatsapp.md` (seluruhnya) ·
`docs/07-modul-admin.md §A7, §A1, §A13, §A14` · `docs/04-skema-database.md §4.2`
(`whatsapp_templates`, `whatsapp_messages`) · `docs/09-keamanan-hak-akses.md §9.3` ·
`docs/10-roadmap-implementasi.md` (butir 2.3.1–2.3.6, **R6**, tabel risiko) ·
`.claude/rules/10,20,30,40,50,60` · kode: `App\Services\WhatsAppNotifier`,
`config/whatsapp.php`, `config/company.php` (`wa_messages`), `App\Support\PhoneNumber`,
`Admin\BookingController::show`, `Admin\ContactMessageController`,
`components/admin/whatsapp-panel.tsx`, `pages/admin/bookings/show.tsx`,
`tests/Feature/Admin/AccessMatrixTest.php`, `DashboardService`.

---

## Keputusan implisit — tidak ditanyakan

Sudah ditetapkan rancangan, aturan proyek, atau kode yang berjalan. Dicatat supaya tidak dibuka ulang.

| Hal | Ketetapan | Sumber |
|-----|-----------|--------|
| Mekanisme kirim | **Klik-to-chat `wa.me`, dipicu manual.** Tanpa gateway, tanpa pengiriman otomatis, tanpa email | Keputusan final #4, `docs/08 §8.1` |
| Pengingat H-1 otomatis | **Won't do** — mustahil tanpa gateway | `docs/08 §8.1` |
| Normalisasi nomor | `PhoneNumber::normalize` → `62…`; nomor mentah **tidak pernah** masuk URL | `docs/08 §8.3`, `.claude/rules/50` #7 |
| Encoding tautan | `rawurlencode`, bukan `urlencode` | `docs/08 §8.5` |
| Batas panjang pesan | 1.000 karakter (`config('whatsapp.max_message_length')`) | `docs/08 §8.5` |
| Dua tabel baru | `whatsapp_templates`, `whatsapp_messages` — kolomnya sudah ditetapkan | `docs/04 §4.2`, roadmap 2.3.1 |
| Panel draft | Tertanam di **detail booking**, bukan layar terpisah | `docs/07 §A7`, `docs/08 §8.2` |
| Hak akses panel draft | SA **dan** advisor (siapa pun yang boleh membuka booking itu) | `docs/07 §A7`, matriks `docs/09 §9.3` |
| Hak akses penyunting template | **Super Admin saja**; advisor ditolak di server, menunya tidak dirender | `docs/07 §A7`, `docs/07 §A13` |
| Validasi placeholder | Placeholder di luar daftar yang sah **ditolak saat menyimpan** | `docs/08 §8.4`, roadmap 2.3.4 |
| Isi pesan disimpan | `rendered_message` disimpan untuk audit — bukan dihitung ulang saat dibaca | `docs/04 §4.2`, `docs/08 §8.8` |
| Privasi | Nomor WA hanya terlihat staf yang login; halaman publik tidak pernah menampilkannya | `docs/08 §8.8`, `.claude/rules/50` |
| Larangan promosi | Template hanya untuk pesan transaksional | `docs/08 §8.8` |
| Tombol WA mengambang publik | Tidak berubah — nomor bengkel dari `config/company.php`, di luar alur ini | `docs/08 §8.6` |
| Baris A14 | `AccessMatrixTest` ditambahi baris template WA (2.3.6) | roadmap 2.2.7, 2.3.6 |
| Layout & komponen | `AdminLayout`, `DataTable`, `ConfirmDialog`, toast, `lib/format.ts` yang sudah ada | `.claude/rules/20`, `40` |
| Tahap tidak dipecah | Pemilik proyek sudah memilih "satu sub-fase, selesai secepatnya" di grill F2.2 Q1; F2.3 jauh lebih kecil dari F2.2 | grill F2.2 Q1 |

---

## Pertentangan antar-dokumen yang ditemukan

`CLAUDE.md` menetapkan **dokumen yang benar** bila kode dan dokumen berselisih. Tetapi di sini
**dokumen berselisih dengan dokumen**, jadi keputusannya diambil eksplisit lalu dokumennya
diperbarui. Empat titik:

| # | Titik | `docs/08 §8.4` | `docs/04 §4.2` | `config/whatsapp.php` | Kode yang berjalan | Diselesaikan di |
|---|-------|----------------|----------------|------------------------|--------------------|-----------------|
| K1 | Nama kunci template ke-6 | `booking_reminder` | `reminder_h1` | `booking_reminder` | — | **Q4** |
| K2 | Template untuk `no_show` | tidak ada | tidak ada | tidak ada | **ada** (`company.wa_messages.no_show`) | **Q3** |
| K3 | Daftar placeholder | 11 nama, ada `{{ringkasan_biaya}}` | menyebut `{{status}}` | 11 nama, tanpa `{{status}}` | 10 nama, tanpa `{{ringkasan_biaya}}` & `{{status}}` | **Q4** |
| K4 | Kunci pemilih pesan | kunci template | kunci template | kunci template | **status booking** | otomatis — kode sekarang jalur sementara **R6** |

---

## Pertanyaan

### Q1 — Kapan baris `whatsapp_messages` ditulis?

**Pertanyaan:** `docs/08 §8.2` menggambarkan barisnya ditulis **otomatis saat advisor mengubah
status**. Alternatifnya: baris ditulis **hanya ketika advisor menekan "Buka WhatsApp"**.

**Rekomendasi: baris ditulis saat advisor menekan "Buka WhatsApp".** Panel tetap menampilkan
pratinjau pesan begitu status berubah, tetapi pratinjau itu belum menjadi baris di database.

1. **Kartu "draft belum dikirim" harus bisa kembali ke nol.** Bila setiap perubahan status
   menulis baris `generated`, aksi cepat dashboard (F2.1.2 — Konfirmasi · Mulai · Selesai)
   meninggalkan tiga baris per booking. Angka yang tidak pernah nol akan diabaikan dalam
   seminggu — padahal kartu itu satu-satunya penangkal risiko "advisor lupa menekan kirim" yang
   tercatat di [tabel risiko roadmap](../10-roadmap-implementasi.md#risiko).
2. **`skipped` jadi punya arti jujur** — "saya sengaja tidak mengabari pelanggan ini", ditekan
   sadar; bukan tombol pembersih sampah buatan sistem sendiri.
3. **Log auditnya lebih berarti**: menjawab "pesan apa yang **dikeluarkan**", bukan "pesan apa
   yang pernah dirender server". Yang kedua tidak ada pembacanya.

⚠️ **Konsekuensi:** advisor yang mengubah status lalu menutup tab tidak meninggalkan jejak, jadi
kartu dashboard tidak bisa dihitung dari `whatsapp_messages`. Penangkalnya di **Q6**.

- **Keputusan saya:** [x] **Disetujui — baris ditulis saat advisor menekan "Buka WhatsApp".**
- **Catatan — konsekuensi teknis yang ikut terputus di sini:**
  1. **URL `wa.me` tetap disusun server saat halaman dirender**, dikirim sebagai prop. Yang
     ditunda sampai penekanan tombol adalah **penulisan barisnya**, bukan penyusunan tautannya.
  2. **Tombolnya tetap `<a href>` sungguhan, bukan `window.open()` sesudah menunggu respons.**
     Peramban memblokir jendela baru yang dibuka di luar tumpukan gestur pengguna, jadi pola
     "POST dulu, buka belakangan" akan gagal diam-diam di sebagian pemakaian. Yang berjalan:
     tautannya terbuka seperti biasa, dan penekanan yang sama memicu `router.post` untuk menulis
     barisnya. Ini menyimpang dari `window.open(url, '_blank', 'noopener')` di
     [`docs/08 §8.5`](../08-notifikasi-whatsapp.md#85-pembuatan-tautan) — dokumennya diperbarui.
  3. **Status `generated` tetap dipakai**, artinya: "advisor sudah membuka WhatsApp, belum
     menandai terkirim". Perpindahannya `generated → sent` atau `generated → skipped`.

---

### Q2 — Pesan boleh disunting di panel sebelum dibuka?

**Pertanyaan:** [`docs/08 §8.2`](../08-notifikasi-whatsapp.md#82-cara-kerja) menulis: *"Pesan yang
tampil di panel bisa disunting sebelum dikirim; versi final yang benar-benar dibuka itulah yang
tersimpan di `whatsapp_messages.rendered_message`."* Jadi panel berisi `<textarea>`, bukan
pratinjau mati. Apakah itu dipertahankan?

**Rekomendasi: tidak — panel menampilkan pratinjau yang tidak bisa disunting.** Menyimpang dari
`docs/08 §8.2`, jadi bila disetujui dokumen itu ikut diperbarui.

1. **WhatsApp sendiri sudah menyediakan kotak sunting.** Tautan `wa.me?text=…` tidak mengirim
   apa pun — ia hanya **mengisi kolom ketik** WhatsApp. Advisor yang ingin menambah kalimat
   mengetiknya di sana, di layar yang sama tempat ia menekan kirim. Penyunting kedua di panel
   kita menggandakan kemampuan yang tujuannya sudah punya.
2. **`rendered_message` tetap bisa dipercaya sebagai buatan server.** Bila teksnya bisa disunting
   di React, isi kolom audit itu menjadi apa pun yang dikirim klien. Kita tetap tidak akan pernah
   tahu teks yang benar-benar ditekan kirim (`docs/08 §8.1` sudah mengakui "tidak ada bukti
   terkirim sungguhan"), jadi pilihannya antara **"teks resmi yang sistem keluarkan"** dan
   **"teks yang diketik klien dan diklaim final"**. Yang pertama lebih berguna saat ada keluhan:
   ia membuktikan template mana yang berlaku saat itu.
3. **Penyesuaian yang berulang seharusnya jatuh ke template.** Kalau advisor mengubah kalimat
   yang sama setiap kali, templatenya yang salah — dan sejak 2.3.4 SA bisa memperbaikinya
   sendiri. Panel yang bisa disunting menyembunyikan sinyal itu.

Yang tetap ada di panel: pratinjau penuh, nomor tujuan, **Salin Pesan** (sudah ada hari ini),
**Buka WhatsApp**, **Tandai Terkirim**, **Lewati**.

⚠️ **Konsekuensi:** log menjadi bukti *"pesan apa yang sistem keluarkan"*, bukan *"kalimat apa
yang sampai ke pelanggan"* — batas itu ditulis apa adanya di `docs/08 §8.8`, bukan dibiarkan
tampak seperti arsip percakapan.

Bila **ditolak**, tiga hal ikut wajib: teks suntingan dikirim di badan POST dan divalidasi server
(tipe + panjang maksimal), URL disusun di klien dari nomor yang **sudah ternormalisasi** di props,
dan `docs/08 §8.8` tetap menyebut kolom itu isian klien.

- **Keputusan saya:** [x] **Mengikuti rekomendasi** — panel menampilkan pratinjau yang **tidak
  bisa disunting**; `docs/08 §8.2` diperbarui.
- **Catatan:** dua hal yang terikat pada keputusan ini dan tidak boleh luput:
  1. **Tidak ada teks pesan yang datang dari klien.** POST penulis log hanya membawa
     `template_key`; `rendered_message` disusun ulang **di server** dari template yang berlaku
     saat itu. Menerima teks dari request akan menjadikan kolom audit isian pengguna
     (`.claude/rules/50` — "Menerima `status`, `total`, … dari request").
  2. **Batas log ditulis apa adanya di `docs/08 §8.8`**: ia bukti pesan yang **dikeluarkan
     sistem**, bukan kalimat yang sampai ke pelanggan. Advisor tetap bebas menyunting di kotak
     ketik WhatsApp, dan suntingan itu memang tidak terekam.

---

### Q3 — Template `no_show` diadakan atau tidak? (K2)

**Rekomendasi: diadakan — kuncinya `booking_no_show`, sehingga jumlah template menjadi 7.**

1. **`no_show` justru status yang paling butuh pesan.** Ia satu-satunya akhir yang punya jalur
   pemulihan: pelanggan lupa, dan pesanlah yang membuatnya menjadwalkan ulang. Ia juga
   melepaskan kuota (`BookingStatus::releasesQuota()`) — slotnya sudah hangus, jadi tidak ada
   kerugian mengundangnya kembali.
2. **Menghapusnya adalah kemunduran terhadap yang sudah hidup.** Teks `no_show` sudah berjalan
   sejak F1.5 lewat `config('company.wa_messages')`. `/audit-paritas` di 2.5.10 akan menandainya
   sebagai fitur yang hilang, dan pada titik itu memperbaikinya jauh lebih mahal.
3. **Biayanya satu baris seeder.** Pemetaan kunci↔status berbasis data, bukan `if` — tidak ada
   percabangan kode yang bertambah.

Enam kunci di `docs/08 §8.4` memang bukan daftar berbasis status (`booking_created` dipicu
pembuatan, `booking_reminder` dipicu manual), jadi menambah satu kunci berbasis status tidak
merusak pola apa pun.

**Daftar final — 7 template:** `booking_created` · `booking_confirmed` · `booking_in_progress` ·
`booking_completed` · `booking_cancelled` · `booking_no_show` · `booking_reminder`.

> `pending` pada `config('company.wa_messages')` hari ini = `booking_created`. Sekadar ganti nama
> saat berpindah ke tabel, bukan template baru.

- **Keputusan saya:** [x] **Mengikuti rekomendasi** — `booking_no_show` diadakan, total 7 template.
- **Catatan:** `docs/08 §8.4`, `docs/04 §4.2`, dan `config('whatsapp.templates')` sama-sama
  ditambahi barisnya.

---

### Q4 — Nama kunci ke-6, `{{ringkasan_biaya}}`, dan `{{status}}` (K1, K3)

**Rekomendasi (a): kuncinya `booking_reminder`, bukan `reminder_h1`.** `docs/04 §4.2` yang
diperbaiki.

- Dua dari tiga sumber sudah memakainya, termasuk `config/whatsapp.php` yang **sudah ter-deploy**.
- Awalan `booking_*` konsisten untuk seluruh 7 kunci; kolom `key` inilah yang dicari notifier.
- `reminder_h1` **menanam aturan bisnis di dalam nama**. "H-1" hidup di `config/booking.php`
  (`lead_time`), dan menyalinnya ke nama kunci adalah pola cacat **B2** sistem lama. Bila bengkel
  kelak mengingatkan H-2, namanya berubah jadi bohong sementara kodenya tidak peduli.

**Rekomendasi (b): `{{ringkasan_biaya}}` dikeluarkan dari daftar placeholder yang sah sampai
Tahap 11**, dan teks awal `booking_completed` di seeder tidak memakainya.

Sumbernya tabel `invoices` yang baru lahir di 2.4.1. Tiga pilihan, dan dua di antaranya buruk:

| Pilihan | Akibat |
|---------|--------|
| Dirender string kosong | Placeholder yang **sah tetapi selalu kosong**. SA menyisipkannya, pratinjau tidak menampilkan apa-apa, dan ia menyangka penyuntingnya rusak. Lebih buruk daripada ditolak — penolakan mengajari, kekosongan tidak |
| Dirender dari harga paket layanan | Angka yang **belum tentu ditagihkan**. Pesan "Total biaya: Rp 450.000" yang meleset dari invoice sungguhan adalah janji harga ke pelanggan, dan itu masalah uang, bukan masalah teks |
| **Dikeluarkan sampai F2.4** ✅ | SA yang mengetiknya mendapat pesan galat yang jelas. Menghidupkannya kelak = satu baris di `config/whatsapp.php` + satu entri di peta placeholder + memperbarui teks awal `booking_completed` |

**Rekomendasi (c): `{{status}}` tidak diadakan.** `docs/04 §4.2` yang keliru. Setiap template
sudah terikat satu pemicu, jadi `{{status}}` di dalam `booking_confirmed` selalu berbunyi
"Dikonfirmasi" — informasi nol. Lebih buruk lagi di `booking_reminder`, yang statusnya
`confirmed` sehingga pesan pengingat akan menyebut kata yang menyesatkan.

**Rekomendasi (d) — sekalian menutup akar K3: satu sumber untuk daftar placeholder.**
Hari ini nama placeholder tertulis di **empat** tempat (dua dokumen, satu config, satu peta di
`WhatsAppNotifier`) dan sudah berselisih. Yang berlaku setelah ini:

- `config('whatsapp.allowed_placeholders')` = sumber untuk **validasi**.
- Peta di implementasi notifier = sumber untuk **perenderan**.
- **Satu uji Pest memastikan kedua himpunan itu identik** — selisih seperti K3 gagal di CI, bukan
  ditemukan pelanggan.
- Kedua dokumen berhenti menyalin daftarnya; keduanya menunjuk ke config.

**Daftar final — 10 placeholder:** `nama` · `kode_booking` · `tanggal` · `jam` · `kendaraan` ·
`plat` · `paket` · `estimasi_selesai` · `alasan` · `alamat`.

- **Keputusan saya:** [x] **Mengikuti seluruh rekomendasi (a)–(d).**
- **Catatan:** `{{ringkasan_biaya}}` masuk daftar pekerjaan **F2.4** — dicatat di roadmap butir
  2.4.x agar tidak hilang.

---

### Q5 — Penyunting template: CRUD penuh atau sunting saja?

**Rekomendasi: bukan CRUD penuh. Hanya daftar 7 template + sunting (`name`, `body`,
`is_active`). Tanpa tambah, tanpa hapus.**

1. **Kolom `key` adalah yang dicari kode.** Template buatan SA punya kunci yang tidak dipicu apa
   pun — baris mati yang tampak seperti fitur. SA akan menyangka pesannya terkirim.
2. **Menghapus template mematahkan jalur kirim.** `docs/08 §8.5` memanggil
   `firstWhere('key', …)` lalu langsung membaca `->body`; template yang hilang menabrak null
   **di tengah alur ubah status** — advisor kehilangan perubahan statusnya gara-gara pesan.
3. **Yang dijanjikan R6 memang penyuntingan kata-katanya**, bukan penambahan jenis pesan.

**`is_active` dipertahankan dan diberi arti tegas: "jangan tawarkan draft untuk pemicu ini".**
Itu keinginan yang masuk akal — bengkel yang tidak pernah mengabari saat `in_progress`. Bila
templatenya nonaktif, panel menampilkan penjelasan dan **tidak merender tombol apa pun**;
notifier mengembalikan `null`, persis pola yang sudah berjalan hari ini saat pelanggan tidak
punya nomor WA (`whatsapp-panel.tsx` sudah menangani `draft === null`).

> **Tidak ada jalur cadangan diam-diam ke teks seeder.** Menonaktifkan template yang lalu
> diam-diam digantikan teks bawaan berarti tombol nonaktifnya tidak melakukan apa-apa — lebih
> membingungkan daripada crash yang dihindari.

**Isi layar `/admin/template-wa`** (URL berbahasa Indonesia, sejajar `/admin/paket-layanan`;
`docs/07 §A7` menulis `/admin/wa-template` — dokumennya disesuaikan):

- Daftar 7 baris: label, kunci (**hanya baca**), status aktif, waktu diperbarui.
- Form sunting: `name`, `body` (textarea), `is_active`.
- **Daftar placeholder yang sah tampil di sebelah penyunting**, bisa diklik untuk menyisipkan.
- **Pratinjau terender memakai satu booking contoh** — SA melihat hasil akhirnya, bukan mentahnya.
- Validasi: `body` wajib; placeholder di luar daftar ditolak dengan pesan yang **menyebut nama
  placeholder yang salah**; panjang badan template dibatasi (lihat risiko pemotongan di bawah).

**Menyunting template tidak mengubah pesan yang sudah tercatat** — itulah alasan
`rendered_message` disimpan apa adanya (`docs/04 §4.2`).

- **Keputusan saya:** [x] **Mengikuti rekomendasi** — sunting saja; `is_active` berarti "tidak
  ditawarkan"; tanpa jalur cadangan diam-diam.
- **Catatan:** roadmap 2.3.4 berbunyi "CRUD template WA" — kata CRUD diperbaiki menjadi
  "daftar + sunting".

---

### Q6 — Kartu dashboard 2.3.5 menghitung apa, dan rentangnya berapa?

**Rekomendasi: kartu menghitung dari sisi *booking*, bukan dari sisi baris draft.**

Karena Q1 memutuskan baris hanya lahir saat tombol ditekan, menghitung baris `generated` hanya
menangkap "sudah dibuka tetapi belum ditandai" — justru bukan kelalaian yang ditakutkan. Yang
ditagih risiko roadmap adalah advisor yang **tidak membuka sama sekali**.

**Definisinya:** booking yang…

1. statusnya termasuk pemicu ber-template **aktif** (`confirmed`, `in_progress`, `completed`,
   `cancelled`, `no_show`), **dan**
2. belum punya baris `whatsapp_messages` berstatus `sent` **maupun** `skipped` untuk kunci
   template status itu, **dan**
3. perubahan status terakhirnya terjadi **dalam 7 hari terakhir**.

**Butir 3 yang membuat kartunya berguna, dan ia wajib.** Tanpa rentang, seluruh booking sejak
Tahap 5 ikut terhitung — tabelnya baru lahir hari ini, jadi tidak satu pun punya baris `sent`.
Kartunya akan dibuka di angka ratusan pada hari pertama dan tidak akan pernah bisa dikosongkan.
Kartu yang mustahil dinolkan sama saja dengan tidak ada kartu.

Rentang dihitung dari **peristiwa perubahan status** (`booking_status_histories.created_at`),
bukan dari `booking_date`: yang ditagih adalah kabar yang belum disampaikan, dan itu melekat pada
peristiwanya. Pembatalan untuk servis bulan depan tetap tertangkap — dan itu justru pesan yang
paling mendesak.

**Kartunya bisa diklik**, menuju `/admin/bookings` dengan saringan baru `wa=belum`. Angka tanpa
tujuan memaksa advisor berburu satu per satu; ini menambah satu saringan ke `BookingFilters` yang
sudah ada, bukan layar baru.

⚠️ **Konsekuensi hari pertama:** booking yang statusnya berubah dalam 7 hari sebelum Tahap 10
rilis akan muncul sebagai "belum dikabari" walau advisor mungkin sudah mengabarinya lewat tombol
R6 — yang tidak mencatat apa pun. Jumlahnya kecil dan habis sendiri dalam sepekan; advisor cukup
menekan **Lewati** untuk membersihkannya. Ini disebut di catatan rilis, bukan disembunyikan.

- **Keputusan saya:** [x] **Mengikuti rekomendasi**, termasuk rentang 7 hari, saringan
  `wa=belum`, dan kartu yang bisa diklik.
- **Catatan:** bila kueri "riwayat status terakhir" terbukti berat di dashboard, jalur mundurnya
  memakai `bookings.booking_date` dalam ±7 hari — indeks `bookings(status, booking_date)` sudah
  ada sejak `2026_08_03_100010`. Ketepatannya sedikit berkurang; kegunaannya tidak.

---

### Q7 — Riwayat pesan dilihat di mana?

**Rekomendasi: dua pembaca, dan tidak ada layar `/admin/wa-log` baru.**

1. **Daftar ringkas di detail booking**, di bawah panel draft: waktu, template, pelaku, status
   (`generated`/`sent`/`skipped`), dan kutipan pesan. Inilah pembaca alaminya — pertanyaan "sudah
   dikabari belum?" selalu muncul saat menatap satu booking, bukan saat menatap tabel global.
2. **Daftar booking yang disaring `wa=belum`** dari Q6 — menjawab "siapa saja yang belum
   dikabari", yang merupakan satu-satunya pertanyaan lintas-booking yang nyata.

**Kenapa tidak ada layar log tersendiri:** tidak ada butirnya di roadmap 2.3.1–2.3.6, `docs/07
§A7` tidak menggambarkannya, dan tabel berisi **isi pesan + nomor pelanggan** yang bisa disaring
bebas adalah permukaan privasi baru yang belum ada yang meminta (`docs/08 §8.8` membatasi tabel
ini untuk audit, bukan untuk ditelusuri). Bila kebutuhannya muncul kelak, ia lahir dari keluhan
nyata dan dirancang untuk keluhan itu.

**Retensi: tidak ada penghapusan.** `.claude/rules/30` melarang menghapus baris audit, dan
tabelnya tumbuh pelan — satu baris per pesan yang benar-benar dibuka, bukan per perubahan status
(Q1). Berbeda dari `activity_log` yang tumbuh per perubahan kolom dan karena itu punya retensi 12
bulan.

- **Keputusan saya:** [x] **Mengikuti rekomendasi** — riwayat di detail booking + saringan
  `wa=belum`; tanpa layar log global; tanpa retensi.
- **Catatan:** menambah daftar riwayat ke detail booking berarti `whatsapp_messages` ikut
  di-`load` di `BookingController::show` — masuk daftar `with()` supaya tidak jadi N+1.

---

### Q8 — Pengingat H-1 ditekan dari layar mana?

**Rekomendasi: dari dua tempat — baris di A3 Jadwal Harian, dan panel di detail booking.**
Tidak ada layar baru.

- **A3 `/admin/jadwal` adalah satu-satunya layar yang sudah menjawab "siapa saja yang servis
  besok"** — ia menampilkan 11 baris slot untuk satu tanggal dengan navigasi ← hari →. Advisor
  menekan → sekali, lalu mengirim pengingat dari daftar itu. Mengirimnya dari detail booking saja
  berarti membuka delapan halaman untuk delapan pengingat, dan pekerjaan yang merepotkan adalah
  pekerjaan yang tidak dilakukan — persis risiko yang hendak ditutup.
- **Detail booking tetap punya tombolnya** agar seluruh jenis pesan berada di satu panel yang
  sama; tidak ada jenis pesan yang hanya bisa dikirim dari satu layar tertentu.

**Batasnya tegas: pengingat hanya ditawarkan untuk booking berstatus `confirmed` yang
`booking_date`-nya masih di depan.**

- `pending` **tidak**: mengirim "sampai jumpa besok" untuk booking yang belum dikonfirmasi adalah
  janji yang belum tentu ditepati bengkel.
- `cancelled`/`no_show`/`completed` **tidak**: mengingatkan servis yang sudah lewat atau batal
  adalah cacat, bukan fitur.

Alurnya sama persis dengan draft lain: `<a href>` + `router.post` yang menulis baris dengan
`template_key = booking_reminder`. Tidak ada mekanisme kedua.

> **Bukan penjadwalan.** Tidak ada cron, tidak ada antrean — H-1 otomatis tetap **Won't do**
> (`docs/08 §8.1`) sampai ada gateway. Yang dibangun hanyalah tombol di layar yang tepat.

- **Keputusan saya:** [x] **Mengikuti rekomendasi** — A3 + detail booking; `confirmed` dan
  bertanggal depan saja.
- **Catatan:** tombol di A3 menambah kolom aksi pada layar jadwal — perubahan kecil pada layar
  F1.5.5 yang sudah ada, bukan layar baru.

---

### Q9 — Bentuk interface, nasib `send()`, dan `contactReplyUrl()`

**Rekomendasi (a): `send()` tidak dibuat sekarang.** `docs/08 §8.7` menuliskannya sebagai no-op
di interface; itu kode mati.

Yang dijanjikan §8.7 — "cukup tambah implementasi baru, tanpa mengubah controller" — dijaga oleh
**adanya interface itu sendiri**, bukan oleh adanya method kosong. Karena tidak ada satu pun
pemanggil `send()` hari ini, method itu tidak memaksa siapa pun menulis kode yang siap-gateway;
ia hanya menambah satu baris yang harus dijelaskan. Saat gateway benar-benar datang, method itu
dirancang terhadap API yang nyata (kembaliannya apa? masuk antrean? gagalnya bagaimana?) alih-alih
ditebak hari ini. `docs/08 §8.7` diperbarui.

**Rekomendasi (b): `contactReplyUrl()` keluar dari `WhatsAppNotifier`.** Ia tidak punya template,
tidak punya log, dan tidak punya booking — `whatsapp_messages.booking_id` bahkan tidak nullable,
jadi ia memang tidak bisa dicatat di tabel yang sama. Menaruhnya di interface memaksa setiap
implementasi masa depan ikut membawa urusan form kontak.

**Bentuk yang dipakai** (namespace `App\Services\WhatsApp\…` sudah tertulis di komentar
`config/whatsapp.php`):

| Berkas | Tanggung jawab |
|--------|----------------|
| `App\Services\WhatsApp\WhatsAppNotifier` (interface) | `draft(Booking $booking, string $templateKey): ?WhatsAppDraft` — **hanya merender & menyusun tautan, tanpa menyentuh database** |
| `App\Services\WhatsApp\ClickToChatNotifier` | Implementasi klik-to-chat (`docs/08 §8.5`) |
| `App\Services\WhatsApp\WhatsAppMessageService` | Menulis log: `record()`, `markSent()`, `markSkipped()` — dalam transaksi, pelaku **diterima sebagai parameter** karena service dilarang menyentuh `auth()` (`.claude/rules/10`) |
| `App\Support\WhatsAppLink` | Satu-satunya tempat `wa.me` disusun: basis URL, `rawurlencode`, batas panjang. Dipakai notifier **dan** balasan pesan kontak |
| `App\Support\WhatsAppDraft` | Objek nilai `readonly` (`url`, `message`, `phone_display`, `template_key`) menggantikan bentuk array hari ini |

Pengikatan di `AppServiceProvider` berdasarkan `config('whatsapp.driver')` (`docs/08 §8.7`).
Logging dipisahkan dari notifier supaya `FonnteNotifier` kelak tidak perlu menyalin ulang
pencatatannya.

⚠️ Memindahkan `App\Services\WhatsAppNotifier` menyentuh `Admin\BookingController`,
`Admin\ContactMessageController`, `BookingStatusTest`, dan `ContentCrudTest` — kecil, tetapi harus
masuk daftar pekerjaan supaya tidak dikira sekadar ganti nama berkas.

- **Keputusan saya:** [x] **Mengikuti seluruh rekomendasi (a)–(b) beserta bentuk berkasnya.**
- **Catatan:** —

---

## Risiko

⚠️ **Potensi masalah: dua sumber teks pesan hidup bersamaan** → `config('company.wa_messages')`
tetap ada setelah tabel `whatsapp_templates` lahir, dan itu persis pola cacat **B2** sistem lama
→ `wa_messages` **dihapus** dari `config/company.php` pada tugas yang sama; isinya berpindah
menjadi `WhatsAppTemplateSeeder`. Tidak ada masa transisi di mana keduanya dibaca.

⚠️ **Potensi masalah: seeder menimpa suntingan Super Admin** → aturan seeder idempoten
(`.claude/rules/30`) biasanya berarti `updateOrCreate`; dipakai di sini, setiap `db:seed`
mengembalikan seluruh badan template ke teks bawaan dan menghapus hasil kerja SA tanpa jejak →
`WhatsAppTemplateSeeder` memakai **`firstOrCreate`**: baris yang belum ada dibuat, baris yang
sudah ada **tidak pernah disentuh**. Tetap idempoten, tetapi tidak merusak.

⚠️ **Potensi masalah: pemotongan pesan diam-diam** → `mb_substr(…, 0, 1000)` memotong tanpa
memberi tahu siapa pun. Tidak pernah terjadi selama teksnya dari `config`; begitu SA bisa
mengetik sendiri, pesan 1.200 karakter terkirim tanpa penutup "— Chery Arta" → panjang **badan
template** divalidasi saat menyimpan dengan menyisakan ruang untuk placeholder terpanjang;
penyunting menampilkan penghitung karakter **dan** pratinjau terender (Q5).

⚠️ **Potensi masalah: kartu dashboard lahir dengan tunggakan palsu** → booking yang sudah
dikabari lewat tombol R6 tidak punya catatan apa pun, jadi ikut terhitung "belum dikabari" pada
hari pertama → dibatasi rentang 7 hari (Q6) sehingga habis sendiri sepekan; tombol **Lewati**
membersihkan sisanya.

⚠️ **Potensi masalah: `whatsapp_messages.booking_id` tidak nullable** → balasan pesan kontak
(F2.2.2) tidak bisa dicatat di tabel yang sama. Ditegaskan sebagai **di luar lingkup**, bukan
celah audit yang terlewat (Q9).

---

## Perubahan dokumen yang harus ikut dilakukan

Konsekuensi DoD #12. Dikumpulkan di sini supaya PRD tinggal menyalin.

| Dokumen | Perubahan |
|---------|-----------|
| `docs/08 §8.2` | Baris log ditulis saat tombol ditekan, bukan saat status berubah (Q1) · pesan **tidak** bisa disunting di panel; kalimat "bisa disunting sebelum dikirim" dicabut (Q2) |
| `docs/08 §8.4` | Tambah `booking_no_show` (Q3) · buang `{{ringkasan_biaya}}` sampai F2.4 (Q4) · berhenti menyalin daftar placeholder, tunjuk `config/whatsapp.php` |
| `docs/08 §8.5` | Tautan dibuka lewat `<a href>`, bukan `window.open()` setelah menunggu respons (Q1) · `draft()` tidak lagi menulis ke database |
| `docs/08 §8.7` | `send()` tidak ada di interface sampai gateway benar-benar dipilih (Q9) |
| `docs/08 §8.8` | Batas log dinyatakan apa adanya: bukti pesan yang **dikeluarkan sistem**, bukan yang sampai ke pelanggan (Q2) |
| `docs/04 §4.2` | `reminder_h1` → `booking_reminder` (Q4a) · tambah `booking_no_show` (Q3) · buang `{{status}}` (Q4c) |
| `docs/07 §A7` | `/admin/wa-template` → `/admin/template-wa` · "CRUD" → "daftar + sunting" (Q5) · riwayat pesan di detail booking (Q7) · tombol pengingat di A3 (Q8) |
| `docs/07 §A3` | Baris jadwal mendapat aksi kirim pengingat (Q8) |
| `docs/07 §A1` | Kartu "belum dikabari" beserta definisinya (Q6) |
| `docs/10` | Butir 2.3.4 "CRUD" diperbaiki · `{{ringkasan_biaya}}` dicatat sebagai pekerjaan F2.4 |

---

## Log Keputusan

| # | Pertanyaan | Keputusan | Cara diambil |
|---|------------|-----------|--------------|
| Q1 | Kapan baris log ditulis | Saat tombol "Buka WhatsApp" ditekan | disetujui |
| Q2 | Pesan bisa disunting di panel | Tidak — pratinjau mati; teks tidak pernah datang dari klien | mengikuti rekomendasi |
| Q3 | Template `no_show` | Diadakan (`booking_no_show`) — 7 template | mengikuti rekomendasi |
| Q4 | Kunci ke-6 & placeholder | `booking_reminder` · `{{ringkasan_biaya}}` ditunda ke F2.4 · `{{status}}` dibuang · config jadi satu sumber | mengikuti rekomendasi |
| Q5 | Penyunting template | Daftar + sunting saja; `is_active` = "tidak ditawarkan" | mengikuti rekomendasi |
| Q6 | Kartu dashboard | Dihitung dari booking, rentang 7 hari, bisa diklik ke `wa=belum` | mengikuti rekomendasi |
| Q7 | Riwayat pesan | Detail booking + saringan `wa=belum`; tanpa layar log global | mengikuti rekomendasi |
| Q8 | Pengingat H-1 | A3 Jadwal + detail booking; `confirmed` bertanggal depan saja | mengikuti rekomendasi |
| Q9 | Bentuk interface | Tanpa `send()`; `contactReplyUrl()` pindah ke `WhatsAppLink` | mengikuti rekomendasi |
