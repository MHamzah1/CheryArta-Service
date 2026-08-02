# 01 — Analisis Sistem Lama

Sumber: `index (1).html` — 3.141 baris, satu berkas tunggal berisi CSS, markup, dan dua blok script.

## 1.1 Profil Teknis

| Aspek | Kondisi |
|-------|---------|
| Arsitektur | Single-page statis, satu berkas HTML, tanpa build step |
| Backend | Firebase Realtime Database (`asia-southeast1`), diakses langsung dari browser |
| Autentikasi | Perbandingan string password di sisi klien (`password === 'cheryarta01'`) |
| Sesi | `localStorage.adminLoggedIn` + `adminLoginTime`, masa berlaku 24 jam |
| Navigasi | Menyembunyikan/menampilkan `<section>` dengan `display: none/block` + `sessionStorage.currentPage` |
| Dependensi | Font Awesome 6.4, Google Fonts Inter, SheetJS 0.18.5, Firebase JS SDK 10.13.1 |
| Model data | Node tunggal `bookings/{timestamp}` berisi 9 field datar |

Struktur data satu-satunya:

```json
"bookings": {
  "1735689600000": {
    "name": "Budi", "Plat_Nomor": "B-1234-ABC", "phone": "0812-3456-7890",
    "model": "Tiggo 8 Pro", "service": "Tiggo_8_Free",
    "date": "2026-01-05", "time": "09:00",
    "Keluhan": "AC kurang dingin", "status": "Successful"
  }
}
```

## 1.2 Inventaris Fitur — Wajib Dibawa (feature parity)

Daftar ini adalah kontrak minimum: sistem baru **tidak boleh** kehilangan satu pun.

| # | Fitur lama | Lokasi lama | Nasib di sistem baru |
|---|-----------|-------------|----------------------|
| F1 | Hero + CTA "Book Now" | baris 1465–1479 | Dipertahankan, desain diperbarui |
| F2 | Slider 8 fasilitas + swipe + autoplay 5 detik | 1481–1554, 2069–2251 | Dipertahankan, sumber data pindah ke tabel `facilities` |
| F3 | Modal preview gambar fasilitas + navigasi prev/next | 1977–1992, 2407–2444 | Dipertahankan (lightbox) |
| F4 | Form booking 8 field + plat nomor 3 segmen | 1556–1664 | Dipertahankan, ditambah pilihan kendaraan tersimpan |
| F5 | Validasi plat, telepon, tanggal H-1, hari Minggu | 2268–2303, 3128–3136 | Dipindah ke server (Form Request), tetap ada versi klien |
| F6 | Kuota maksimal 2 booking per slot jam | 2305–2321 | Dipertahankan persis, dipindah ke server |
| F7 | Dropdown paket layanan berkelompok (10 opsi) | 1596–1619 | Pindah ke tabel `service_packages`, dikelola admin |
| F8 | "Cek Service" — cari status via plat nomor | 1756–1802, 2533–2563 | Diganti tracking di akun customer + pencarian publik tetap ada |
| F9 | Panel admin: tabel booking + kartu versi mobile | 1804–1892, 2720–2825 | Dipertahankan, jadi modul Manajemen Booking |
| F10 | Filter admin (nama/plat, status, rentang tanggal) | 1828–1865, 2686–2708 | Dipertahankan, filter dijalankan di server |
| F11 | Edit booking lewat modal | 1906–1975, 3014–3043 | Dipertahankan + pencatatan riwayat perubahan |
| F12 | Hapus booking | 2843–2852 | Diganti *soft delete* + alasan pembatalan |
| F13 | Export Excel | 2854–2897 | Dipertahankan (server-side, `maatwebsite/excel`) |
| F14 | Export ke Google Sheets via clipboard TSV | 2899–2960 | Dipertahankan sebagai "Salin untuk Spreadsheet" |
| F15 | Refresh data | 2827–2841, 2565–2609 | Tidak relevan lagi (data selalu segar per request) |
| F16 | Profil perusahaan: tentang, 4 keunggulan, kontak, jam operasional | 1666–1754 | Dipertahankan **persis**, lihat §1.5 |
| F17 | Menu mobile hamburger | 1438–1462, 2498–2519 | Dipertahankan |
| F18 | Header transparan → solid saat scroll | 3045–3052 | Dipertahankan |

## 1.3 Temuan Cacat — Wajib Diperbaiki

Setiap temuan di bawah menjadi **requirement** sistem baru, bukan sekadar catatan.

### Keamanan

| ID | Temuan | Baris | Requirement baru |
|----|--------|-------|------------------|
| S1 | Password admin `cheryarta01` tertulis di kode, terlihat via view-source | 3002 | Hash bcrypt di tabel `users`, verifikasi di server |
| S2 | Field email di form login diminta tapi tidak pernah diperiksa | 2999–3002 | Login memvalidasi email **dan** password |
| S3 | Otorisasi hanya di klien — `localStorage.setItem('adminLoggedIn','true')` cukup untuk masuk panel | 2972–2985 | Session server-side + middleware + Policy per aksi |
| S4 | Kredensial Firebase & URL database terbuka; bila rules permisif, seluruh data (nama, HP, plat) bisa dibaca/dihapus siapa saja | 2005–2014 | Database di belakang server, tidak pernah diakses langsung dari browser |
| S5 | XSS — data booking disisipkan lewat `innerHTML` tanpa escaping | 2638, 2758, 2782 | React meng-escape secara default; `dangerouslySetInnerHTML` dilarang (lihat rule keamanan) |
| S6 | Injeksi atribut — `JSON.stringify(booking)` ditanam ke dalam `onclick`, hanya `'` yang di-escape | 2769, 2815 | Tidak ada handler berbasis string; event handler React |
| S7 | SheetJS 0.18.5 mengandung kerentanan prototype pollution (diperbaiki di ≥ 0.19.3) | 11 | Export dipindah ke server, pustaka klien dihapus |
| S8 | Data booking pelanggan lain bisa dibaca lewat fitur "Cek Service" (mengambil seluruh node lalu memfilter di klien) | 2546–2556 | Pencarian dijalankan di server, hanya mengembalikan ringkasan status |

### Fungsional

| ID | Temuan | Baris | Requirement baru |
|----|--------|-------|------------------|
| B1 | Plat 2 huruf mustahil diinput: `maxlength="1"` vs validasi `^[A-Z]{1,2}$` | 1572 vs 2284 | Segmen kode wilayah menerima 1–2 huruf |
| B2 | Dua aturan `min` tanggal saling menimpa (besok vs hari ini) | 3058–3061 vs 3126 | Satu sumber kebenaran: `config/booking.php` |
| B3 | Larangan hari Minggu hanya di listener input, tidak diperiksa saat submit | 3128–3136 | Divalidasi di server |
| B4 | `onValue` didaftarkan berulang tanpa unsubscribe → listener menumpuk | 2670, 2834, 3038 | Tidak relevan (tanpa realtime listener) |
| B5 | Memakai variabel global `event` — gagal di Firefox | 2566, 2828 | Tidak relevan |
| B6 | `.hero` dikembalikan ke `display: block` padahal CSS-nya `flex` | 2485, 2493, 2967 | Tidak relevan (routing sungguhan) |
| B7 | `toISOString()` memakai UTC → salah satu hari saat diakses dini hari WIB | 3060 | Semua perhitungan tanggal di server dengan zona `Asia/Jakarta` |
| B8 | Jumlah slide di-hardcode `8` di tiga tempat | 2081, 2086, 2092 | Turunan dari jumlah data |
| B9 | Blok CSS `select option` dideklarasikan dua kali | 455 & 460 | Tidak relevan (Tailwind) |
| B10 | Status `Successful` bukan tahapan alur kerja yang jelas dan tumpang tindih dengan `Completed` | 2323 | State machine baru, lihat [05-alur-bisnis.md](05-alur-bisnis.md) |

### Model Data

| ID | Temuan | Requirement baru |
|----|--------|------------------|
| D1 | Tidak ada entitas customer — nama, HP, plat diketik ulang tiap booking, rawan duplikasi & salah ketik | Tabel `users` + `vehicles`, data terisi otomatis saat login |
| D2 | Tidak ada kode booking yang bisa dirujuk pelanggan | `bookings.booking_code` format `CA-YYYYMMDD-NNNN` |
| D3 | Tipe mobil berupa teks bebas → laporan per model mustahil dibuat | Relasi ke `car_models`, teks manual hanya sebagai cadangan |
| D4 | Paket layanan tertanam di HTML, tanpa harga & durasi | Tabel `service_packages` dengan harga & estimasi durasi |
| D5 | Tidak ada jejak audit — tidak diketahui siapa mengubah/menghapus apa | `booking_status_histories` + activity log |
| D6 | Primary key memakai `Date.now()` → tabrakan bila dua booking di milidetik sama | Auto-increment MySQL |

## 1.4 Fitur Sistem Lama yang Sengaja Dibuang

| Fitur | Alasan |
|-------|--------|
| Tombol "Refresh" di panel admin & cek service | Data selalu diambil segar tiap request; tombol ini artefak dari listener Firebase |
| Firebase Realtime Database & seluruh SDK-nya | Digantikan MySQL, sesuai instruksi |
| Sinkronisasi realtime tabel admin | Tidak sepadan biayanya untuk 1–2 admin aktif; diganti auto-refresh polling opsional di dashboard |
| Login dengan password tunggal bersama | Diganti akun per orang agar jejak audit bermakna |

## 1.5 Informasi Perusahaan (dikutip persis, tidak boleh diubah)

```
Nama          : Chery Arta
Alamat        : Jl. Jend. Sudirman No.1, Kranji, Kec. Bekasi Bar., Kota Bekasi, Jawa Barat
Telepon       : (021) 38317201 / 0895-4045-46904
Email         : Cheryaftersales.10254719@gmail.com
Jam operasional:
  Senin - Jumat : 08:00 - 16:00
  Sabtu         : 08:00 - 14:00
  Minggu        : Tutup
Klaim          : Pengalaman lebih dari 15 tahun di industri otomotif
Keunggulan     : Teknisi Bersertifikat, Peralatan Modern, Layanan Cepat, Garansi Kualitas
```

**8 fasilitas** (judul + deskripsi dibawa apa adanya): Customer Service Center, Showroom Chery,
Ruang Tunggu Premium, Kids Play Area, Workshop Modern, Area Diskusi Santai, Ruang Konsultasi,
Lounge Entertainment.

**10 paket layanan lama** yang menjadi data awal `service_packages`:

| Kategori | Paket |
|----------|-------|
| First Maintenance ICE | First Maintenance (1.000 km/1 Bln)<br>First Maintenance (5.000 km/3 Bln) |
| First Maintenance EV | First Maintenance EV (5.000 km/6 Bln) |
| Free Maintenance | Tiggo 5X / Cross Series<br>Tiggo 8 Series<br>EV Series<br>CSH Series |
| Lainnya | Other |

**Slot waktu lama:** 08:00, 08:30, 09:00, 09:30, 10:00, 10:30, 11:00, 11:30, 13:00, 13:30, 14:00
(istirahat 12:00–13:00 — terlihat dari tidak adanya slot 12:00/12:30).

## 1.6 Rencana Data Lama

Sistem baru dimulai dari database kosong. Bila ada data produksi di Firebase yang perlu dibawa:

1. Export node `bookings` ke JSON dari Firebase Console.
2. Jalankan `php artisan booking:import-firebase path/to/bookings.json` (dibangun di Fase 6).
3. Perintah tersebut membuat akun customer *placeholder* dari pasangan nama + nomor telepon,
   membuat `vehicles` dari plat nomor, memetakan `service` lama ke `service_packages.code`,
   dan memetakan status lama: `Successful → confirmed`, `Pending → pending`,
   `In Progress → in_progress`, `Completed → completed`, `Cancelled → cancelled`.
4. Akun placeholder ditandai `must_reset_password = true`.
