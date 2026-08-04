# Grill — F2.2 Konten, Pengguna & Audit (A10 + A11 + A12 + A13 + A14)

**Status:** ✅ selesai · dibuka dan ditutup 4 Agustus 2026
**Tahap:** 9 (`F2.2`) — lihat [roadmap](../10-roadmap-implementasi.md#tahap-9--konten-pengguna--audit--f22)
**Keputusan:** 8 pertanyaan terjawab. Tujuh rekomendasi disetujui; **satu ditolak** (Q4 —
hapus spam dikerjakan sebagai hapus permanen, bukan soft delete).
**Berikutnya:** ✅ sudah ditulis → [`docs/prds/prd-konten-pengguna-audit.md`](../prds/prd-konten-pengguna-audit.md)

Sumber yang sudah dibaca: `docs/07-modul-admin.md §A10–§A14` ·
`docs/10-roadmap-implementasi.md §F2.2` (butir 2.2.1–2.2.8) ·
`docs/04-skema-database.md §4.2` (`facilities`, `faqs`, `testimonials`, `contact_messages`,
`activity_log`) · `docs/09-keamanan-hak-akses.md §9.3` · `.claude/rules/10,20,30,40,50,60` ·
kode: `UserPolicy`, `CustomerController`, `CustomerAccountService`, `CarModelController`,
`ServicePackageController`, `CarModelGalleryService`, `admin-layout.tsx`, `User`, `Facility`,
`Faq`, `Testimonial`, `ContactMessage`.

---

## Keputusan implisit — tidak ditanyakan

Sudah ditetapkan rancangan atau kode. Dicatat supaya tidak dibuka ulang.

| Hal | Ketetapan | Sumber |
|-----|-----------|--------|
| Sub-fase & urutan | F2.2, sesudah F2.1 (✅ selesai), sebelum F2.3 WhatsApp | `docs/10 §Big Fase 2` |
| **Tidak ada migration untuk A10** | `facilities`, `faqs`, `testimonials`, `contact_messages` sudah lahir di F1.2.2 beserta seeder idempoten | `docs/04 §4.2`, **R4** |
| Hak akses A10 | **Super Admin saja** — advisor tidak melihat menunya dan ditolak server | `docs/07 §A10`, matriks `docs/09 §9.3` |
| Hak akses A11 | **Super Admin saja**, grup rute `role:super_admin` | `docs/07 §A11`, roadmap 2.2.4 |
| Hak akses A12 | **Super Admin saja** | `docs/07 §A12` |
| A11 hanya akun staf | `super_admin` \| `service_advisor`. Sasaran ber-role `customer` **ditolak** — pengelolaannya tetap di A6 | `docs/07 §A11` |
| A11 tidak pernah hapus permanen | Nonaktifkan, bukan hapus | `docs/07 §A11`, `.claude/rules/30` |
| Password akun staf baru | **Tidak diisi admin.** Server membuat acak + `must_reset_password` | `docs/07 §A11`, roadmap 2.2.3 |
| Pengaman A11 | SA tidak bisa menurunkan/menonaktifkan diri sendiri; **SA aktif terakhir** dilindungi, dihitung **di dalam transaksi dengan baris terkunci** | `docs/07 §A11`, roadmap 2.2.4 |
| Kolom A11 | `name`, `email` (unik), `phone_wa` (unik, ternormalisasi `62…`), `role`, `is_active` | `docs/07 §A11` |
| Daftar A11 | Saringan role & status, paginasi 25, kolom login terakhir (`last_login_at` sudah diisi sejak F1.2) | `docs/07 §A11`, kode `AuthenticatedSessionController:45` |
| Pustaka A12 | **`spatie/laravel-activitylog`** (migration bawaan paket), retensi **12 bulan** | roadmap 2.2.6, `docs/04 §4.2` |
| Model yang dicatat A12 | `Booking`, `Invoice`, `User`, `CarModel`, `ServicePackage` | `docs/04 §4.2` |
| Kolom layar A12 | Waktu, pelaku, aksi, objek, perubahan (sebelum → sesudah). Filter: pelaku, jenis objek, rentang tanggal | `docs/07 §A12` |
| Struktur menu A13 | Pohon menunya sudah ditulis utuh — Konten (SA) dan Sistem (SA) tinggal dipasang | `docs/07 §A13` |
| Menu tidak boleh diakses | **Tidak dirender**, bukan ditampilkan-lalu-dinonaktifkan; server tetap menolak URL yang diketik | `docs/07 §A13`, temuan S3 |
| A14 | `tests/Feature/Admin/AccessMatrixTest.php` dibuat **sekaligus lengkap**: satu uji per baris matriks. Baris WA menyusul F2.3.6, invoice F2.4.6 | `docs/07 §A14`, roadmap 2.2.7 |
| Layout | `AdminLayout` yang sudah ada | `.claude/rules/20` |
| Pola CRUD | Mengikuti `CarModelController` / `ServicePackageController` yang sudah ada — Form Request, Service, Policy, `DataTable` | kode + `.claude/rules/10` |
| Unggah gambar fasilitas | Pakai `CloudinaryImageUploader` + `UploadRules` yang sudah ada — bukan jalur unggah baru | kode `CarModelGalleryService` |
| Normalisasi telepon | `PhoneNumber::normalize` yang sudah ada | kode, `.claude/rules/50` |
| Bahasa & format | Indonesia, `Asia/Jakarta`, `lib/format.ts`, `lib/status.ts` | keputusan final #7 |

---

## Pertanyaan

### Q1 — F2.2 memuat lima modul sekaligus. Dipecah atau tidak?

**Pertanyaan:** F2.2 berisi 8 butir yang menyentuh **lima modul**: A10 (empat layar CRUD),
A11 (CRUD + pengaman transaksional + middleware `must_reset_password`), A12 (paket baru + layar
log + retensi), A13 (navigasi), A14 (matriks uji lengkap). Apakah dikerjakan sebagai satu
sub-fase, atau dipecah?

**Rekomendasi: pecah menjadi dua — F2.2a Konten (A10) dan F2.2b Pengguna & Audit (A11+A12),
dengan A13+A14 ikut di F2.2b.**

Alasannya bukan sekadar "kelihatan banyak":

1. **Bandingkan dengan F2.1 yang baru selesai.** F2.1 isinya **dua** modul (A1+A9) dan
   menghasilkan ±26 berkas. F2.2 seperti tertulis punya **empat layar CRUD di A10 saja** —
   masing-masing dengan Form Request, Service, Policy, halaman index/form, dan uji — plus A11
   yang pengamannya lebih rumit daripada apa pun di F2.1.
2. **Sifat pekerjaannya berbeda jauh.** A10 adalah CRUD berulang dengan pola yang sudah
   terbukti (`CarModelController`) — pekerjaan mekanis, risiko rendah. A11+A12 menyentuh
   **autentikasi, otorisasi, dan penguncian transaksi** — tempat bug paling mahal. Menggabungkan
   keduanya dalam satu sub-fase membuat yang berisiko dikerjakan sambil terburu-buru mengejar
   sisa CRUD.
3. **A14 tidak bisa ditulis di tengah.** `AccessMatrixTest` "dibuat sekaligus lengkap" mencakup
   baris A10 **dan** A11. Ia harus jadi butir terakhir setelah keduanya ada — kalau F2.2
   dipadatkan, A14 yang paling mungkin dikorbankan, dan itu persis temuan S3 sistem lama.

- **Keputusan saya:** [x] **TETAP SATU sub-fase — F2.2 tidak dipecah.** Prioritasnya
  **selesai secepatnya**.
- **Catatan:** Dua konsekuensi yang mengikat pengerjaannya, karena rekomendasi pecah ditolak:
  1. **Tidak ada target hari di mana pun.** Atas permintaan pemilik proyek, seluruh estimasi
     berbasis hari dihapus dari `docs/10` (bagian "Ringkasan Jadwal" diganti "Urutan
     Pengerjaan"), dari PRD, dan dari dokumen grill. F2.2 dinyatakan selesai berdasarkan
     **DoD dan `AccessMatrixTest` yang hijau**, bukan berapa lama ia dikerjakan.
  2. **A14 dikerjakan di DEPAN, bukan di belakang.** `AccessMatrixTest` ditulis lebih dulu
     sebagai kerangka lengkap — satu uji per baris matriks, termasuk baris untuk rute A10 dan
     A11 yang belum ada. Baris-baris itu **merah sejak awal** dan berubah hijau seiring
     modulnya lahir. Dengan begitu "sekaligus lengkap" tetap terpenuhi dan uji matriks tidak
     bisa jatuh sebagai korban saat pekerjaan dikejar cepat. Ini pengganti pengaman yang
     hilang akibat tidak dipecah.

---

### Q2 — Utang login/role dari F1.2: masuk F2.2 atau tidak?

**Pertanyaan:** Redirect setelah login mengarah ke `/dashboard` milik customer tanpa memandang
role, dan grup rute customer belum dijaga `role:customer` — staf yang login mendarat di layar
customer. Masuk F2.2 atau dijadwalkan terpisah?

**Rekomendasi: masukkan ke F2.2, digabung dengan butir 2.2.5.**

Butir 2.2.5 sudah akan memasang middleware `must_reset_password` yang mengalihkan pengguna
sesudah login, dan menyentuh `AuthenticatedSessionController` beserta grup rute yang sama.
Mengerjakan keduanya terpisah berarti membuka berkas yang sama dua kali dan menguji alur login
dua kali. Digabung, biayanya nyaris nol.

Ada pula alasan kebenaran: selama redirect masih mengabaikan role, **`AccessMatrixTest` (A14)
akan lulus sambil membiarkan cacatnya hidup** — matriks menguji "boleh/tidak boleh membuka",
bukan "mendarat di mana". Menutup ini sebelum A14 ditulis membuat uji matriksnya jujur.

⚠️ Potensi masalah: memasang `role:customer` pada grup rute customer akan **menutup akses staf**
ke `/booking`, padahal matriks `docs/09 §9.3` menandai "Membuat booking" ✅ untuk SA dan advisor
→ bila staf ternyata memakai form booking customer, mereka kehilangan jalurnya → usulan: staf
memang tidak memakainya, jalur resmi mereka `admin.bookings.create` (walk-in) yang sudah ada
sejak F1.5; matriks tetap benar. **Perlu konfirmasi Anda**, karena inilah satu-satunya bagian
yang bisa mengubah kebiasaan kerja.

- **Keputusan saya:** [x] **Masuk F2.2, digabung dengan butir 2.2.5.**
- **Catatan:** Dengan menerima ini, asumsi berikut ikut berlaku dan menjadi catatan resmi:
  **staf tidak memakai form booking customer** (`/booking`) — jalur mereka `admin.bookings.create`
  (walk-in) sejak F1.5. Baris matriks "Membuat booking ✅ SA/ADV" tetap benar karena dipenuhi
  lewat walk-in. Bila asumsi ini keliru, yang berubah hanya satu baris middleware — grup rute
  customer diberi `role:customer` kecuali `/booking`.

---

### Q3 — A12 mencatat sejak kapan, dan apakah menabrak `booking_status_histories`?

**Pertanyaan:** `spatie/laravel-activitylog` mulai mencatat sejak dipasang — seluruh perubahan
sebelum F2.2 tidak punya jejak. Selain itu perubahan status booking **sudah** dicatat di
`booking_status_histories` sejak F1.5. Apakah A12 ikut mencatatnya lagi?

**Rekomendasi: (a) tidak ada pengisian mundur; (b) A12 TIDAK mencatat perubahan status booking.**

(a) Mengisi mundur berarti mengarang data audit — log audit yang isinya tebakan lebih berbahaya
daripada log yang jujur mulai dari tanggal tertentu. Layar A12 cukup menyebutkan tanggal mulai
pencatatan supaya kekosongan sebelumnya tidak disalahartikan sebagai "tidak ada yang berubah".

(b) `booking_status_histories` sudah menjadi sumber kebenaran transisi status, dipakai detail
booking dan diuji sejak F1.5. Mencatat hal yang sama di `activity_log` menciptakan **dua sumber
kebenaran untuk satu fakta** — persis cacat B2 sistem lama, dan keduanya pasti akan berbeda
suatu hari. A12 mencatat perubahan **data** booking (tanggal, jam, paket, kendaraan, keluhan)
dan seluruh perubahan `User`, `CarModel`, `ServicePackage`; status ditangani tabel yang sudah ada.

⚠️ Potensi masalah: `docs/04 §4.2` menyebut `activity_log` dicatat untuk model `Booking` tanpa
mengecualikan status → bila diikuti harfiah, dua sumber kebenaran lahir → usulan: perbarui
`docs/04` agar menyebut pengecualian itu secara eksplisit, dan layar A12 menautkan ke riwayat
status di detail booking.

- **Keputusan saya:** [x] **(a) Tidak ada pengisian mundur. (b) A12 tidak mencatat perubahan
  status booking** — itu tetap milik `booking_status_histories`.
- **Catatan:** `docs/04 §4.2` **wajib ikut diperbarui** menyebut pengecualian ini, kalau tidak
  pengembang berikutnya akan menambahkannya kembali dengan niat baik. Layar A12 menampilkan
  tanggal mulai pencatatan supaya kekosongan sebelum F2.2 tidak dibaca sebagai "tidak ada
  perubahan".

---

### Q4 — "Hapus spam" di `/admin/pesan-masuk`: hapus sungguhan atau soft delete?

**Pertanyaan:** `docs/07 §A10` menyebut aksi "hapus spam". `docs/04` tidak memberi
`contact_messages` kolom soft delete, sedangkan `.claude/rules/30` melarang menghapus baris audit
dan mewajibkan soft delete pada data yang bisa disesali.

**Rekomendasi: soft delete, dengan migration menambah `deleted_at`.**

Pesan kontak adalah **pesan dari calon pelanggan**, bukan baris audit — tetapi juga bukan data
sekali pakai. Salah tandai spam pada pesan yang sungguhan berarti kehilangan pelanggan tanpa
jejak, dan tidak ada cara memulihkannya. Soft delete membuatnya bisa dikembalikan, dan biayanya
satu kolom.

Ini menambah **satu migration** ke F2.2 yang menurut roadmap "tidak ada migrasi data" — perlu
persetujuan Anda karena menyimpang dari rencana.

- **Keputusan saya:** [x] **HAPUS SUNGGUHAN.** Rekomendasi soft delete **ditolak** —
  keputusan pemilik proyek, final.
- **Catatan:** Konsekuensi yang diterima secara sadar: pesan yang salah ditandai spam **hilang
  permanen dan tidak bisa dikembalikan**. Tidak ada migration untuk `contact_messages`, dan
  roadmap "tidak ada migrasi data" tetap berlaku utuh — ini keuntungan nyata dari keputusan ini.

  Dua pengaman yang tetap dipasang, karena tidak melawan keputusan dan biayanya nol:
  1. **`ConfirmDialog` wajib**, dengan tombol bertuliskan aksinya — "Hapus Pesan", bukan "OK"
     (`.claude/rules/40`). Dialognya **menampilkan nama pengirim dan cuplikan isi pesan**,
     supaya yang dihapus terlihat sebelum ditekan, bukan sesudah.
  2. **Penghapusan dicatat di `activity_log` (A12)** — isinya hilang, tetapi fakta bahwa
     seseorang menghapusnya, kapan, dan pesan dari siapa tetap punya jejak. Ini juga yang
     membuat aksi ini tidak menjadi lubang senyap.

---

### Q5 — Testimoni: diketik admin, atau dikirim pelanggan?

**Pertanyaan:** `testimonials` sekarang hanya terisi seeder, dan tidak ada form publik untuk
mengirimnya. `docs/07 §A10` memberi aksi "terbitkan/sembunyikan" — kata yang biasanya berarti
ada antrean moderasi.

**Rekomendasi: diketik admin, tanpa alur moderasi.**

Tidak ada form publik yang mengirim testimoni, dan membangunnya bukan bagian F2.2. "Terbitkan/
sembunyikan" karena itu cukup berarti saklar `is_published` pada baris yang admin ketik sendiri
— bukan kotak masuk yang menunggu persetujuan. Bila kelak pelanggan boleh mengirim, alur
moderasinya ditambahkan saat itu di atas kolom yang sudah ada.

- **Keputusan saya:** [x] **Diketik admin, tanpa alur moderasi.** `is_published` hanyalah saklar
  tampil/sembunyi di landing page.
- **Catatan:** Tidak ada form testimoni publik di F2.2, dan tidak ada kotak masuk moderasi.

---

### Q6 — Urutan fasilitas & FAQ: seret-lepas atau kolom angka?

**Pertanyaan:** `facilities` dan `faqs` punya `sort_order`. Proyek sudah punya pola seret-lepas
di galeri katalog (`ReorderCarModelImageRequest`).

**Rekomendasi: seret-lepas, memakai ulang pola galeri yang sudah ada.**

Kolom angka selalu berakhir dengan dua baris bernilai sama dan urutan yang tidak bisa ditebak.
Polanya sudah terbukti di katalog dan tinggal disalin — jadi ini bukan penambahan lingkup yang
berarti. Bila ternyata memakan waktu, kolom angka adalah jalur mundur yang sah.

- **Keputusan saya:** [x] **Seret-lepas**, memakai ulang pola galeri katalog.
- **Catatan:** Testimoni juga punya `sort_order` — ikut memakai pola yang sama.

---

### Q7 — Retensi 12 bulan A12: siapa yang menjalankannya?

**Pertanyaan:** `docs/07 §A12` menetapkan retensi **12 bulan**. `spatie/laravel-activitylog`
menyediakan perintah `activitylog:clean`, tetapi perintah tidak berjalan sendiri — ia butuh
penjadwal. Belum pernah ada pekerjaan terjadwal di proyek ini, dan belum jelas apakah runtime
Railway menjalankan `schedule:work`.

**Rekomendasi: daftarkan di `routes/console.php` sekarang, jangan pasang penjadwalnya di F2.2.**

Perintahnya didaftarkan (`Schedule::command('activitylog:clean')->daily()`) supaya niatnya
terekam di kode, tetapi **memastikan penjadwal benar-benar hidup di Railway adalah pekerjaan
F2.5 Pengerasan & Go-Live**, bersama backup dan CSP. Alasannya: dengan volume satu bengkel,
log yang tidak dibersihkan selama beberapa bulan tidak membahayakan apa pun — sedangkan
menyiapkan proses penjadwal terpisah di Railway adalah urusan infrastruktur yang tidak
sebanding bila diselipkan ke F2.2.

⚠️ Potensi masalah: retensi yang hanya tertulis di dokumen tetapi tidak pernah berjalan adalah
janji kosong → kalau tidak dicatat, tidak akan ada yang ingat → usulan: tambahkan butir eksplisit
di F2.5 dan sebutkan di `docs/07 §A12` bahwa penjadwalnya aktif sejak F2.5.

- **Keputusan saya:** [x] **Daftarkan `activitylog:clean` di `routes/console.php` sekarang;
  penjadwalnya diaktifkan di F2.5.**
- **Catatan:** Ini soal **cron pembersih log**, bukan target jadwal pengerjaan — dua hal
  berbeda yang kebetulan sama-sama disebut "jadwal". Butir F2.5 ditambahkan supaya retensi
  12 bulan tidak berhenti sebagai janji di dokumen.

---

### Q8 — Reset password staf: password sementaranya ditampilkan di layar?

**Pertanyaan:** A11 punya aksi "reset password", dan akun staf baru dibuat dengan password acak
buatan server. Keputusan final #4 melarang notifikasi email — jadi tidak ada yang mengirimkan
password itu ke pemiliknya. Bagaimana password sementara sampai ke tangan staf yang bersangkutan?

**Rekomendasi: tampilkan sekali di layar, tepat setelah aksi berhasil, lalu tidak pernah lagi.**

Ini pola yang sama dengan reset password customer di A6 (`CustomerAccountService`), jadi bukan
mekanisme baru. Yang penting dijaga:

- Password muncul **satu kali** di dalam kotak hasil aksi, dengan peringatan bahwa ia tidak
  bisa dilihat lagi. Tidak disimpan dalam bentuk terbaca di mana pun — `CustomerAccountService`
  sudah bekerja begitu.
- **Tidak pernah masuk `activity_log`.** A12 mencatat *bahwa* password direset, bukan isinya
  (`.claude/rules/50` — log tidak boleh memuat password).
- Tidak masuk flash message yang tersimpan di sesi lebih lama dari satu tampilan.
- Penyampaiannya ke staf lewat WhatsApp/lisan oleh SA, sama seperti alur customer di
  `docs/09 §9.2`.

Alternatif yang saya **tidak** rekomendasikan: membiarkan SA mengetik sendiri password staf.
Itu berarti SA mengetahui password orang lain secara permanen, dan `docs/07 §A11` sudah
menutup jalur itu ("password **tidak** diisi admin").

- **Keputusan saya:** [x] **Tampilkan sekali di layar tepat setelah aksi berhasil, lalu tidak
  pernah lagi.**
- **Catatan:** Empat syarat yang mengikat, seluruhnya sudah tertulis di rekomendasi dan menjadi
  bagian DoD butir ini: tampil **satu kali** dengan peringatan bahwa ia tidak bisa dilihat lagi ·
  **tidak pernah masuk `activity_log`** (A12 mencatat *bahwa* password direset, bukan isinya) ·
  tidak tersimpan terbaca di mana pun · disampaikan SA ke staf lewat WhatsApp atau lisan.

  ⚠️ Sudah tercatat di [tabel risiko `docs/10`](../10-roadmap-implementasi.md#risiko): password
  staf lebih berat daripada kasus customer, karena yang dibuka adalah panel internal. Yang
  memperpendek masa berlakunya adalah `must_reset_password` yang ditegakkan di butir 2.2.5 —
  password sementara itu hanya sah untuk satu kali masuk.

---

## Log Keputusan

Ditutup 4 Agustus 2026.

| # | Keputusan | Alasan singkat |
|---|-----------|----------------|
| Q1 | **F2.2 tetap satu sub-fase**; estimasi hari tidak dipakai sebagai target, prioritas selesai cepat. A14 dikerjakan **di depan** sebagai kerangka uji yang merah lalu menghijau | Pemilik proyek menolak pemecahan; A14-di-depan menggantikan pengaman yang hilang |
| Q2 | **Utang login/role masuk F2.2**, digabung butir 2.2.5 | Menyentuh berkas dan alur yang sama; terpisah berarti kerja dua kali |
| Q3 | **Tidak ada pengisian mundur** activity log; **status booking tidak dicatat A12** | Audit hasil tebakan lebih berbahaya daripada audit yang jujur; dua sumber kebenaran = cacat B2 |
| Q4 | **Hapus spam = hapus sungguhan**, bukan soft delete | Keputusan pemilik proyek; rekomendasi soft delete ditolak. Dikawal `ConfirmDialog` + jejak di activity log |
| Q5 | **Testimoni diketik admin**, tanpa moderasi | Tidak ada form publik yang mengirimnya |
| Q6 | **Seret-lepas** untuk `sort_order` fasilitas, FAQ, testimoni | Pola sudah ada di galeri katalog; kolom angka selalu berakhir bentrok |
| Q7 | **`activitylog:clean` didaftarkan sekarang, penjadwalnya diaktifkan di Tahap 12** | Volume satu bengkel tidak terancam log yang menumpuk beberapa bulan; menyiapkan proses cron di Railway urusan infrastruktur |
| Q8 | **Password sementara staf tampil sekali di layar**, tidak pernah masuk activity log | Tanpa email (keputusan final #4) tidak ada jalur lain; SA mengetik sendiri password orang lain jauh lebih buruk |
| — | **Seluruh target berbasis hari dihapus dari dokumen** atas permintaan pemilik proyek | Angka hari menciptakan tekanan menyatakan selesai sebelum uji dan otorisasi beres. Selesai diukur dari DoD, bukan durasi |
| — | **Roadmap ditata ulang tanpa pembagian Big Fase** — satu urutan 12 tahap | Permintaan pemilik proyek. Kode `Fx.y` dipertahankan sebagai label tetap karena dirujuk ±350 kali |

## Yang berubah di `docs/` akibat sesi ini

Dicatat supaya PRD tinggal menyalin, dan supaya tidak ada yang terlewat saat implementasi.

| Berkas | Perubahan | Status |
|--------|-----------|--------|
| `docs/10` butir **2.2.5** | Ditambah perbaikan tujuan setelah login menurut role + `role:customer` pada grup rute customer (Q2) | ✅ sudah |
| `docs/04 §4.2` | `activity_log` **tidak** mencatat perubahan status booking — itu milik `booking_status_histories` (Q3) | ⏳ saat implementasi |
| `docs/07 §A10` | Testimoni diketik admin, tanpa moderasi (Q5); "hapus spam" = hapus permanen berkawal `ConfirmDialog` (Q4) | ⏳ saat implementasi |
| `docs/07 §A12` | Pencatatan dimulai sejak Tahap 9, tanpa pengisian mundur; penjadwal retensi aktif di Tahap 12 (Q3, Q7) | ⏳ saat implementasi |
| `docs/10` Tahap 12 | Butir baru: aktifkan penjadwal `activitylog:clean` di Railway (Q7) | ⏳ saat implementasi |
