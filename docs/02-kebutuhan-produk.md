# 02 — Kebutuhan Produk (PRD)

## 2.1 Masalah yang Diselesaikan

Prototipe lama sudah bisa menerima booking, tetapi berhenti sebagai *form pengumpul data*:
pelanggan tidak punya akun, tidak tahu perkembangan mobilnya, dan bengkel tidak punya
riwayat per kendaraan. Semua pengetahuan operasional (siapa yang mengubah apa, unit mana yang
sudah pernah servis apa) hilang begitu data ditulis.

Sasaran sistem baru:

| # | Tujuan | Indikator keberhasilan |
|---|--------|------------------------|
| G1 | Booking bisa diselesaikan sendiri oleh pelanggan tanpa telepon | ≥ 70% booking masuk lewat web, bukan telepon/WA |
| G2 | Pelanggan tahu status mobilnya tanpa menelepon | Pertanyaan "mobil saya bagaimana?" turun; setiap booking punya timeline yang terlihat |
| G3 | Bengkel punya riwayat servis per kendaraan | 100% booking selesai terhubung ke satu unit kendaraan ber-plat |
| G4 | Landing page meyakinkan calon pelanggan baru | Halaman katalog & fasilitas terindeks Google, ada jalur kontak WA satu klik |
| G5 | Operasional harian tertelusur | Setiap perubahan status tercatat: siapa, kapan, dari status apa ke apa |

## 2.2 Persona

### P1 — Rani, pemilik Tiggo 8 (Customer)
Umur 34, karyawan swasta, akses lewat **HP**. Tidak paham istilah bengkel. Ingin: pilih tanggal,
dapat kepastian jam, dan tahu kapan mobilnya selesai. Frustrasi bila harus mengetik ulang data
plat setiap servis, atau harus menelepon untuk tanya progres.

### P2 — Andre, Service Advisor
Umur 27, di depan komputer bengkel sepanjang hari. Menerima 5–15 booking/hari. Ingin: satu layar
berisi jadwal hari ini, ubah status secepat mungkin, dan kabari pelanggan lewat WA tanpa mengetik
ulang pesan. Frustrasi bila harus buka banyak halaman untuk satu pekerjaan.

### P3 — Pak Hendra, Kepala Bengkel (Super Admin)
Umur 45, membuka sistem 2–3 kali sehari dan setiap akhir bulan. Ingin: tahu berapa unit masuk
minggu ini, paket apa yang paling laku, dan bisa menarik data ke Excel untuk laporan ke prinsipal.
Tidak mau mengurus hal teknis.

## 2.3 Riset Kebutuhan — Landing Page Bengkel/Dealer Service (scope kecil)

Pola baku yang dipakai website *after-sales* otomotif berskala kecil. Kolom prioritas memakai
MoSCoW: **M**ust / **S**hould / **W**on't (fase ini).

| # | Komponen | Prioritas | Alasan kebutuhan |
|---|----------|-----------|------------------|
| L1 | **Hero + satu CTA dominan** ("Booking Servis Sekarang") | M | Halaman jasa dengan satu ajakan tunggal mengonversi lebih baik daripada banyak pilihan sejajar |
| L2 | **Trust signal di layar pertama**: 15+ tahun pengalaman, teknisi bersertifikat, garansi | M | Pengunjung menilai kredibilitas bengkel dalam hitungan detik; sudah tersedia di konten lama |
| L3 | **Daftar layanan + estimasi durasi** | M | Pertanyaan pertama pelanggan: "servis apa saja, berapa lama?" |
| L4 | **Katalog mobil (etalase produk)** | M | Diminta eksplisit; sekaligus menangkap pencarian "harga Tiggo 8 Bekasi" |
| L5 | **Galeri fasilitas** | M | Sudah ada 8 foto; fasilitas ruang tunggu adalah pembeda utama bengkel resmi |
| L6 | **Alur "Cara Booking" 3 langkah** | M | Mengurangi keraguan pengguna awam sebelum mendaftar akun |
| L7 | **Lokasi + peta + jam operasional** | M | Kebutuhan bisnis lokal; wajib untuk pencarian "bengkel Chery Bekasi" |
| L8 | **Tombol WhatsApp mengambang** | M | Jalur kontak paling dipakai di Indonesia; nol biaya |
| L9 | **FAQ** | S | Menurunkan pertanyaan berulang + menambah kata kunci ekor panjang |
| L10 | **Testimoni pelanggan** | S | Bukti sosial; bisa diisi manual oleh admin di awal |
| L11 | **SEO teknis**: meta tag, Open Graph, `schema.org/AutoRepair`, sitemap.xml, robots.txt | S | Tanpa ini, halaman bagus pun tidak ditemukan |
| L12 | **Form kontak/pertanyaan** | S | Untuk pengunjung yang belum siap membuat akun |
| L13 | **Halaman promo/artikel tips perawatan** | W | Butuh komitmen produksi konten; ditunda |
| L14 | **Live chat / chatbot** | W | Tidak sepadan untuk skala ini; WA sudah menutupi kebutuhan |
| L15 | **Multi-bahasa (EN)** | W | Pasar lokal, tidak dibutuhkan |

### Kebutuhan non-fungsional landing page

| Aspek | Target |
|-------|--------|
| Mobile-first | Lalu lintas didominasi HP; semua layout dirancang dari lebar 360px ke atas |
| Kecepatan | LCP < 2,5 detik pada 4G; gambar `webp`, lazy-load, ukuran responsif |
| Aksesibilitas | Kontras teks ≥ 4.5:1, seluruh fungsi bisa diakses keyboard, `alt` pada tiap gambar |
| SEO | Judul unik per halaman, URL bersih (`/katalog/tiggo-8-pro`) |

## 2.4 Riset Kebutuhan — Admin Internal Bengkel (scope kecil)

| # | Modul | Prioritas | Alasan kebutuhan |
|---|-------|-----------|------------------|
| A1 | **Dashboard ringkas**: booking hari ini, menunggu konfirmasi, sedang dikerjakan, selesai bulan ini, tren 30 hari | M | Layar pertama harus menjawab "apa yang harus saya kerjakan hari ini" |
| A2 | **Manajemen booking**: daftar + filter + detail + ubah status + tugaskan advisor | M | Inti pekerjaan harian; menggantikan tabel admin lama |
| A3 | **Jadwal harian / okupansi slot** | M | Advisor perlu melihat slot mana yang penuh sebelum menerima booking telepon |
| A4 | **Katalog mobil** (CRUD + varian + galeri) | M | Diminta eksplisit |
| A5 | **Paket layanan** (CRUD: nama, kategori, durasi, harga) | M | Menggantikan dropdown hardcode |
| A6 | **Customer & kendaraan**: cari, lihat riwayat servis per unit | M | Basis nilai jangka panjang sistem |
| A7 | **Notifikasi WhatsApp**: draft pesan otomatis + log kirim | M | Diminta eksplisit |
| A8 | **Estimasi biaya & invoice** | M | Diminta eksplisit di fitur customer |
| A9 | **Export laporan** (Excel/CSV) | M | Feature parity dengan sistem lama |
| A10 | **Konten landing**: fasilitas, FAQ, testimoni, pesan kontak masuk | S | Agar konten tidak perlu programmer untuk diubah |
| A11 | **Manajemen user internal + role** | S | Hanya Super Admin; kecil tapi wajib untuk jejak audit |
| A12 | **Activity log** | S | Menjawab "siapa yang menghapus booking ini" |
| A13 | **Booking walk-in** (dibuat admin untuk pelanggan yang datang langsung) | S | Realitas bengkel: tidak semua pelanggan booking online |
| A14 | Penugasan mekanik per pekerjaan | W | Butuh role ketiga; ditunda |
| A15 | Manajemen stok sparepart | W | Modul besar tersendiri; di luar scope |
| A16 | Reminder servis berkala otomatis | W | Butuh penjadwal + gateway WA otomatis; ditunda |

## 2.5 User Story

Format: *Sebagai [peran], saya ingin [kebutuhan], agar [manfaat]* + kriteria penerimaan.

### Customer

**US-C1 — Registrasi**
Sebagai calon pelanggan, saya ingin mendaftar dengan email dan nomor WhatsApp, agar bisa booking.
- Field wajib: nama, email (unik), nomor WhatsApp (unik, dinormalisasi ke `62…`), password (min. 8 karakter).
- Email yang sudah terdaftar ditolak dengan pesan jelas, bukan error mentah.
- Nomor WA divalidasi format Indonesia (`08…`, `62…`, atau `+62…`).
- Setelah daftar, pengguna langsung masuk dan diarahkan ke halaman yang tadi dituju.

**US-C2 — Login & sesi**
Sebagai pelanggan, saya ingin login dengan email dan password, agar data saya tersimpan.
- Ada "Ingat saya"; sesi kedaluwarsa mengembalikan ke halaman login dengan pesan.
- Gagal login 5 kali dalam 1 menit → dibatasi (rate limit).
- Percobaan menuju `/booking` tanpa login diarahkan ke login, lalu **kembali ke `/booking`** setelah berhasil.

**US-C3 — Kelola kendaraan**
Sebagai pelanggan, saya ingin menyimpan data mobil saya, agar tidak mengetik plat berulang kali.
- Bisa menambah > 1 kendaraan; salah satu bisa ditandai utama.
- Plat disimpan dalam 3 segmen (kode wilayah 1–2 huruf, nomor 1–4 angka, seri 1–3 huruf).
- Model dipilih dari katalog Chery; bila bukan Chery, boleh diisi manual.

**US-C4 — Booking servis**
Sebagai pelanggan, saya ingin memesan jadwal servis, agar tidak perlu antre.
- Data diri terisi otomatis dari akun; kendaraan dipilih dari daftar milik saya.
- Tanggal minimal H+1; hari Minggu tidak bisa dipilih (dinonaktifkan di kalender **dan** ditolak server).
- Slot yang kuotanya penuh (2 booking) ditampilkan sebagai tidak tersedia, bukan baru ditolak setelah submit.
- Setelah berhasil: muncul kode booking `CA-YYYYMMDD-NNNN` dan ringkasan.

**US-C5 — Riwayat & tracking**
Sebagai pelanggan, saya ingin melihat status servis saya, agar tidak perlu menelepon.
- Daftar booking saya: mendatang & riwayat, dengan badge status.
- Halaman detail memuat **timeline**: kapan dibuat, dikonfirmasi, mulai dikerjakan, selesai — beserta catatan advisor.
- Riwayat bisa disaring per kendaraan.

**US-C6 — Reschedule & batalkan**
Sebagai pelanggan, saya ingin mengubah atau membatalkan jadwal sendiri, agar tidak merepotkan admin.
- Diizinkan hanya saat status `pending` atau `confirmed`, dan minimal H-1 sebelum tanggal booking.
- Reschedule = membuat jadwal baru yang tetap merujuk booking lama (`rescheduled_from_id`), kuota slot lama dilepas.
- Pembatalan wajib menyertakan alasan (pilihan + isian bebas).

**US-C7 — Estimasi biaya & invoice**
Sebagai pelanggan, saya ingin melihat rincian biaya, agar tidak ada kejutan saat membayar.
- Sebelum servis: estimasi dari harga paket.
- Setelah selesai: rincian jasa + sparepart, subtotal, diskon, total; bisa diunduh PDF.

### Service Advisor

**US-A1 — Jadwal hari ini**: melihat seluruh booking hari ini beserta okupansi tiap slot dalam satu layar.
**US-A2 — Ubah status**: mengubah status booking dengan catatan opsional; sistem menyiapkan draft pesan WA.
**US-A3 — Booking walk-in**: membuat booking atas nama pelanggan yang datang langsung, termasuk membuat akun pelanggan baru bila belum ada.
**US-A4 — Cari cepat**: mencari booking berdasarkan kode, nama, atau plat dari kolom pencarian global.
**US-A5 — Invoice**: menyusun rincian biaya untuk booking yang selesai dan menerbitkannya.

### Super Admin

**US-S1 — Semua wewenang Service Advisor**, ditambah:
**US-S2 — Katalog & paket**: mengelola model mobil, varian, galeri, serta paket layanan beserta harga & durasi.
**US-S3 — Pengguna internal**: membuat/menonaktifkan akun advisor.
**US-S4 — Laporan**: melihat rekap per periode dan mengekspornya ke Excel.
**US-S5 — Konten**: mengelola fasilitas, FAQ, testimoni, dan membaca pesan kontak masuk.
**US-S6 — Audit**: melihat riwayat perubahan data penting.

## 2.6 Batas Scope (Won't do — fase ini)

Pembayaran online/payment gateway · stok & pembelian sparepart · penugasan & absensi mekanik ·
reminder servis otomatis terjadwal · aplikasi mobile · multi-cabang · program loyalitas/poin ·
integrasi DMS prinsipal · chatbot.

## 2.7 Kriteria Penerimaan Global

1. Semua fitur pada tabel §1.2 sistem lama tersedia atau punya penggantinya yang setara.
2. Seluruh cacat pada §1.3 tidak terulang, dan diuji otomatis untuk S1–S3, B1–B3, B7.
3. Semua halaman publik dapat dipakai penuh pada layar 360px.
4. Tidak ada kredensial atau logika otorisasi di sisi klien.
5. Setiap perubahan status booking meninggalkan jejak: siapa, kapan, dari apa ke apa.
