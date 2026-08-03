---
description: Ubah hasil grill menjadi satu dokumen PRD lengkap — tujuan yang mau dicapai, bukan cara mencapainya
argument-hint: <topik fitur, mis. "invoice servis">
---

Tulis PRD untuk: **$ARGUMENTS**

Ini **tujuan** — ke mana kita akan sampai. Bukan rencana cara mencapainya; itu urusan
`/prd-to-issues`.

## Langkah

1. **Baca dokumen grill lebih dulu** bila ada (`docs/grills/grill-[topik].md`). Itu sumber
   kebenaran keputusan; percakapan hanya pelengkap.
2. Telusuri percakapan hanya untuk mencari hal yang belum tercatat di dokumen grill.
3. **Baca rancangan yang relevan di `docs/`** — pakai tabel penunjuk di
   `.claude/rules/00-konteks-proyek.md`. PRD tidak boleh bertabrakan dengan `docs/` tanpa
   menyatakannya terang-terangan (lihat aturan di bawah).
4. Bila masih ada yang benar-benar kabur, ajukan **SATU** pertanyaan sebelum lanjut — jangan
   mengarang.
5. Telusuri kode untuk memastikan modul mana yang akan tersentuh. Path harus nyata.
6. Tulis PRD memakai struktur di bawah.
7. Simpan sebagai `docs/prds/prd-[nama-fitur].md`.

## Struktur PRD

```markdown
# PRD: [Nama Fitur]

## Masalah
Masalah apa yang sedang diselesaikan? Siapa yang mengalaminya?
Spesifik — rujuk keluhan atau cacat yang benar-benar teramati, termasuk temuan
sistem lama di `docs/01-analisis-sistem-lama.md` bila relevan.

## Tujuan
Satu atau dua kalimat: seperti apa wujud berhasilnya?

## Latar
Konteks yang perlu diketahui pembaca: keadaan sekarang, kenapa dikerjakan sekarang,
posisinya di `docs/10-roadmap-implementasi.md` (sub-fase F1.x/F2.x dan kode modul A1–A14
bila ini modul admin).

## Selesai Bila
Daftar periksa eksplisit. Bila seluruhnya tercentang, fitur ini rilis.
Setiap butir harus bisa diperiksa manusia dalam waktu di bawah 5 menit.
- [ ] butir 1
- [ ] butir 2

## Lingkup
Apa yang dicakup PRD ini. Sebutkan terang-terangan.

## Bukan Lingkup
Apa yang **sengaja tidak** dikerjakan di sini. Bagian ini sama pentingnya dengan
bagian Lingkup — wajib ada, minimal 2–3 butir.

## Pengguna & Kebutuhannya
Peran mana yang menyentuh fitur ini: `customer`, `service_advisor`, `super_admin`,
atau pengunjung publik.

- Sebagai [peran], saya ingin [tindakan] supaya [hasil]
- Sebagai [peran], saya ingin [tindakan] supaya [hasil]

## Kebutuhan Fungsional
Daftar bernomor: apa yang harus dilakukan sistem. Kelompokkan per wilayah bila perlu
(Data, Aturan Bisnis, Hak Akses, Antarmuka).

## Kebutuhan Non-Fungsional
Isi yang benar-benar berlaku, bukan basa-basi. Untuk proyek ini biasanya menyangkut:

- **Keamanan** — Policy per sumber daya, Form Request, rate limit; rujuk
  `.claude/rules/50-keamanan.md` dan `docs/09-keamanan-hak-akses.md`
- **Waktu & uang** — `Asia/Jakarta`, `decimal(12,2)`, total dihitung di server
- **Konfigurasi** — angka aturan bisnis hanya di `config/booking.php`
- **Antarmuka** — Bahasa Indonesia, token warna dari design system, benar pada 360/768/1280
- **Kinerja** — daftar dipaginasi di server, tanpa N+1

## Keputusan yang Sudah Disetujui
Keputusan yang sudah diambil saat grill. Bernomor. **Terkunci — jangan dibuka ulang.**
Sertakan asalnya bila datang dari `docs/` atau dari keputusan final di
`.claude/rules/00-konteks-proyek.md`.

## Pertanyaan Terbuka
Yang masih menggantung dan butuh jawaban sebelum atau saat pembangunan.
Bila tidak ada, tulis "Tidak ada".

## Dampak ke Rancangan `docs/`
Apakah PRD ini mengubah skema, alur bisnis, hak akses, atau keputusan yang sudah
tertulis di `docs/`? Bila ya, sebutkan berkas dan bagian mana yang harus ikut
diperbarui. Bila tidak, tulis "Tidak ada — PRD ini sepenuhnya mengikuti rancangan".

## Modul yang Tersentuh
Path nyata, bukan deskripsi kabur. Tandai berkas baru secara eksplisit.

| Berkas | Baru / Ubah | Yang berubah |
|--------|-------------|--------------|
| `database/migrations/…` | Baru | … |
| `app/Models/….php` | Ubah | … |
| `app/Services/….php` | Baru | … |
| `app/Policies/….php` | Baru | … |
| `app/Http/Requests/….php` | Baru | … |
| `app/Http/Controllers/{Admin,Customer,Public}/….php` | Baru | … |
| `routes/web.php` | Ubah | … |
| `resources/js/pages/….tsx` | Baru | … |
| `resources/js/types/….ts` | Ubah | … |
| `tests/Feature/….php` | Baru | … |

## Risiko
Apa yang bisa meleset? Apa yang belum diketahui?
Perhatikan khusus: operasi baca-lalu-tulis yang butuh kunci transaksi, kebocoran data
pribadi ke halaman publik, dan angka aturan bisnis yang tergoda ditulis di dua tempat.

## Ukuran Keberhasilan
Bagaimana kita tahu ini berhasil setelah dipakai sungguhan?
```

## Aturan

- **Jangan melewatkan satu bagian pun** — tulis "Tidak berlaku" bila memang tidak relevan.
- **Jangan mengisi dengan basa-basi.** Setiap kalimat harus membawa informasi.
- Modul harus berupa berkas/path nyata, bukan deskripsi kabur.
- "Selesai Bila" harus bisa diperiksa manusia di bawah 5 menit per butir.
- Bagian "Bukan Lingkup" wajib ada, sekalipun hanya 2–3 butir.
- **Bila PRD ini menyimpang dari `docs/`, katakan.** `CLAUDE.md` menetapkan dokumen yang benar
  bila kode dan dokumen bertentangan — jadi penyimpangan harus muncul di bagian
  "Dampak ke Rancangan `docs/`" beserta berkas yang perlu diperbarui, bukan diselundupkan.
- Jangan menulis ulang Definition of Done proyek. DoD 12 butir di
  `docs/10-roadmap-implementasi.md` berlaku otomatis untuk setiap tugas — "Selesai Bila" hanya
  memuat kriteria khusus fitur ini.
- Sesudah PRD tersimpan, **jangan** bertanya "mulai implementasi sekarang?" — tunggu instruksi
  saya berikutnya.
