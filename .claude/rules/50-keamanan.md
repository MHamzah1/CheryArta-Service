# Aturan 50 — Keamanan

Rujukan lengkap: `docs/09-keamanan-hak-akses.md`. Aturan ini lahir dari cacat nyata sistem lama —
setiap poin punya nomor temuan asalnya.

## Larangan Mutlak

| Larangan | Asal |
|----------|------|
| Kredensial, password, atau API key di kode frontend | S1 |
| Menentukan hak akses dari `localStorage`/`sessionStorage` | S3 |
| `dangerouslySetInnerHTML` untuk data buatan pengguna | S5 |
| Menyusun handler dari string yang mengandung data pengguna | S6 |
| Mengirim seluruh isi tabel ke browser lalu memfilter di sana | S8 |
| Menerima `status`, `total`, `user_id`, atau `booking_code` dari request | — |
| `$guarded = []` pada model | — |
| Menyambung string ke dalam query SQL | — |
| Menonaktifkan CSRF pada rute yang mengubah data | — |

## Wajib Ada

1. **Otorisasi di server untuk setiap aksi.** UI menyembunyikan tombol; server yang menolak.
2. **Policy untuk setiap sumber daya.** Data milik pengguna diambil lewat relasi
   (`$user->bookings()->findOrFail()`), bukan pencarian global lalu diperiksa belakangan.
3. **Form Request untuk setiap masukan.** Tidak ada `$request->all()`.
4. **Rate limit** pada: login (5/menit), registrasi (5/jam), form kontak (5/jam),
   pelacakan publik (10/menit), endpoint slot (60/menit).
5. **Pesan galat netral** saat login gagal — jangan bocorkan email mana yang terdaftar.
6. **Regenerasi sesi** setiap login berhasil.
7. **Nomor telepon dinormalisasi** sebelum disimpan; jangan pernah menaruh input mentah ke URL `wa.me`.

## Unggahan Berkas

- Hanya `jpg|jpeg|png|webp` (brosur: `pdf`), maks 2 MB (brosur 5 MB).
- Validasi dengan `image` + `mimes` (memeriksa isi, bukan ekstensi).
- Nama berkas di-generate ulang (`Str::uuid()`), nama asli dibuang.
- Gambar diproses ulang (resize + `webp`) sebelum disimpan.
- Direktori penyimpanan tidak boleh mengeksekusi PHP.

## Data Pribadi

- Halaman publik tidak pernah menampilkan nama lengkap, nomor telepon, atau plat pelanggan.
- Pelacakan publik hanya mengembalikan kode, jadwal, dan status.
- Nomor WhatsApp hanya terlihat oleh staf yang login.
- Log jangan memuat password, token, atau isi pesan pribadi di luar `whatsapp_messages`.

## Saat Menulis Fitur Baru — periksa ini

- [ ] Siapa yang boleh memanggil ini? Sudah ada Policy/middleware-nya?
- [ ] Apakah pengguna bisa mengganti ID di URL untuk menyentuh data orang lain?
- [ ] Field apa yang datang dari request? Apakah ada yang seharusnya ditentukan server?
- [ ] Apakah ada data pribadi yang bocor ke halaman publik atau ke props Inertia yang tidak perlu?
- [ ] Apakah aksi ini perlu tercatat di activity log?
- [ ] Apakah ada operasi baca-lalu-tulis yang perlu dikunci transaksi?
