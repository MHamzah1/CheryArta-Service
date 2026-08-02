---
description: Bandingkan sistem baru dengan prototipe lama — pastikan tidak ada fitur yang hilang dan tidak ada cacat lama yang terulang
---

Audit kesetaraan fitur terhadap prototipe lama `index (1).html`.

## 1. Paritas fitur

Buka `docs/01-analisis-sistem-lama.md §1.2` (18 fitur F1–F18). Untuk setiap baris, periksa di
kode yang ada sekarang:

| Status | Arti |
|--------|------|
| ✅ Ada | Fitur tersedia dan berfungsi |
| ⚠️ Sebagian | Ada tapi belum setara |
| ❌ Hilang | Belum dibuat |
| ➖ Sengaja dibuang | Tercantum di §1.4 |

Sajikan sebagai tabel. Untuk yang ⚠️ atau ❌, sebutkan persisnya apa yang kurang.

## 2. Cacat lama tidak terulang

Periksa satu per satu terhadap `docs/01-analisis-sistem-lama.md §1.3`:

**Keamanan (S1–S8)**
- Ada kredensial di kode frontend?
- Ada keputusan otorisasi yang hanya di klien?
- Ada `dangerouslySetInnerHTML` untuk data pengguna?
- Ada endpoint yang mengembalikan data pelanggan lain?

**Fungsional (B1–B10)**
- Plat 2 huruf bisa diinput? (uji `AB-1234-CD`)
- Aturan tanggal hanya bersumber dari `config/booking.php`?
- Hari Minggu ditolak di server, bukan hanya di UI?
- Perhitungan tanggal memakai `Asia/Jakarta`?

**Data (D1–D6)**
- Kode booking ada dan unik?
- Riwayat status tercatat lengkap?
- Paket layanan berasal dari database, bukan hardcode?

## 3. Informasi perusahaan

Bandingkan dengan `docs/01-analisis-sistem-lama.md §1.5` — alamat, dua nomor telepon, email,
jam operasional, klaim 15+ tahun, 4 keunggulan, dan 8 judul fasilitas harus **persis sama**.
Laporkan setiap perbedaan, sekecil apa pun.

## 4. Keluaran

Ringkasan berisi: jumlah fitur ✅/⚠️/❌, daftar cacat lama yang terdeteksi terulang (bila ada),
selisih informasi perusahaan, dan daftar tindakan yang perlu dikerjakan diurutkan dari yang
paling penting. Jangan memperbaiki apa pun sebelum ringkasan ini ditinjau, kecuali diminta.
