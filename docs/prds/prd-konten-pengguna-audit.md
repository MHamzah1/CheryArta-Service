# PRD: Konten, Pengguna & Audit (Tahap 9 — A10 + A11 + A12 + A13 + A14)

**Sumber keputusan:** [`docs/grills/grill-konten-pengguna-audit.md`](../grills/grill-konten-pengguna-audit.md) (ditutup 4 Agustus 2026)
**Tahap:** 9 (`F2.2`) — lihat [roadmap](../10-roadmap-implementasi.md#tahap-9--konten-pengguna--audit--f22)
**Tanggal:** 4 Agustus 2026
**Status:** ✅ **SELESAI** 4 Agustus 2026 — 546 uji Pest hijau, seluruh gerbang kualitas bersih.
Satu butir "Selesai Bila" belum tercentang: pemeriksaan responsif 360px secara visual.

---

## Masalah

Panel admin sudah bisa menjalankan pekerjaan harian bengkel, tetapi **empat hal masih hanya
bisa disentuh lewat DBeaver** — dan satu di antaranya tidak bisa disentuh sama sekali.

**Konten landing page tidak bisa diubah pemiliknya.** Tabel `facilities`, `faqs`, dan
`testimonials` sudah terisi sejak Tahap 3 (**R4**), dan landing page Tahap 7 membacanya dengan
benar. Tetapi mengubah satu kalimat FAQ berarti membuka klien database. Pemilik bengkel tidak
punya jalan masuk ke isinya sendiri.

**Pesan dari form kontak masuk ke database dan berhenti di sana.** `contact_messages` menerima
kiriman sejak Tahap 3, tetapi tidak ada layar untuk membacanya. Konsekuensinya sudah dicatat
terbuka di roadmap Tahap 7: halaman kontak terpaksa menonjolkan tombol WhatsApp sebagai jalur
utama, dan formnya hanya jalur cadangan. Calon pelanggan yang memakai form itu mengira dirinya
diabaikan — dan memang begitu kenyataannya.

**Menambah satu service advisor menuntut `tinker` atau DBeaver.** Akun staf hanya lahir dari
`UserSeeder` (**R9**). Akibatnya lebih dari sekadar merepotkan: seluruh uji hak akses selama ini
menilai **akun contoh dari seeder**, bukan orang sungguhan. Begitu aplikasi didemokan ke pemilik
bengkel, batas ini berubah dari "dapat diterima" menjadi penghalang.

**Tidak ada yang tahu siapa mengubah apa.** Sistem lama tidak punya jejak audit sama sekali, dan
sampai sekarang penggantinya juga belum. Advisor bisa mengubah status booking, menyunting paket
layanan, atau membuka data seluruh pelanggan tanpa satu baris pun tercatat. `docs/09 §9.7`
menjanjikan "advisor melihat data customer hanya lewat panel admin yang tercatat di activity
log" — janji yang belum ditepati.

Satu lagi yang sifatnya berbeda: **otorisasi sudah benar tetapi belum bisa dibaca sebagai satu
kesatuan.** Barisnya tersebar di `AdminAccessTest`, `CustomerDirectoryTest`, dan uji katalog.
Tersebar berarti baris yang *hilang* tidak kelihatan — dan itu persis cara temuan S3 sistem lama
bisa terulang tanpa ada yang sadar.

## Tujuan

Pemilik bengkel bisa mengurus isi situsnya, membaca pesan yang masuk, dan menambah akun staf
sendiri — tanpa membuka klien database. Setiap perubahan penting meninggalkan jejak yang bisa
ditelusuri, dan seluruh matriks hak akses terbukti lewat satu berkas uji yang lengkap.

## Latar

Tahap 8 (Dashboard & Laporan) sudah ditutup, sehingga Tahap 9 adalah **pekerjaan berikutnya**.
Urutan Big Fase 2 sengaja mendahulukan panel admin: yang dipakai setiap hari oleh orang bengkel
adalah panelnya, bukan invoice atau notifikasi otomatis.

Tahap ini memuat **lima modul sekaligus** — A10 Konten, A11 Pengguna Internal, A12 Activity Log,
A13 Navigasi, A14 Matriks Hak Akses. Sebagian besar penumpukan itu berasal dari dua sub-fase yang
dicabut: **F1.7** membuang A11 dan penegakan `must_reset_password` ke sini, **F1.8** membuang
`AccessMatrixTest` ke sini. Rekomendasi memecah tahap ini ditolak pemilik proyek (keputusan #1);
penggantinya adalah A14 dikerjakan **di depan**, bukan di belakang.

Fondasi yang sudah tersedia dan **dipakai ulang, bukan dibangun ulang**:

| Sudah ada | Dipakai untuk |
|-----------|---------------|
| Tabel `facilities`, `faqs`, `testimonials`, `contact_messages` + seeder idempoten | Seluruh A10 — tanpa migrasi data |
| Model `Facility`, `Faq`, `Testimonial`, `ContactMessage` beserta `scopeActive`/`scopeOrdered` | Kueri daftar & saringan |
| Pola CRUD `CarModelController` + `ServicePackageController` | Kerangka empat layar A10 |
| `CloudinaryImageUploader` + `UploadRules` | Gambar fasilitas |
| Seret-lepas galeri katalog (`gallery-manager.tsx`, HTML5 `draggable`, tanpa pustaka) | Pengurutan fasilitas, FAQ, testimoni |
| `CustomerAccountService` (password acak + `must_reset_password`) | Pola pembuatan & reset akun staf |
| `PhoneNumber::normalize` | `phone_wa` akun staf |
| `WhatsAppNotifier::draft()` | Balas pesan kontak via klik-to-chat |
| `DataTable`, `ConfirmDialog`, `EmptyState`, `Pagination`, `PhoneInput` | Seluruh layar |
| `PublicContent` | Landing page membaca konten — **tidak disentuh**, hanya isinya yang kini bisa diubah |

## Selesai Bila

DoD 12 butir di [`docs/10`](../10-roadmap-implementasi.md#definition-of-done) berlaku otomatis.
Berikut kriteria khusus tahap ini:

**A14 — dikerjakan lebih dulu**

- [x] `AccessMatrixTest` ada sejak awal pengerjaan dan memuat satu uji per baris matriks
      `docs/09 §9.3`, termasuk baris A10 dan A11 yang rutenya belum lahir
- [x] Baris yang rutenya belum ada **merah**, bukan dilewati atau dikomentari
- [x] Saat tahap ini ditutup, seluruh baris hijau kecuali invoice dan template WA yang memang
      menyusul di Tahap 10–11

**A10 — Konten**

- [x] Mengubah satu FAQ dari `/admin/faq` langsung terlihat di halaman FAQ publik tanpa deploy ulang
- [x] Menyeret urutan fasilitas mengubah urutannya di landing page
- [x] Menonaktifkan satu testimoni membuatnya hilang dari landing page, barisnya tetap ada di admin
- [x] Mengunggah gambar fasilitas menghasilkan URL Cloudinary, bukan berkas di disk
- [x] `/admin/pesan-masuk` menampilkan pesan dari form kontak, terbaru dulu, dengan penanda belum dibaca
- [x] Membuka satu pesan menandainya terbaca dan mencatat siapa yang membacanya
- [x] Lencana jumlah pesan belum dibaca muncul di sidebar dan berkurang setelah dibaca
- [x] Tombol balas membuka WhatsApp dengan nomor pengirim yang sudah ternormalisasi
- [x] Menghapus pesan menampilkan `ConfirmDialog` berisi **nama pengirim dan cuplikan isinya**,
      dan barisnya benar-benar hilang setelah dikonfirmasi
- [x] Advisor ditolak di seluruh rute A10 — bukan sekadar tidak melihat menunya

**A11 — Pengguna Internal**

- [x] `/admin/users` menampilkan **hanya** akun staf; tidak ada satu pun customer yang bocor ke daftar ini
- [x] Menambah advisor baru berhasil tanpa admin mengetik password apa pun
- [x] Password sementara tampil **satu kali** setelah aksi berhasil, dan tidak muncul lagi setelah halaman dimuat ulang
- [x] Akun staf baru tidak bisa membuka menu apa pun sebelum menetapkan password sendiri
- [x] Super Admin gagal menurunkan role dirinya sendiri, dengan pesan yang menjelaskan sebabnya
- [x] Super Admin gagal menonaktifkan dirinya sendiri
- [x] Super Admin **aktif terakhir** tidak bisa diturunkan maupun dinonaktifkan
- [x] Mengarahkan rute A11 ke id akun customer ditolak
- [x] Advisor ditolak di seluruh `/admin/users`

**A12 — Activity Log**

- [x] Mengubah paket layanan, katalog, atau akun staf memunculkan barisnya di `/admin/activity-log`
- [x] Baris memperlihatkan waktu, pelaku, aksi, objek, dan perubahan sebelum → sesudah
- [x] Mengubah **status booking** **tidak** memunculkan baris di sini — riwayatnya tetap di detail booking
- [x] Password, token, dan password sementara **tidak pernah** muncul di isi log
- [x] Saringan pelaku, jenis objek, dan rentang tanggal bekerja dan ikut di query string
- [x] Layar menyebutkan tanggal mulai pencatatan, sehingga kekosongan sebelumnya tidak disalahartikan
- [x] Advisor ditolak di `/admin/activity-log`

**A13 — Navigasi & sisa 2.2.5**

- [x] Sidebar memuat grup **Konten** dan **Sistem**, keduanya hanya terlihat Super Admin
- [x] Advisor tidak melihat kedua grup itu **dan** ditolak server bila URL-nya diketik langsung
- [ ] Seluruh layar benar pada 360px — tabel jadi kartu, form tidak menggeser badan halaman
      → **belum diverifikasi visual.** Komponennya memang yang sudah responsif (`DataTable`
      berubah jadi kartu di `< md`, form dibatasi `max-w-2xl`, daftar seret memakai flex yang
      membungkus), tetapi belum ada yang membukanya di 360px sungguhan. Butir ini menunggu
      pemeriksaan manual.

## Lingkup

**A10 Konten** — empat layar CRUD di bawah `/admin`:

- `/admin/fasilitas` — judul, deskripsi, gambar, urutan (seret), aktif
- `/admin/faq` — pertanyaan, jawaban, kategori, urutan (seret), aktif
- `/admin/testimoni` — nama, model mobil, rating 1–5, isi, urutan (seret), terbitkan/sembunyikan
- `/admin/pesan-masuk` — daftar pesan kontak, tandai dibaca, balas via WhatsApp, hapus permanen;
  lencana jumlah belum dibaca di `AdminLayout`

**A11 Pengguna Internal** di `/admin/users` (Super Admin saja):

- Daftar akun staf: nama, email, role, status aktif, login terakhir; saringan role & status; paginasi 25
- Tambah & ubah akun — password dibuat server, ditandai `must_reset_password`
- Nonaktifkan/aktifkan, reset password
- Pengaman transaksional: tidak bisa menjatuhkan diri sendiri, tidak bisa menjatuhkan Super Admin aktif terakhir

**A12 Activity Log** di `/admin/activity-log` (Super Admin saja):

- Pencatatan lewat `spatie/laravel-activitylog` untuk `User`, `CarModel`, `ServicePackage`,
  dan perubahan **data** `Booking`
- Layar daftar dengan saringan pelaku, jenis objek, rentang tanggal
- Perintah pembersih retensi 12 bulan didaftarkan di `routes/console.php`

**A13 Navigasi:** grup "Konten" dan "Sistem" ditambahkan ke `AdminLayout`.

**A14 Matriks:** `tests/Feature/Admin/AccessMatrixTest.php` dibuat sekaligus lengkap,
**sebagai pekerjaan pertama tahap ini**.

**Sisa butir 2.2.5c:** penegakan `must_reset_password` — middleware mengalihkan pemilik akun
bertanda ke `settings/password` sampai ia menetapkan password sendiri.

## Bukan Lingkup

1. **Form testimoni publik.** Testimoni diketik admin (keputusan #5). Tidak ada kiriman
   pelanggan, jadi tidak ada antrean moderasi — `is_published` hanyalah saklar tampil/sembunyi.
2. **Soft delete untuk `contact_messages`.** Hapus spam adalah hapus permanen (keputusan #4).
   Tidak ada kolom `deleted_at`, tidak ada layar pemulihan, tidak ada migration untuk tabel ini.
3. **Pengisian mundur activity log.** Perubahan sebelum tahap ini tidak punya jejak dan tidak
   akan dikarang (keputusan #3).
4. **Status booking di activity log.** Tetap milik `booking_status_histories`. A12 tidak
   menirunya.
5. **Mengaktifkan penjadwal cron di Railway.** Perintah `activitylog:clean` didaftarkan, tetapi
   memastikan prosesnya hidup adalah pekerjaan Tahap 12 (keputusan #7).
6. **Pengelolaan akun customer.** Tetap milik A6 di `/admin/customers`. Rute A11 menolak sasaran
   ber-role `customer`.
7. **Editor WYSIWYG untuk isi konten.** Teks biasa saja. Konten kaya menuntut pembersihan HTML
   yang belum ada, dan `dangerouslySetInnerHTML` dilarang keras.
8. **Baris invoice dan template WA di `AccessMatrixTest`.** Modulnya belum ada; menyusul di
   F2.4.6 dan F2.3.6.
9. **Isi dashboard customer `/dashboard`.** Masih kerangka Tahap 1 dan tidak disentuh di sini.

## Pengguna & Kebutuhannya

Seluruh isi tahap ini milik **Super Admin**. Advisor tidak menyentuh satu pun layar baru di
sini — ia hanya terkena akibatnya lewat activity log dan penegakan password. Customer dan tamu
tidak punya akses sama sekali.

- Sebagai **Super Admin**, saya ingin mengubah FAQ dan fasilitas dari panel, supaya memperbaiki
  satu kalimat di situs tidak menuntut saya membuka klien database.
- Sebagai **Super Admin**, saya ingin membaca pesan yang masuk lewat form kontak, supaya calon
  pelanggan yang memakai form tidak berakhir diabaikan.
- Sebagai **Super Admin**, saya ingin membalas pesan itu lewat WhatsApp dengan satu klik, supaya
  saya tidak perlu menyalin nomornya secara manual.
- Sebagai **Super Admin**, saya ingin menambah akun service advisor sendiri, supaya menerima
  karyawan baru tidak menuntut bantuan pengembang.
- Sebagai **Super Admin**, saya ingin akun staf baru menetapkan passwordnya sendiri saat pertama
  masuk, supaya saya tidak pernah mengetahui password orang lain.
- Sebagai **Super Admin**, saya ingin dicegah menjatuhkan akun Super Admin terakhir, supaya
  saya tidak bisa mengunci diri sendiri dari sistem yang saya miliki.
- Sebagai **Super Admin**, saya ingin tahu siapa mengubah apa dan kapan, supaya perselisihan
  soal data bisa diselesaikan dengan catatan, bukan dengan ingatan.
- Sebagai **service advisor**, saya ingin diminta menetapkan password sendiri saat pertama masuk,
  supaya password sementara yang orang lain ketahui tidak berlaku lebih dari satu kali.

## Kebutuhan Fungsional

### A. Matriks hak akses — dikerjakan lebih dulu

1. `tests/Feature/Admin/AccessMatrixTest.php` dibuat **sebelum** modul mana pun di tahap ini,
   memuat satu uji per baris matriks `docs/09 §9.3`.
2. Baris untuk rute yang belum lahir tetap ditulis dan **dibiarkan merah** — bukan dilewati,
   dikomentari, atau ditandai `skip`. Merah adalah daftar pekerjaan yang tersisa.
3. Setiap baris menguji tiga sisi: Super Admin boleh, advisor ditolak pada baris SA-saja,
   customer dan tamu ditolak seluruhnya.

### B. Konten — fasilitas, FAQ, testimoni

4. Ketiganya memakai pola CRUD yang sama: daftar terpaginasi, form tambah/ubah, saklar aktif,
   dan pengurutan seret-lepas.
5. Pengurutan memakai satu endpoint per sumber daya yang menerima **daftar id berurutan**, dan
   `exists` dibatasi ke tabel yang bersangkutan supaya id dari tabel lain ditolak.
6. Menyimpan urutan dibungkus `DB::transaction` — urutan yang tersimpan separuh lebih buruk
   daripada urutan lama.
7. Gambar fasilitas melewati `CloudinaryImageUploader` yang sudah ada; nama berkas asli dibuang,
   `image_public_id` dan `image_url` disimpan berpasangan.
8. Menghapus fasilitas juga menghapus gambarnya di Cloudinary — berkas yatim membebani kuota.
9. Testimoni diketik admin. Tidak ada alur moderasi; `is_published` hanya menentukan tampil atau
   tidak di landing page.
10. Menonaktifkan atau menyembunyikan satu baris **tidak** menghapusnya, dan efeknya langsung
    terlihat di halaman publik karena `PublicContent` sudah menyaring `scopeActive`.

### C. Konten — pesan masuk

11. Daftar menampilkan pesan terbaru dulu, dengan penanda jelas untuk yang belum dibaca, dan
    saringan belum/sudah dibaca.
12. Membuka satu pesan menandainya `is_read`, mengisi `read_by` dengan pelaku, dan `read_at`
    dengan waktu server. Ketiganya **tidak** fillable dan tidak pernah datang dari request.
13. Lencana jumlah belum dibaca dikirim lewat shared props `AdminLayout` dan hanya dihitung untuk
    Super Admin.
14. Tombol balas memakai `WhatsAppNotifier` yang sudah ada; nomor selalu bentuk ternormalisasi
    `62…`, tidak pernah input mentah yang ditempel ke URL.
15. Pesan tanpa nomor telepon tidak merender tombol WhatsApp sama sekali — bukan tombol mati.
16. **Menghapus pesan menghapusnya permanen.** Wajib melewati `ConfirmDialog` yang menampilkan
    nama pengirim dan cuplikan isi pesan, dengan tombol bertuliskan "Hapus Pesan".
17. Penghapusan tercatat di activity log: isinya hilang, tetapi fakta siapa menghapus pesan dari
    siapa dan kapan tetap punya jejak.

### D. Pengguna Internal

18. Daftar hanya memuat akun ber-role `super_admin` atau `service_advisor`. Kueri disaring di
    database, bukan diambil seluruhnya lalu difilter di PHP.
19. Kolom: nama, email, role, status aktif, login terakhir (`last_login_at` sudah terisi sejak
    Tahap 3). Saringan role & status, paginasi 25.
20. Membuat akun: `name`, `email` (unik lintas seluruh akun), `phone_wa` (unik, ternormalisasi),
    `role`, `is_active`. **Password tidak ada di form.** Server membuatnya acak dan menandai
    `must_reset_password`.
21. `role` yang diterima hanya `super_admin` atau `service_advisor`. Nilai `customer` ditolak
    validasi, dan sasaran ber-role `customer` ditolak di seluruh rute ini.
22. Password sementara ditampilkan **satu kali** tepat setelah aksi berhasil, disertai peringatan
    bahwa ia tidak bisa dilihat lagi. Tidak disimpan terbaca di mana pun.
23. Pengaman berikut dihitung **di dalam transaksi dengan baris terkunci**, bukan dibaca lalu
    ditulis:
    - Super Admin tidak bisa menurunkan role dirinya sendiri
    - Super Admin tidak bisa menonaktifkan dirinya sendiri
    - Super Admin **aktif terakhir** tidak bisa diturunkan maupun dinonaktifkan
24. Pesan penolakannya menjelaskan sebabnya — "Anda adalah Super Admin aktif terakhir", bukan
    "Tindakan tidak diizinkan".
25. Akun tidak pernah dihapus permanen dari layar ini.

### E. Penegakan `must_reset_password`

26. Middleware pada rute ber-auth mengalihkan pemilik akun bertanda ke `settings/password`.
27. Dikecualikan: rute ganti password itu sendiri, logout, dan aset. Tanpa pengecualian ini
    pengguna terjebak di pengalihan tanpa ujung.
28. Setelah password ditetapkan, tanda dilepas dan pengguna diantar ke beranda sesuai perannya
    lewat `App\Support\HomeRoute` yang sudah ada.

### F. Activity Log

29. Pencatatan memakai `spatie/laravel-activitylog` untuk `User`, `CarModel`, `ServicePackage`,
    dan perubahan **data** `Booking` (tanggal, jam, paket, kendaraan, keluhan).
30. **Perubahan status booking tidak dicatat** — `booking_status_histories` tetap satu-satunya
    sumber kebenaran transisi status.
31. Atribut sensitif dikecualikan dari pencatatan: `password`, `remember_token`, dan password
    sementara apa pun.
32. Layar daftar terpaginasi dengan saringan pelaku, jenis objek, dan rentang tanggal; saringan
    aktif ikut di query string.
33. Layar menyebutkan tanggal mulai pencatatan, supaya kekosongan sebelum tahap ini tidak dibaca
    sebagai "tidak ada yang berubah".
34. Tidak ada pengisian mundur.
35. `Schedule::command('activitylog:clean')->daily()` didaftarkan di `routes/console.php`.
    Mengaktifkan prosesnya di Railway adalah pekerjaan Tahap 12.

### G. Navigasi & hak akses

36. `AdminLayout` menambahkan grup **Konten** (Fasilitas · FAQ · Testimoni · Pesan Masuk) dan
    **Sistem** (Pengguna Internal · Activity Log), keduanya khusus Super Admin.
37. Menu yang tidak boleh diakses **tidak dirender**, bukan ditampilkan lalu dinonaktifkan.
38. Seluruh rute baru berada di grup `role:super_admin` **dan** memanggil `Gate::authorize` per
    aksi. Menyembunyikan menu bukan pengaman.

## Kebutuhan Non-Fungsional

**Keamanan**

- Setiap sumber daya baru punya Policy: `FacilityPolicy`, `FaqPolicy`, `TestimonialPolicy`,
  `ContactMessagePolicy`. `UserPolicy` diperluas untuk sasaran staf.
- Seluruh masukan lewat Form Request. Tidak ada `$request->all()`.
- `is_read`, `read_by`, `read_at`, `role`, `must_reset_password` ditetapkan server — tidak pernah
  dibaca dari request (`.claude/rules/50`).
- Unggahan gambar dibatasi `jpg|jpeg|png|webp`, maks 2 MB, divalidasi lewat isi berkas bukan
  ekstensi, nama di-generate ulang.
- Password sementara tidak pernah masuk activity log, tidak masuk log aplikasi, dan tidak
  tersimpan dalam bentuk terbaca.
- Nomor telepon pengirim pesan hanya terlihat staf yang login. Tidak ada rute publik yang lahir
  dari PRD ini.

**Waktu**

- `read_at` dan seluruh tanggal memakai `Asia/Jakarta`. Dilarang `date()`, `strtotime()`, atau
  `new DateTime()` telanjang (temuan B7).
- Rentang tanggal saringan activity log divalidasi di server, batas atasnya ditetapkan.

**Konfigurasi**

- Retensi activity log (12 bulan) hidup di berkas `config`, bukan ditulis di service dan di
  label antarmuka sekaligus.
- Batas unggahan memakai `App\Support\UploadRules` yang sudah ada.

**Antarmuka**

- Bahasa Indonesia, istilah konsisten. Warna hanya dari design token; status hanya lewat
  `lib/status.ts`.
- Tanggal dan angka diformat lewat `lib/format.ts`.
- Benar pada 360 / 768 / 1280. Tabel dibungkus `overflow-x-auto` dan menjadi kartu di `< md`.
- Seret-lepas wajib punya alternatif papan ketik atau tombol naik/turun — seret saja tidak bisa
  dipakai dengan papan ketik (`.claude/rules/20 §Aksesibilitas`).
- Daftar kosong memakai `EmptyState` berisi ajakan tindakan, bukan tabel kosong.

**Kinerja**

- Seluruh daftar dipaginasi di server. Activity log berpotensi paling besar — tidak boleh
  `all()`.
- Lencana pesan belum dibaca satu kueri `count()` bersaringan indeks, bukan memuat seluruh baris.
- Tanpa N+1: relasi pelaku dan objek activity log dimuat `with()`.

## Keputusan yang Sudah Disetujui

Terkunci pada sesi grill 4 Agustus 2026. **Jangan dibuka ulang.**

1. **Tahap 9 tetap satu, tidak dipecah.** Rekomendasi memecah A10 dari A11+A12 ditolak.
   Penggantinya: **A14 dikerjakan di depan** sebagai kerangka uji yang merah lalu menghijau,
   supaya uji matriks tidak jatuh sebagai korban saat pekerjaan dikejar cepat.
2. **Perbaikan tujuan setelah login digabung ke butir 2.2.5** — sudah selesai sebelum PRD ini
   ditulis (2.2.5a dan 2.2.5b). Sisanya 2.2.5c, penegakan `must_reset_password`.
3. **Tidak ada pengisian mundur activity log**, dan **status booking tidak dicatat A12** — audit
   hasil tebakan lebih berbahaya daripada audit yang jujur; dua sumber kebenaran untuk satu fakta
   adalah cacat B2 sistem lama.
4. **Hapus spam = hapus permanen**, bukan soft delete. Rekomendasi soft delete ditolak pemilik
   proyek. Dikawal `ConfirmDialog` yang menampilkan isi pesan + jejak di activity log.
5. **Testimoni diketik admin**, tanpa alur moderasi.
6. **Pengurutan memakai seret-lepas**, memakai ulang pola galeri katalog.
7. **`activitylog:clean` didaftarkan sekarang, penjadwalnya diaktifkan di Tahap 12.**
8. **Password sementara staf tampil sekali di layar**, tidak pernah masuk activity log.

Berlaku pula tanpa dibahas ulang, dari `docs/` dan keputusan final proyek: A10, A11, A12
seluruhnya **Super Admin saja** · akun staf tidak pernah dihapus permanen · `role` tidak pernah
dibaca dari request · WhatsApp tetap klik-to-chat manual · Bahasa Indonesia, `Asia/Jakarta`.

## Pertanyaan Terbuka

1. ~~**Apakah `spatie/laravel-activitylog` versi terbaru mendukung Laravel 12?**~~ —
   **ditutup 4 Agustus 2026: ya.** Diperiksa lebih dulu lewat `composer require --dry-run`
   sebelum apa pun dipasang, persis seperti yang PRD ini syaratkan. Resolusinya berhasil ke
   **v4.12.3** pada Laravel 12 / PHP 8.2 tanpa konflik. Jalur cadangan tabel buatan sendiri
   tidak jadi dipakai.

Selain itu tidak ada. Delapan pertanyaan sesi grill seluruhnya sudah terjawab.

## Dampak ke Rancangan `docs/`

**Ya — empat berkas harus ikut diperbarui.** Seluruhnya berasal dari keputusan grill, dan
tercatat sejak sesi ditutup. **Seluruhnya sudah dikerjakan 4 Agustus 2026.**

| Berkas | Bagian | Perubahan | Status |
|--------|--------|-----------|--------|
| `docs/04-skema-database.md` | `§4.2 activity_log` | Nyatakan eksplisit bahwa `activity_log` **tidak** mencatat perubahan status booking — itu milik `booking_status_histories`. Tanpa ini pengembang berikutnya akan menambahkannya kembali dengan niat baik (keputusan #3) | ✅ |
| `docs/07-modul-admin.md` | `§A10` | Testimoni diketik admin tanpa moderasi (keputusan #5); "hapus spam" adalah hapus permanen berkawal `ConfirmDialog` + jejak activity log (keputusan #4) | ✅ |
| `docs/07-modul-admin.md` | `§A12` | Pencatatan dimulai sejak Tahap 9 tanpa pengisian mundur; penjadwal retensi baru aktif di Tahap 12 (keputusan #3, #7) | ✅ |
| `docs/10-roadmap-implementasi.md` | Tahap 12 | Butir baru **2.5.12**: aktifkan penjadwal `activitylog:clean` di Railway (keputusan #7) | ✅ |
| `docs/07-modul-admin.md` | `§A11`, `§A13`, `§A14` | **Tambahan, tidak direncanakan:** ketiganya ditandai selesai; A11 mendapat catatan password sementara (keputusan #8), A13 catatan lencana pesan belum dibaca, A14 catatan "ditulis lebih dulu sebagai kerangka merah" (keputusan #1) | ✅ |

Tidak ada perubahan pada matriks hak akses (`docs/09 §9.3`) — baris "Konten landing" dan
"Pengguna internal & activity log" sudah menetapkan SA-saja, dan PRD ini mengikutinya apa adanya.

## Modul yang Tersentuh

**Satu migration saja** — bawaan `spatie/laravel-activitylog`. Keempat tabel A10 sudah lahir di
Tahap 3, dan keputusan #4 meniadakan kebutuhan kolom `deleted_at`.

| Berkas | Baru / Ubah | Yang berubah |
|--------|-------------|--------------|
| `tests/Feature/Admin/AccessMatrixTest.php` | **Baru** | **Dikerjakan pertama.** Satu uji per baris matriks `docs/09 §9.3`; baris A10/A11 merah sampai modulnya lahir |
| `app/Http/Controllers/Admin/FacilityController.php` | **Baru** | CRUD fasilitas + `reorder` |
| `app/Http/Controllers/Admin/FaqController.php` | **Baru** | CRUD FAQ + `reorder` |
| `app/Http/Controllers/Admin/TestimonialController.php` | **Baru** | CRUD testimoni + `reorder` |
| `app/Http/Controllers/Admin/ContactMessageController.php` | **Baru** | Daftar, tandai dibaca, hapus permanen |
| `app/Http/Controllers/Admin/UserController.php` | **Baru** | A11 — daftar staf, tambah, ubah, toggle aktif, reset password |
| `app/Http/Controllers/Admin/ActivityLogController.php` | **Baru** | A12 — daftar bersaringan |
| `app/Services/ContentOrderService.php` | **Baru** | Simpan urutan seret dalam transaksi; dipakai tiga sumber daya konten |
| `app/Services/UserService.php` | **Baru** | Pembuatan akun staf, ubah role, toggle aktif, reset password — seluruh pengaman keputusan #23 di dalam transaksi dengan baris terkunci |
| `app/Http/Middleware/EnsurePasswordIsReset.php` | **Baru** | Butir 2.2.5c |
| `app/Http/Requests/Admin/StoreFacilityRequest.php`, `UpdateFacilityRequest.php` | **Baru** | Validasi fasilitas |
| `app/Http/Requests/Admin/FaqRequest.php` | **Baru** | Validasi FAQ |
| `app/Http/Requests/Admin/TestimonialRequest.php` | **Baru** | Validasi testimoni, rating 1–5 |
| `app/Http/Requests/Admin/ReorderContentRequest.php` | **Baru** | Daftar id berurutan, `exists` dibatasi per tabel |
| `app/Http/Requests/Admin/StoreUserRequest.php`, `UpdateUserRequest.php` | **Baru** | Validasi akun staf; `role` dibatasi dua nilai, `customer` ditolak |
| `app/Http/Requests/Admin/ActivityLogFilterRequest.php` | **Baru** | Saringan pelaku, jenis objek, rentang tanggal berbatas |
| `app/Policies/FacilityPolicy.php`, `FaqPolicy.php`, `TestimonialPolicy.php`, `ContactMessagePolicy.php` | **Baru** | SA saja |
| `app/Policies/UserPolicy.php` | Ubah | Tambah kemampuan atas sasaran **staf**; yang lama khusus customer dan tidak boleh jadi pintu belakang |
| `app/Models/Facility.php`, `Faq.php`, `Testimonial.php` | Ubah | Trait `LogsActivity` bila dicatat; sisanya sudah lengkap |
| `app/Models/User.php`, `CarModel.php`, `ServicePackage.php`, `Booking.php` | Ubah | Trait `LogsActivity` + daftar atribut yang dikecualikan |
| `config/activity.php` | Ubah | Retensi 12 bulan (dipublikasikan paket) |
| `bootstrap/app.php` | Ubah | Daftarkan `EnsurePasswordIsReset` |
| `routes/console.php` | Ubah | `Schedule::command('activitylog:clean')->daily()` |
| `routes/web.php` | Ubah | Rute A10, A11, A12 di dalam grup `role:super_admin` |
| `resources/js/pages/admin/konten/fasilitas/index.tsx`, `form.tsx` | **Baru** | Layar fasilitas |
| `resources/js/pages/admin/konten/faq/index.tsx`, `form.tsx` | **Baru** | Layar FAQ |
| `resources/js/pages/admin/konten/testimoni/index.tsx`, `form.tsx` | **Baru** | Layar testimoni |
| `resources/js/pages/admin/konten/pesan-masuk/index.tsx` | **Baru** | Daftar + panel baca + hapus |
| `resources/js/pages/admin/users/index.tsx`, `form.tsx` | **Baru** | Layar A11 |
| `resources/js/pages/admin/activity-log/index.tsx` | **Baru** | Layar A12 |
| `resources/js/components/admin/sortable-list.tsx` | **Baru** | Seret-lepas umum + tombol naik/turun untuk papan ketik; disarikan dari pola `gallery-manager.tsx` |
| `resources/js/components/admin/temporary-password-panel.tsx` | **Baru** | Menampilkan password sementara satu kali beserta peringatannya |
| `resources/js/layouts/admin-layout.tsx` | Ubah | Grup "Konten" dan "Sistem" + lencana pesan belum dibaca |
| `resources/js/types/index.ts` | Ubah | Tipe props konten, akun staf, baris activity log |
| `app/Http/Middleware/HandleInertiaRequests.php` | Ubah | Shared prop lencana pesan belum dibaca (SA saja) |
| `tests/Feature/Admin/ContentCrudTest.php` | **Baru** | CRUD + urutan + efek ke halaman publik |
| `tests/Feature/Admin/ContactMessageTest.php` | **Baru** | Tandai dibaca, hapus permanen, penolakan advisor |
| `tests/Feature/Admin/UserManagementTest.php` | **Baru** | Seluruh pengaman keputusan #23, penolakan sasaran customer |
| `tests/Feature/Admin/ActivityLogTest.php` | **Baru** | Pencatatan, pengecualian status booking, password tidak bocor |
| `tests/Feature/Auth/MustResetPasswordTest.php` | **Baru** | Akun bertanda terkunci sampai menetapkan password |
| `composer.json` | Ubah | `spatie/laravel-activitylog` |

## Risiko

**`spatie/laravel-activitylog` bisa belum mendukung Laravel 12.** `composer install` yang gagal
menjatuhkan seluruh deploy, bukan hanya fitur audit — pelajaran langsung dari Tahap 8. →
Periksa dukungan versinya **sebelum** menambahkan paket. Jalur cadangan sudah jelas dan murah:
tabel `activity_log` buatan sendiri dengan observer, karena kebutuhannya sederhana.

**Lima modul dalam satu tahap, dikejar cepat.** Yang paling mungkin dikorbankan adalah A14,
padahal ia satu-satunya yang membuktikan otorisasi utuh — persis cara temuan S3 sistem lama
terulang. → A14 dikerjakan **di depan** sebagai kerangka merah (keputusan #1). Kalau ada yang
tertinggal, yang tertinggal terlihat sebagai uji merah, bukan sebagai kekosongan senyap.

**Pengaman "Super Admin aktif terakhir" adalah operasi baca-lalu-tulis.** Dua permintaan
bersamaan yang masing-masing menurunkan satu dari dua Super Admin terakhir bisa lolos berdua dan
mengunci semua orang dari sistem. Persis pola cacat kuota booking sistem lama. → Hitungan
dilakukan `lockForUpdate()` di dalam `DB::transaction`, dan ujinya menembak dua permintaan
bersamaan, bukan hanya satu.

**Hapus permanen pesan kontak tidak bisa dibatalkan** (keputusan #4). Pesan pelanggan sungguhan
yang salah ditandai spam hilang selamanya. → `ConfirmDialog` menampilkan nama pengirim dan
cuplikan isi sebelum tombol ditekan, dan penghapusannya tercatat di activity log sehingga
setidaknya diketahui pernah ada dan siapa yang menghapusnya.

**Password sementara staf membuka panel internal** — lebih berat daripada kasus customer di
Tahap 6, karena yang dibuka bukan akun pelanggan melainkan panel bengkel. → Masa berlakunya
dipersempit `must_reset_password` yang ditegakkan di butir yang sama (2.2.5c), sehingga hanya sah
untuk satu kali masuk. Tidak pernah masuk activity log, tidak pernah tersimpan terbaca.

**Activity log bisa membocorkan data yang justru ingin dilindungi.** Ia mencatat nilai sebelum
dan sesudah — termasuk kolom sensitif bila tidak dikecualikan. → `password`, `remember_token`,
dan password sementara dikecualikan eksplisit di tiap model, dan ada uji yang membuktikannya.

**Retensi 12 bulan berpeluang jadi janji kosong.** Perintahnya didaftarkan, tetapi prosesnya
baru hidup di Tahap 12 (keputusan #7). → Butir eksplisit ditambahkan ke Tahap 12, dan `docs/07
§A12` menyebutkan kapan penjadwalnya mulai berlaku — bukan dibiarkan tersirat.

**Seret-lepas tidak bisa dipakai dengan papan ketik.** Pola galeri katalog yang dipakai ulang
memakai HTML5 `draggable` tanpa pustaka, dan itu tidak punya jalur papan ketik. → `SortableList`
menyediakan tombol naik/turun sebagai jalur setara, bukan sekadar tambahan kosmetik.

## Ukuran Keberhasilan

1. **Pemilik bengkel mengubah isi situsnya sendiri minimal sekali** dalam sebulan pertama tanpa
   menghubungi pengembang. Bila nol, layar konten ternyata tidak menjawab kebutuhan nyata dan
   perlu ditinjau sebelum dikembangkan lebih jauh.
2. **Tidak ada lagi pesan kontak yang tidak terbaca lebih dari satu hari kerja.** Sebelum ini
   angkanya tidak pernah dihitung karena tidak ada yang bisa membacanya; sesudahnya, lencana
   belum dibaca seharusnya kosong pada akhir hari.
3. **Advisor berikutnya ditambahkan lewat `/admin/users`, bukan lewat DBeaver.** Ini penanda
   paling jelas bahwa A11 benar-benar menggantikan jalur lama.
4. **Activity log terpakai saat ada perselisihan data** — minimal satu kali dibuka untuk menjawab
   "siapa yang mengubah ini". Bila tidak pernah dibuka dalam enam bulan, retensinya boleh
   diperpendek.
5. **`AccessMatrixTest` menangkap minimal satu regresi otorisasi** sepanjang Tahap 10–12. Kalau
   tidak pernah, ia tetap berguna sebagai dokumentasi hidup — tetapi kalau iya, ia sudah membayar
   seluruh biayanya sendiri.
