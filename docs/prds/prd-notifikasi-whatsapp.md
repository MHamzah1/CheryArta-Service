# PRD: Notifikasi WhatsApp Penuh (A7)

**Status:** ✅ **Terimplementasi 5 Agustus 2026.** Catatan penyelesaian beserta sepuluh
penyimpangan yang disengaja ada di
[roadmap Tahap 10](../10-roadmap-implementasi.md#tahap-10--notifikasi-whatsapp-penuh--f23--selesai).
Pertanyaan Terbuka #2 sudah ditetapkan — lihat bagiannya di bawah.

**Tahap 10 · `F2.3` · butir 2.3.1–2.3.6**
Sumber keputusan: [`docs/grills/grill-notifikasi-whatsapp.md`](../grills/grill-notifikasi-whatsapp.md)
(9 pertanyaan, seluruhnya terjawab, tidak ada rekomendasi yang ditolak).

## Masalah

Sejak F1.5.7 bengkel punya tombol "Chat via WhatsApp" di detail booking — jalur sementara
keputusan **R6**. Ia berfungsi, tetapi menyisakan tiga lubang yang nyata:

1. **Sistem tidak tahu pelanggan mana yang sudah dikabari.** Tidak ada satu pun catatan bahwa
   sebuah pesan pernah dibuat. Advisor yang mengubah status lalu lupa membuka WhatsApp tidak
   meninggalkan jejak, dan tidak ada yang bisa menagihnya. Ini tercatat sebagai risiko hidup di
   [roadmap §Risiko](../10-roadmap-implementasi.md#risiko) — *"Notifikasi WA bergantung
   kedisiplinan advisor menekan kirim"* — dan sengaja dibiarkan terbuka sepanjang Tahap 8–9.
2. **Teks pesan hanya bisa diubah programmer.** Kalimatnya tertanam di
   `config('company.wa_messages')`. Pemilik bengkel yang ingin mengubah satu kata harus meminta
   deploy. Ini menyalahi janji A7 di [`docs/07 §A7`](../07-modul-admin.md): template dapat
   disunting Super Admin tanpa menyentuh kode.
3. **Tidak ada pengingat H-1.** Slot yang hangus karena pelanggan lupa adalah kuota yang tidak
   bisa dijual ulang — `no_show` melepaskan kuotanya *setelah* jamnya lewat, jadi kerugiannya
   sudah terjadi. Sistem lama pun tidak punya ini.

Yang mengalaminya: **service advisor** (setiap hari, saat mengubah status), **super admin**
(tidak bisa memperbaiki kalimat yang salah), dan **pelanggan** (tidak menerima kabar).

Satu cacat sistem lama yang relevan dan tidak boleh terulang: nomor telepon disimpan apa adanya
lengkap dengan tanda hubung hasil format otomatis (`0812-3456-7890`), yang **tidak bisa dipakai
`wa.me`** ([`docs/08 §8.3`](../08-notifikasi-whatsapp.md#83-normalisasi-nomor)). Normalisasi
`62…` sudah berjalan sejak F1.2; PRD ini tidak boleh membuka jalur baru yang melewatinya.

## Tujuan

Setiap perubahan status booking menghasilkan pesan siap kirim yang **tercatat**, teksnya bisa
diperbaiki sendiri oleh Super Admin tanpa deploy, dan dashboard menunjukkan dengan jujur berapa
pelanggan yang **belum** dikabari — sehingga kelalaian menjadi terlihat, bukan tak terdeteksi.

## Latar

**Keadaan sekarang.** `App\Services\WhatsAppNotifier` menyusun satu tautan `wa.me` per booking
dari `config('company.wa_messages')` (6 teks berkunci status), dikirim ke React sebagai prop
`whatsapp` dan dirender `components/admin/whatsapp-panel.tsx`. Tidak ada tabel, tidak ada log,
tidak ada layar template. Kelasnya sengaja sudah dinamai sesuai
[`docs/03 §3.6`](../03-arsitektur-teknis.md) supaya tahap ini cukup mengubah isinya.

**Kenapa sekarang.** Delapan tahap sebelumnya mendahulukan panel yang dipakai setiap hari
(booking, jadwal, dashboard, laporan, konten, akun). WhatsApp punya jalur sementara yang
berfungsi sehingga boleh menyusul — tetapi jalur itu sudah dipakai sepanjang Tahap 6–9 dan
utangnya menumpuk. Satu ketergantungan menunggu di sini: **kartu "draft WA belum dikirim" di
dashboard A1** sengaja tidak dibangun di Tahap 8 karena tabelnya belum ada
([roadmap, butir 2.3.5](../10-roadmap-implementasi.md#tahap-10--notifikasi-whatsapp-penuh--f23)).

**Posisi.** Tahap 10 dari 12. Modul **A7**, menyentuh **A1** (kartu dashboard), **A2** (panel &
riwayat di detail booking, saringan baru), **A3** (tombol pengingat), **A13** (menu Template WA),
**A14** (baris matriks). Sesudah ini tersisa Tahap 11 (Invoice) dan Tahap 12 (Pengerasan).

## Selesai Bila

- [ ] Super Admin membuka `/admin/template-wa`, mengubah satu kalimat pada template
      **Dikonfirmasi**, menyimpan — lalu membuka satu booking berstatus `confirmed` dan melihat
      kalimat barunya di panel WhatsApp.
- [ ] Advisor menekan **Buka WhatsApp** di detail booking: WhatsApp terbuka dengan teks terisi,
      **dan** satu baris muncul di riwayat pesan booking itu berstatus "Sudah dibuka".
- [ ] Advisor menekan **Tandai Terkirim**; baris yang sama berubah status dan booking itu hilang
      dari hitungan kartu dashboard.
- [ ] Advisor menekan **Lewati** pada booking lain; booking itu juga hilang dari hitungan kartu,
      dengan status "Dilewati" di riwayatnya.
- [ ] Kartu **"Belum dikabari"** di `/admin/dashboard` menunjukkan angka yang benar, dan
      mengkliknya membuka `/admin/bookings` yang sudah tersaring hanya berisi booking tersebut.
- [ ] Super Admin menonaktifkan template **Sedang Dikerjakan**; booking berstatus `in_progress`
      menampilkan penjelasan di panel dan **tidak** menampilkan tombol apa pun — bukan galat.
- [ ] Menyimpan template berisi `{{harga}}` ditolak dengan pesan yang **menyebut** `{{harga}}`;
      menyimpan template berisi `{{ringkasan_biaya}}` juga ditolak (belum ada sampai Tahap 11).
- [ ] `/admin/jadwal` pada tanggal besok menampilkan tombol pengingat pada booking berstatus
      **Dikonfirmasi**, dan **tidak** menampilkannya pada booking `pending`, `cancelled`, atau
      pada tanggal yang sudah lewat.
- [ ] Advisor membuka `/admin/template-wa` → ditolak 403; menu "Template WA" tidak dirender di
      sidebar-nya.
- [ ] Pelanggan tanpa nomor WhatsApp: panel tetap menampilkan penjelasan lama, tanpa tombol dan
      tanpa galat.
- [ ] `php artisan migrate:fresh --seed` menghasilkan **7 template** aktif; menjalankan
      `php artisan db:seed` lagi sesudah Super Admin menyunting satu template **tidak** mengubah
      suntingannya.
- [ ] `grep -r "wa_messages"` tidak menghasilkan apa pun di luar `docs/`.

## Lingkup

- Dua tabel baru: `whatsapp_templates` (7 baris) dan `whatsapp_messages` (log klik-to-chat).
- `WhatsAppTemplateSeeder` idempoten yang **tidak menimpa** suntingan Super Admin.
- Interface `WhatsAppNotifier` + implementasi `ClickToChatNotifier`, menggantikan kelas konkret
  `App\Services\WhatsAppNotifier` yang ada sekarang.
- Panel WhatsApp baru di detail booking: pratinjau, **Buka WhatsApp**, **Tandai Terkirim**,
  **Lewati**, penanda "belum dikabari" yang menonjol, dan riwayat pesan booking itu.
- Layar `/admin/template-wa` (Super Admin): daftar 7 template + sunting, dengan validasi
  placeholder dan pratinjau terender.
- Kartu "Belum dikabari" di dashboard A1 + saringan `wa=belum` di daftar booking A2.
- Tombol pengingat H-1 di `/admin/jadwal` dan di detail booking.
- Baris matriks template WA di `AccessMatrixTest` (butir 2.3.6).
- Pembaruan `docs/04`, `docs/07`, `docs/08`, `docs/10` sesuai keputusan grill.

## Bukan Lingkup

- **Gateway WhatsApp apa pun** (Fonnte, Cloud API) dan method `send()` di interface. Jalur
  peningkatannya tetap terbuka lewat pengikatan `config('whatsapp.driver')`, tetapi tidak ada
  kode yang ditulis untuknya sekarang (keputusan grill Q9).
- **Pengiriman otomatis dan penjadwalan.** Tidak ada cron, tidak ada Job antrean. Pengingat H-1
  tetap tombol yang ditekan manusia — H-1 otomatis tetap *Won't do*
  ([`docs/08 §8.1`](../08-notifikasi-whatsapp.md#81-keputusan--batasannya)).
- **Layar log pesan global** (`/admin/wa-log`). Riwayat hanya dibaca dari detail booking dan dari
  daftar booking tersaring (keputusan grill Q7).
- **Penyuntingan pesan di panel.** Advisor menyunting di kotak ketik WhatsApp bila perlu; teks
  tidak pernah datang dari klien (keputusan grill Q2).
- **Menambah atau menghapus template.** Kuncinya ditentukan kode; Super Admin menyunting isinya
  (keputusan grill Q5).
- **`{{ringkasan_biaya}}`.** Sumbernya tabel `invoices` yang baru lahir di Tahap 11
  (keputusan grill Q4).
- **Balasan pesan kontak (F2.2.2) tidak ikut dicatat** di `whatsapp_messages` — kolom
  `booking_id` tidak nullable dan pesan kontak tidak punya booking. Tautannya tetap ada, hanya
  pindah rumah ke `App\Support\WhatsAppLink`.
- **Retensi/penghapusan `whatsapp_messages`.** Tabel audit tidak dihapus
  ([`.claude/rules/30`](../../.claude/rules/30-database.md)).

## Pengguna & Kebutuhannya

Menyentuh `service_advisor` dan `super_admin`. **Tidak** menyentuh `customer` maupun pengunjung
publik — tidak ada satu pun layar publik atau layar customer yang berubah.

- Sebagai **service advisor**, saya ingin melihat pesan siap kirim langsung setelah mengubah
  status, supaya mengabari pelanggan tidak menuntut mengetik ulang apa pun.
- Sebagai **service advisor**, saya ingin menandai pesan yang sudah saya kirim, supaya saya tidak
  mengirim dua kali dan rekan saya tahu pelanggan itu sudah dihubungi.
- Sebagai **service advisor**, saya ingin menandai **Lewati** pada pelanggan yang tidak perlu
  dikabari, supaya daftar tunggakan saya mencerminkan keadaan sebenarnya.
- Sebagai **service advisor**, saya ingin mengirim pengingat untuk seluruh booking besok dari
  satu layar, supaya delapan pengingat tidak berarti membuka delapan halaman.
- Sebagai **super admin**, saya ingin memperbaiki kalimat pesan sendiri, supaya salah tulis
  tidak menunggu jadwal deploy.
- Sebagai **super admin**, saya ingin melihat berapa pelanggan yang belum dikabari begitu membuka
  dashboard, supaya kelalaian ketahuan di hari yang sama, bukan lewat keluhan pelanggan.

## Kebutuhan Fungsional

### Data

1. Tabel **`whatsapp_templates`** sesuai [`docs/04 §4.2`](../04-skema-database.md): `key`
   (varchar 50, **unik**), `name` (varchar 100), `body` (text), `is_active` (boolean, default
   true), timestamps.
2. Tabel **`whatsapp_messages`** sesuai `docs/04 §4.2`: `booking_id` (FK, cascade),
   `template_key` (varchar 50), `recipient_phone` (varchar 20, **ternormalisasi**),
   `rendered_message` (text), `generated_by` (FK → `users`), `status` (varchar 15),
   `sent_at` (timestamp NULL), timestamps. Index pada `(booking_id, template_key)` dan pada
   `(status, created_at)` — keduanya dipakai kartu dashboard dan saringan `wa=belum`.
3. **Tujuh template** di-seed: `booking_created`, `booking_confirmed`, `booking_in_progress`,
   `booking_completed`, `booking_cancelled`, `booking_no_show`, `booking_reminder`. Teks awalnya
   dipindahkan dari `config('company.wa_messages')` yang sudah berjalan, disesuaikan dengan
   `docs/08 §8.4`.
4. **`WhatsAppTemplateSeeder` memakai `firstOrCreate`, bukan `updateOrCreate`.** Baris yang belum
   ada dibuat; baris yang sudah ada tidak pernah disentuh — kalau tidak, setiap `db:seed`
   menghapus hasil kerja Super Admin tanpa jejak.
5. **`config('company.wa_messages')` dihapus** pada tugas yang sama. Tidak ada masa transisi di
   mana dua sumber teks dibaca bersamaan (cacat **B2**).
6. Enum **`WhatsAppMessageStatus`**: `generated` | `sent` | `skipped`, dengan label Indonesia
   ("Sudah dibuka", "Terkirim", "Dilewati").
7. Enum **`WhatsAppTemplateKey`** memuat ketujuh kunci beserta pemetaan dari `BookingStatus`.
   Karena itu daftar `config('whatsapp.templates')` **dihapus** — satu himpunan kunci, satu
   tempat.
8. **`{{ringkasan_biaya}}` dikeluarkan** dari `config('whatsapp.allowed_placeholders')`; daftar
   yang berlaku 10 nama: `nama`, `kode_booking`, `tanggal`, `jam`, `kendaraan`, `plat`, `paket`,
   `estimasi_selesai`, `alasan`, `alamat`.

### Aturan Bisnis

9. **Baris `whatsapp_messages` ditulis hanya ketika advisor menekan "Buka WhatsApp"** — bukan
   saat status berubah. Pratinjau di panel tidak menyentuh database.
10. Pesan yang dicatat **disusun ulang di server** dari template yang berlaku saat itu. Request
    hanya membawa `template_key`; **tidak ada teks pesan yang datang dari klien.**
11. `rendered_message` dan `recipient_phone` disimpan apa adanya dan **tidak pernah dihitung
    ulang** saat dibaca — menyunting template tidak mengubah pesan yang sudah tercatat.
12. Perpindahan status pesan: `generated → sent` (mengisi `sent_at`) atau `generated → skipped`.
    Tidak ada perpindahan lain.
13. **Template nonaktif berarti "jangan tawarkan draft untuk pemicu ini".** Notifier
    mengembalikan `null`; panel menampilkan penjelasan dan tidak merender tombol. **Tidak ada
    jalur cadangan diam-diam** ke teks seeder — cadangan diam-diam membuat tombol nonaktifnya
    tidak melakukan apa-apa.
14. Panel juga mengembalikan `null` bila pelanggan tidak punya `phone_wa` — perilaku yang sudah
    berjalan hari ini, dipertahankan.
15. **Kartu dashboard menghitung booking, bukan baris draft.** Sebuah booking terhitung "belum
    dikabari" bila: statusnya `confirmed`/`in_progress`/`completed`/`cancelled`/`no_show`
    **dan** templatenya aktif **dan** belum ada baris `sent` maupun `skipped` untuk kunci
    template status itu **dan** perubahan status terakhirnya terjadi **dalam 7 hari terakhir**.
16. Batas 7 hari itu wajib: tanpanya seluruh booking sejak Tahap 5 ikut terhitung — tabelnya baru
    lahir sehingga tak satu pun punya baris `sent` — dan kartunya lahir di angka ratusan yang
    tidak akan pernah bisa dinolkan.
17. **Pengingat hanya ditawarkan untuk booking berstatus `confirmed` yang tanggalnya masih di
    depan.** `pending` ditolak (janji yang belum tentu ditepati bengkel); `cancelled`, `no_show`,
    `completed`, dan tanggal lampau ditolak.
18. Nomor tujuan **selalu** bentuk ternormalisasi `62…` dari `users.phone_wa`. Tautan disusun
    dengan `rawurlencode` dan dipotong pada `config('whatsapp.max_message_length')`.
19. **Panjang badan template divalidasi saat menyimpan**, menyisakan ruang untuk placeholder
    terpanjang, supaya pemotongan 1.000 karakter tidak pernah memakan penutup pesan.
20. Placeholder di luar daftar yang sah **ditolak saat menyimpan**, dengan pesan yang menyebut
    nama placeholder yang salah.

### Hak Akses

21. **Panel draft, Tandai Terkirim, Lewati, dan pengingat**: Super Admin **dan** service advisor,
    dijaga `BookingPolicy::view` yang sudah ada — siapa pun yang boleh membuka booking itu boleh
    mengabarinya.
22. **`/admin/template-wa`: Super Admin saja.** Grup rute `role:super_admin` **dan**
    `WhatsAppTemplatePolicy` (turunan `SuperAdminOnlyPolicy` yang sudah ada) — middleware tidak
    menggantikan Policy ([`docs/09 §9.3`](../09-keamanan-hak-akses.md)).
23. Menu "Template WA" **tidak dirender** untuk advisor, dan URL yang diketik tetap ditolak
    server.
24. `generated_by` diisi dari pengguna yang sedang masuk, **diterima service sebagai parameter** —
    service dilarang menyentuh `auth()` ([`.claude/rules/10`](../../.claude/rules/10-backend-laravel.md)).
25. Customer dan tamu ditolak pada seluruh rute baru; barisnya ditambahkan ke `AccessMatrixTest`.

### Antarmuka

26. Panel WhatsApp di detail booking: nomor tujuan, pratinjau pesan **tidak bisa disunting**,
    tombol **Buka WhatsApp** (`<a href>` sungguhan), **Salin Pesan** (sudah ada), **Tandai
    Terkirim**, **Lewati**.
27. **Tombol pembuka wajib `<a href>`, bukan `window.open()` setelah menunggu respons** —
    peramban memblokir jendela yang dibuka di luar tumpukan gestur pengguna. Penekanan yang sama
    memicu `router.post` yang menulis barisnya.
28. **Penanda "Belum dikabari" yang menonjol** pada detail booking selama belum ada baris `sent`
    atau `skipped` untuk status berjalan.
29. Riwayat pesan booking itu ditampilkan di bawah panel: waktu, template, pelaku, status,
    kutipan pesan.
30. `/admin/template-wa` menampilkan 7 baris (label, kunci **hanya baca**, aktif, diperbarui).
    Form sunting berisi `name`, `body`, `is_active`, **daftar placeholder yang bisa diklik untuk
    menyisipkan**, penghitung karakter, dan **pratinjau terender memakai satu booking contoh**.
31. Kartu **"Belum dikabari"** di dashboard bisa diklik menuju `/admin/bookings?wa=belum`.
    Saringan itu ditambahkan ke `BookingFilters` dan ke bilah saringan A2.
32. `/admin/jadwal` menambah aksi kirim pengingat pada baris booking yang memenuhi syarat butir
    17.
33. Konfirmasi **Lewati** memakai `ConfirmDialog` dengan teks tombol yang menyebut aksinya
    ("Lewati Pesan Ini"), bukan "OK".

## Kebutuhan Non-Fungsional

- **Keamanan.** `WhatsAppTemplatePolicy` untuk template; `BookingPolicy::view` untuk seluruh aksi
  pesan. Seluruh masukan lewat Form Request — tidak ada `$request->all()`. Booking diambil lewat
  route model binding + Policy, sehingga mengganti ID di URL tidak membuka booking orang lain.
  **Tidak ada teks pesan yang diterima dari request** (butir 10) — kolom audit tidak boleh
  menjadi isian pengguna ([`.claude/rules/50`](../../.claude/rules/50-keamanan.md)). Pratinjau
  dirender sebagai teks biasa; `dangerouslySetInnerHTML` tetap dilarang (temuan S5).
- **Privasi.** Nomor WhatsApp pelanggan hanya muncul di layar staf yang masuk. Tidak ada rute
  publik baru. `whatsapp_messages` dipakai untuk audit, bukan untuk ditelusuri bebas — itu
  sebabnya tidak ada layar log global ([`docs/08 §8.8`](../08-notifikasi-whatsapp.md#88-aturan-privasi)).
- **Waktu.** `sent_at` dan rentang 7 hari dihitung pada `Asia/Jakarta`
  (`config('booking.timezone')`), bukan UTC (temuan B7). Tanggal di dalam pesan memakai format
  Indonesia yang sudah ada.
- **Uang.** Tidak berlaku — `{{ringkasan_biaya}}` sengaja di luar lingkup sampai Tahap 11.
- **Konfigurasi.** Batas panjang pesan dan daftar placeholder di `config/whatsapp.php`. Aturan
  H-1 pengingat memakai nilai yang sudah ada di `config/booking.php` — **tidak** disalin ke nama
  kunci template maupun ke React (cacat **B2**; itulah alasan kuncinya `booking_reminder`, bukan
  `reminder_h1`).
- **Antarmuka.** Bahasa Indonesia; istilah **Booking**, **Servis**, **Kendaraan**, **Paket
  Layanan**. Warna hanya dari design token; penanda "belum dikabari" memakai `danger-500` secara
  hemat dan **selalu disertai teks**, tidak pernah warna saja. Benar pada 360/768/1280; tabel
  riwayat dan daftar template dibungkus `overflow-x-auto`.
- **Kinerja.** Riwayat pesan ikut `with()` di `BookingController::show` agar tidak N+1. Daftar
  template 7 baris — tidak dipaginasi, dan itu satu-satunya pengecualian karena jumlahnya
  ditentukan kode. Kartu dashboard dihitung satu kueri agregat, memakai index butir 2 dan index
  `bookings(status, booking_date)` yang sudah ada sejak `2026_08_03_100010`.
- **Aksesibilitas.** Tombol ikon ber-`aria-label`; ikon dekoratif `aria-hidden`. Pesan galat
  placeholder tampil inline di bawah field dengan fokus ke field yang salah.

## Keputusan yang Sudah Disetujui

**Terkunci. Jangan dibuka ulang.**

1. **Baris `whatsapp_messages` ditulis saat advisor menekan "Buka WhatsApp"**, bukan saat status
   berubah. *(Grill Q1 — menyimpang dari `docs/08 §8.2`.)*
2. **Tombolnya `<a href>` sungguhan**, bukan `window.open()` sesudah menunggu respons.
   *(Grill Q1 — menyimpang dari `docs/08 §8.5`.)*
3. **Pesan tidak bisa disunting di panel**; teks tidak pernah datang dari klien.
   *(Grill Q2 — menyimpang dari `docs/08 §8.2`.)*
4. **Tujuh template**, termasuk `booking_no_show`. *(Grill Q3.)*
5. **Kunci ke-6 bernama `booking_reminder`**, bukan `reminder_h1`. *(Grill Q4a — `docs/04 §4.2`
   yang diperbaiki.)*
6. **`{{ringkasan_biaya}}` ditunda ke Tahap 11**, bukan dirender kosong atau diambil dari harga
   paket. *(Grill Q4b.)*
7. **`{{status}}` tidak diadakan.** *(Grill Q4c — `docs/04 §4.2` yang diperbaiki.)*
8. **Satu sumber daftar placeholder**: config untuk validasi, peta notifier untuk perenderan,
   satu uji Pest menjaga keduanya identik. *(Grill Q4d.)*
9. **Layar template: daftar + sunting saja.** Tanpa tambah, tanpa hapus. *(Grill Q5 — roadmap
   2.3.4 menulis "CRUD"; kata itu diperbaiki.)*
10. **`is_active = false` berarti "tidak ditawarkan"**, tanpa jalur cadangan diam-diam.
    *(Grill Q5.)*
11. **Kartu dashboard dihitung dari sisi booking, rentang 7 hari, bisa diklik ke `wa=belum`.**
    *(Grill Q6.)*
12. **Riwayat dibaca di detail booking + daftar tersaring**; tanpa layar log global; tanpa
    retensi. *(Grill Q7.)*
13. **Pengingat ditekan dari `/admin/jadwal` dan detail booking**, hanya untuk `confirmed`
    bertanggal depan. *(Grill Q8.)*
14. **`send()` tidak dibuat**; `contactReplyUrl()` pindah ke `App\Support\WhatsAppLink`.
    *(Grill Q9 — menyimpang dari `docs/08 §8.7`.)*
15. **Seeder memakai `firstOrCreate`**, tidak menimpa suntingan Super Admin. *(Grill §Risiko.)*
16. **`config('company.wa_messages')` dihapus** tanpa masa transisi. *(Grill §Risiko.)*

Berlaku juga tanpa perlu diulang: keputusan final #4 (klik-to-chat manual, tanpa gateway, tanpa
email), #5 (nomor WA ternormalisasi `62…`), #6 (dua role internal), #7 (Bahasa Indonesia,
`Asia/Jakarta`) di [`.claude/rules/00`](../../.claude/rules/00-konteks-proyek.md).

## Pertanyaan Terbuka

1. **Teks awal `booking_created` dan `booking_reminder` belum pernah dipakai sungguhan.** Lima
   teks lain sudah berjalan sejak F1.5 lewat `config('company.wa_messages')` dan terbukti
   terbaca; dua ini disalin dari contoh di `docs/08 §8.4` dan belum pernah dibaca pelanggan.
   Bukan penghalang — Super Admin bisa memperbaikinya sendiri sejak hari pertama, dan itu memang
   inti tahap ini.
2. ~~**Apakah `booking_created` perlu tombolnya sendiri di panel?**~~ **Ditetapkan sesuai usul
   kerja.** Panel booking berstatus `pending` menawarkan draft `booking_created` seperti status
   lain, tetapi kuncinya ditandai **tidak** `isTracked()` sehingga tidak pernah muncul di kartu
   dashboard maupun saringan `wa=belum` — mengabari booking yang belum dikonfirmasi bersifat
   sukarela. Penandanya ada di `App\Enums\WhatsAppTemplateKey::isTracked()`, satu tempat yang
   sama dengan `booking_reminder`.

## Dampak ke Rancangan `docs/`

**Ada.** Enam berkas ikut diperbarui — lima di antaranya karena PRD ini **menyimpang** dari yang
tertulis, dan penyimpangannya sudah disetujui saat grill (lihat Keputusan 1, 2, 3, 5, 7, 9, 14).

| Berkas | Bagian | Perubahan |
|--------|--------|-----------|
| `docs/08-notifikasi-whatsapp.md` | §8.2 | Baris log ditulis saat tombol ditekan, bukan saat status berubah · kalimat "pesan bisa disunting sebelum dikirim" **dicabut** · diagram urutan disesuaikan |
| | §8.4 | Tambah `booking_no_show` (7 template) · buang `{{ringkasan_biaya}}` sampai Tahap 11 · berhenti menyalin daftar placeholder, tunjuk `config/whatsapp.php` |
| | §8.5 | Tautan dibuka lewat `<a href>`, bukan `window.open()` · contoh `draft()` tidak lagi menulis ke database |
| | §8.7 | `send()` tidak ada di interface sampai gateway benar-benar dipilih |
| | §8.8 | Batas log dinyatakan apa adanya: bukti pesan yang **dikeluarkan sistem**, bukan kalimat yang sampai ke pelanggan |
| `docs/04-skema-database.md` | §4.2 `whatsapp_templates` | `reminder_h1` → `booking_reminder` · tambah `booking_no_show` · buang `{{status}}` dari daftar placeholder |
| `docs/07-modul-admin.md` | §A7 | `/admin/wa-template` → `/admin/template-wa` · "CRUD" → "daftar + sunting" · riwayat pesan di detail booking · status ⚠️ → ✅ |
| | §A1 | Kartu "Belum dikabari" beserta definisi hitungannya |
| | §A3 | Baris jadwal mendapat aksi kirim pengingat |
| | §A13 | Menu "Template WA" pada grup Sistem |
| `docs/10-roadmap-implementasi.md` | Tahap 10 | Butir 2.3.4 "CRUD" diperbaiki · `{{ringkasan_biaya}}` dicatat sebagai pekerjaan Tahap 11 · status tahap ditutup dengan catatan penyimpangan |
| | Status modul admin | A7 ⚠️ → ✅ · A13 baris Template WA ✅ |
| | §Risiko | Baris "Notifikasi WA bergantung kedisiplinan advisor" diperbarui: penangkalnya kini terpasang |
| `docs/09-keamanan-hak-akses.md` | §9.3 matriks | Baris baru: "Menyunting template WhatsApp" — SA ✅, advisor ❌, customer ❌, tamu ❌ |

## Modul yang Tersentuh

| Berkas | Baru / Ubah | Yang berubah |
|--------|-------------|--------------|
| `database/migrations/…_create_whatsapp_templates_table.php` | **Baru** | Tabel template; `key` unik |
| `database/migrations/…_create_whatsapp_messages_table.php` | **Baru** | Tabel log + index `(booking_id, template_key)` dan `(status, created_at)` |
| `database/seeders/WhatsAppTemplateSeeder.php` | **Baru** | 7 template, `firstOrCreate` |
| `database/seeders/DatabaseSeeder.php` | Ubah | Daftarkan seeder baru |
| `database/factories/WhatsAppTemplateFactory.php` | **Baru** | Factory untuk uji |
| `database/factories/WhatsAppMessageFactory.php` | **Baru** | Factory untuk uji |
| `app/Models/WhatsAppTemplate.php` | **Baru** | `$fillable`, cast `is_active`, scope `active` |
| `app/Models/WhatsAppMessage.php` | **Baru** | `$fillable`, cast status & `sent_at`, relasi `booking`/`generatedBy` |
| `app/Models/Booking.php` | Ubah | Relasi `whatsappMessages()` |
| `app/Enums/WhatsAppMessageStatus.php` | **Baru** | `generated`/`sent`/`skipped` + label Indonesia |
| `app/Enums/WhatsAppTemplateKey.php` | **Baru** | 7 kunci + pemetaan dari `BookingStatus` |
| `app/Services/WhatsApp/WhatsAppNotifier.php` | **Baru** | Interface `draft()` — tanpa `send()` |
| `app/Services/WhatsApp/ClickToChatNotifier.php` | **Baru** | Implementasi; merender template, menyusun tautan, tanpa menyentuh DB |
| `app/Services/WhatsApp/WhatsAppMessageService.php` | **Baru** | `record()`, `markSent()`, `markSkipped()` dalam transaksi; pelaku sebagai parameter |
| `app/Services/WhatsAppNotifier.php` | **Hapus** | Digantikan tiga berkas di atas |
| `app/Support/WhatsAppLink.php` | **Baru** | Satu-satunya penyusun `wa.me`: basis URL, `rawurlencode`, batas panjang |
| `app/Support/WhatsAppDraft.php` | **Baru** | Objek nilai `readonly` menggantikan bentuk array |
| `app/Support/BookingFilters.php` | Ubah | Saringan `wa=belum` |
| `app/Policies/WhatsAppTemplatePolicy.php` | **Baru** | Turunan `SuperAdminOnlyPolicy` |
| `app/Rules/KnownPlaceholders.php` | **Baru** | Menolak placeholder tak dikenal, menyebut namanya |
| `app/Http/Requests/Admin/WhatsAppTemplateRequest.php` | **Baru** | `name`, `body` (+ batas panjang), `is_active` |
| `app/Http/Requests/Admin/RecordWhatsAppMessageRequest.php` | **Baru** | Hanya `template_key` — tidak ada teks dari klien |
| `app/Http/Requests/Admin/BookingFilterRequest.php` | Ubah | Terima `wa` |
| `app/Http/Controllers/Admin/WhatsAppTemplateController.php` | **Baru** | `index`, `edit`, `update` |
| `app/Http/Controllers/Admin/WhatsAppMessageController.php` | **Baru** | `store`, `markSent`, `skip` |
| `app/Http/Controllers/Admin/BookingController.php` | Ubah | `show`: draft dari interface baru + riwayat pesan + `with()` |
| `app/Http/Controllers/Admin/ContactMessageController.php` | Ubah | Pakai `WhatsAppLink`, bukan `WhatsAppNotifier` |
| `app/Http/Controllers/Admin/ScheduleController.php` | Ubah | Kelayakan pengingat per baris booking |
| `app/Http/Controllers/Admin/DashboardController.php` | Ubah | Kirim kartu "belum dikabari" |
| `app/Services/DashboardService.php` | Ubah | `pendingWhatsApp()` — satu kueri agregat |
| `app/Providers/AppServiceProvider.php` | Ubah | Ikat interface ke `ClickToChatNotifier` lewat `config('whatsapp.driver')` |
| `config/whatsapp.php` | Ubah | Buang `templates` (pindah ke enum) dan `ringkasan_biaya` |
| `config/company.php` | Ubah | **Hapus** `wa_messages` |
| `routes/web.php` | Ubah | 3 rute pesan (staf) + 3 rute template (grup `role:super_admin`) |
| `resources/js/pages/admin/template-wa/index.tsx` | **Baru** | Daftar 7 template |
| `resources/js/pages/admin/template-wa/edit.tsx` | **Baru** | Form sunting + pratinjau + daftar placeholder |
| `resources/js/components/admin/whatsapp-panel.tsx` | Ubah | Panel penuh: pratinjau, buka, tandai, lewati, penanda |
| `resources/js/components/admin/whatsapp-message-history.tsx` | **Baru** | Riwayat pesan satu booking |
| `resources/js/components/admin/booking-filter-bar.tsx` | Ubah | Saringan `wa=belum` |
| `resources/js/pages/admin/bookings/show.tsx` | Ubah | Panel + riwayat + penanda |
| `resources/js/pages/admin/jadwal/index.tsx` | Ubah | Aksi pengingat per baris |
| `resources/js/pages/admin/dashboard.tsx` | Ubah | Kartu "Belum dikabari" yang bisa diklik |
| `resources/js/layouts/admin-layout.tsx` | Ubah | Menu "Template WA" di grup Sistem (SA saja) |
| `resources/js/types/index.ts` | Ubah | `WhatsAppDraft` diperluas; `WhatsAppMessage`, `WhatsAppTemplate` |
| `tests/Feature/Admin/WhatsAppDraftTest.php` | **Baru** | Alur draft, tandai, lewati, template nonaktif, tanpa nomor |
| `tests/Feature/Admin/WhatsAppTemplateCrudTest.php` | **Baru** | Sunting, validasi placeholder, batas panjang, advisor ditolak |
| `tests/Feature/Admin/AccessMatrixTest.php` | Ubah | Baris template WA (butir 2.3.6) |
| `tests/Feature/Admin/DashboardTest.php` | Ubah | Kartu "belum dikabari" + rentang 7 hari |
| `tests/Feature/Admin/BookingListTest.php` | Ubah | Saringan `wa=belum` |
| `tests/Feature/Admin/ScheduleTest.php` | Ubah | Kelayakan pengingat |
| `tests/Feature/Admin/BookingStatusTest.php` | Ubah | Menyesuaikan namespace notifier |
| `tests/Feature/Admin/ContentCrudTest.php` | Ubah | Menyesuaikan `contactReplyUrl` → `WhatsAppLink` |
| `tests/Unit/WhatsAppPlaceholderTest.php` | **Baru** | Himpunan placeholder config == peta notifier |

## Risiko

| Risiko | Dampak | Penanganan |
|--------|--------|------------|
| **Seeder menimpa suntingan Super Admin** | Setiap `db:seed` mengembalikan teks ke bawaan dan menghapus kerja SA tanpa jejak — kegagalan diam yang baru ketahuan dari keluhan pelanggan | `firstOrCreate`, bukan `updateOrCreate` (Keputusan 15). Diuji: seed → sunting → seed ulang → suntingan bertahan |
| **Dua sumber teks hidup bersamaan** | Persis cacat **B2** sistem lama; pesan berubah-ubah tergantung jalur mana yang terpakai | `config('company.wa_messages')` dihapus di tugas yang sama, tanpa masa transisi. Dijaga butir "Selesai Bila" terakhir |
| **Kartu dashboard lahir dengan tunggakan palsu** | Booking yang sudah dikabari lewat tombol R6 tidak punya catatan, ikut terhitung "belum dikabari" pada hari pertama | Rentang 7 hari membuatnya habis sendiri sepekan; tombol **Lewati** membersihkan sisanya. Disebut di catatan rilis, tidak disembunyikan |
| **Pemotongan pesan diam-diam** | Template panjang buatan SA terkirim tanpa penutup "— Chery Arta", tanpa ada yang tahu | Panjang badan template divalidasi saat menyimpan (butir 19) + penghitung karakter + pratinjau terender |
| **Baca-lalu-tulis pada penandaan pesan** | Dua advisor menekan "Tandai Terkirim" bersamaan pada baris yang sama, atau menekan setelah "Lewati" | `WhatsAppMessageService` mengubah status **di dalam transaksi dengan baris terkunci**, mengikuti pola `BookingService::changeStatus` yang sudah ada. Perpindahan tidak sah ditolak, bukan ditimpa |
| **Kueri kartu dashboard terlalu berat** | Dashboard melambat untuk seluruh staf, setiap muat | Jalur mundur sudah disepakati: `bookings.booking_date` dalam ±7 hari, memakai index `bookings(status, booking_date)` yang sudah ada. Ketepatannya sedikit berkurang, kegunaannya tidak |
| **Memindahkan `App\Services\WhatsAppNotifier`** | Menyentuh dua controller dan dua berkas uji; mudah dikira sekadar ganti nama lalu dilewatkan dari peninjauan | Tercatat eksplisit di tabel Modul; `BookingStatusTest` dan `ContentCrudTest` wajib hijau tanpa disunting isinya selain namespace |
| **Nomor mentah menyelinap ke `wa.me`** | Tautan mengarah entah ke mana; cacat sistem lama terulang | Satu-satunya penyusun tautan adalah `App\Support\WhatsAppLink`, yang hanya menerima nomor dari `users.phone_wa` (sudah ternormalisasi sejak F1.2). Tidak ada jalur kedua |
| **Kebocoran ke halaman publik** | — | Tidak ada rute publik atau rute customer yang berubah; seluruh permukaan baru ada di `/admin` di balik `role:` + Policy |

## Ukuran Keberhasilan

1. **Kartu "Belum dikabari" benar-benar bisa mencapai nol** pada akhir hari kerja biasa. Bila
   sesudah dua minggu angkanya tidak pernah turun ke nol, definisinya (butir 15–16) yang salah,
   bukan advisornya.
2. **Super Admin menyunting minimal satu template dalam bulan pertama** tanpa meminta bantuan
   developer. Kalau tidak pernah terjadi, layar template belum benar-benar menjawab masalah #2 —
   entah karena tidak ditemukan, atau karena teks bawaannya memang sudah cukup.
3. **Tidak ada permintaan deploy yang alasannya "ubah teks WhatsApp"** setelah tahap ini rilis.
4. **Jumlah `no_show` menurun** pada minggu-minggu yang pengingat H-1-nya rutin ditekan,
   dibanding minggu yang tidak. Datanya sudah tersedia dari laporan rekap booking (A9) dan dari
   `whatsapp_messages` berkunci `booking_reminder` — dapat diperiksa tanpa alat baru.
