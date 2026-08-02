# Aturan 00 — Konteks Proyek

## Apa yang sedang dibangun

Website Chery Arta: profil perusahaan + booking servis untuk pelanggan + panel internal bengkel.
Menggantikan prototipe lama `index (1).html` (satu berkas HTML + Firebase).

**Tumpukan:** Laravel 12 · Inertia.js 2 · React 19 + TypeScript · Tailwind 4 · MySQL 8 · shadcn/ui

**Rancangan lengkap ada di `docs/`. Baca dokumen yang relevan sebelum mengubah kode:**

| Kalau menyentuh… | Baca dulu |
|------------------|-----------|
| Aturan slot, status booking, invoice | `docs/05-alur-bisnis.md` |
| Tabel, kolom, relasi | `docs/04-skema-database.md` |
| Rute, service, konfigurasi | `docs/03-arsitektur-teknis.md` |
| Warna, komponen, tata letak | `docs/06-desain-ui-ux.md` |
| Layar admin | `docs/07-modul-admin.md` |
| Pesan WhatsApp | `docs/08-notifikasi-whatsapp.md` |
| Hak akses | `docs/09-keamanan-hak-akses.md` |

## Keputusan yang sudah final — jangan diubah tanpa persetujuan

1. **Tidak ada Firebase.** Seluruh data di MySQL, diakses hanya dari server.
2. **Inertia, bukan API terpisah.** Tidak ada Sanctum token, tidak ada `routes/api.php` publik.
3. **Aturan slot dipertahankan persis dari sistem lama**: maks 2 booking per jam, wajib H-1,
   Minggu tutup, slot 08:00–14:00. Nilainya hidup di `config/booking.php` — **jangan pernah**
   menulis angka ini langsung di kode.
4. **Notifikasi WhatsApp = klik-to-chat** (`wa.me`), dipicu manual oleh admin. Tidak ada gateway,
   tidak ada pengiriman otomatis, tidak ada email notifikasi.
5. **Login pakai email + password**; nomor WhatsApp wajib saat registrasi dan disimpan
   ternormalisasi (`62…`).
6. **Dua role internal**: `super_admin` dan `service_advisor`, ditambah `customer`.
7. **Bahasa antarmuka: Indonesia.** Zona waktu `Asia/Jakarta`, mata uang IDR.

## Data perusahaan (jangan diubah isinya)

Ada di `config/company.php`, dikutip dari sistem lama:

```
Chery Arta
Jl. Jend. Sudirman No.1, Kranji, Kec. Bekasi Bar., Kota Bekasi, Jawa Barat
(021) 38317201 · 0895-4045-46904
Cheryaftersales.10254719@gmail.com
Senin–Jumat 08:00–16:00 · Sabtu 08:00–14:00 · Minggu tutup
15+ tahun pengalaman · Teknisi Bersertifikat · Peralatan Modern · Layanan Cepat · Garansi Kualitas
```

Bila sebuah halaman butuh alamat/telepon/jam, ambil dari shared props — **jangan** menulisnya
langsung di JSX.

## Cacat sistem lama yang tidak boleh terulang

Daftar lengkap ada di `docs/01-analisis-sistem-lama.md §1.3`. Yang paling sering tergoda terulang:

- Password atau logika otorisasi di sisi klien (S1, S3)
- Menyisipkan data pengguna sebagai HTML mentah (S5, S6)
- Validasi hanya di browser (B3)
- Angka aturan bisnis ditulis di lebih dari satu tempat (B2)
- Perhitungan tanggal memakai UTC, bukan `Asia/Jakarta` (B7)
- Memeriksa kuota lalu menulis tanpa penguncian transaksi
