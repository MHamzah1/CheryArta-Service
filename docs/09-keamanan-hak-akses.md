# 09 — Keamanan & Hak Akses

## 9.1 Perbaikan Terhadap Celah Sistem Lama

| ID | Celah lama | Penanganan |
|----|-----------|------------|
| S1 | Password admin tertulis di kode klien | Hash bcrypt di `users.password`, verifikasi di server; tidak ada kredensial di berkas frontend |
| S2 | Email login tidak diperiksa | `Auth::attempt(['email' => …, 'password' => …, 'is_active' => true])` |
| S3 | Otorisasi hanya di klien (`localStorage`) | Session server-side + middleware `auth` & `role` + Policy per aksi; UI hanya menyembunyikan, server yang menolak |
| S4 | Kredensial database terbuka di browser | Browser tidak pernah menyentuh database; kredensial hanya di `.env` server |
| S5 | XSS lewat `innerHTML` | React meng-escape secara bawaan; `dangerouslySetInnerHTML` dilarang (lihat `.claude/rules/50-keamanan.md`) |
| S6 | Injeksi atribut lewat `onclick` berisi JSON | Event handler React, tidak ada handler berbasis string |
| S7 | SheetJS rentan di klien | Export dipindah ke server; pustaka klien dihapus |
| S8 | Seluruh data booking terbaca publik | Pelacakan publik hanya menerima kode booking dan mengembalikan ringkasan status |

## 9.2 Autentikasi

| Aspek | Ketentuan |
|-------|-----------|
| Identitas | Email + password (keputusan #5) |
| Password | Minimal 8 karakter, dicek terhadap daftar bocoran (`Password::defaults()->uncompromised()`), hash bcrypt |
| Sesi | Driver `database`, cookie `HttpOnly` + `SameSite=Lax` + `Secure` di produksi, masa 120 menit, diperpanjang saat aktif |
| Regenerasi | ID sesi diregenerasi setiap login (mencegah *session fixation*) |
| Rate limit | Login 5 percobaan/menit per (email + IP); registrasi 5/jam per IP; form kontak 5/jam per IP; pelacakan publik 10/menit per IP |
| Akun nonaktif | `is_active = false` → login ditolak dengan pesan netral |
| Reset password | Token 60 menit. **Catatan:** tanpa email notifikasi (keputusan #4), reset mandiri lewat email tetap memakai `mail` bawaan Laravel; bila SMTP tidak tersedia, alurnya adalah customer menghubungi bengkel lalu Super Admin mereset dari panel. Perlu dipastikan sebelum rilis. |
| Verifikasi email | **Tidak dipakai** — konsekuensi keputusan #4 |

## 9.3 Otorisasi

Tiga lapis, dan ketiganya harus ada:

1. **Middleware rute** — `role:super_admin`, `role:super_admin,service_advisor`.
2. **Policy per model** — `BookingPolicy`, `VehiclePolicy`, `InvoicePolicy`, `UserPolicy`.
3. **Query berbasis pemilik** — customer selalu memakai `auth()->user()->bookings()`, tidak pernah `Booking::find($id)` lalu memeriksa belakangan.

Contoh `BookingPolicy`:

```php
public function view(User $user, Booking $booking): bool
{
    return $user->isStaff() || $booking->user_id === $user->id;
}

public function reschedule(User $user, Booking $booking): bool
{
    return $booking->user_id === $user->id
        && $booking->status->isReschedulable()
        && $booking->booking_date->gt(today()->addDays(config('booking.reschedule_min_days_before') - 1));
}

public function updateStatus(User $user, Booking $booking): bool
{
    return $user->isStaff();
}
```

### Matriks hak akses

| Kemampuan | Super Admin | Service Advisor | Customer | Tamu |
|-----------|:-----------:|:---------------:|:--------:|:----:|
| Melihat landing page, katalog, layanan, FAQ | ✅ | ✅ | ✅ | ✅ |
| Melacak status via kode booking (ringkasan) | ✅ | ✅ | ✅ | ✅ |
| Membuat booking | ✅ | ✅ | ✅ | ❌ |
| Melihat booking **milik sendiri** | — | — | ✅ | ❌ |
| Melihat **semua** booking | ✅ | ✅ | ❌ | ❌ |
| Jadwal ulang / batal booking sendiri (≥H-1) | — | — | ✅ | ❌ |
| Mengubah status booking | ✅ | ✅ | ❌ | ❌ |
| Menghapus booking (soft delete) | ✅ | ❌ | ❌ | ❌ |
| Mengelola kendaraan sendiri | — | — | ✅ | ❌ |
| Melihat data seluruh customer | ✅ | ✅ | ❌ | ❌ |
| Membuat & menerbitkan invoice | ✅ | ✅ | ❌ | ❌ |
| Membatalkan (void) invoice | ✅ | ❌ | ❌ | ❌ |
| Melihat invoice sendiri | — | — | ✅ | ❌ |
| Laporan pendapatan | ✅ | ❌ | ❌ | ❌ |
| Katalog & paket layanan | ✅ | ❌ | ❌ | ❌ |
| Konten landing (fasilitas, FAQ, testimoni) | ✅ | ❌ | ❌ | ❌ |
| Pengguna internal & activity log | ✅ | ❌ | ❌ | ❌ |

## 9.4 Validasi & Input

- Seluruh masukan divalidasi lewat **Form Request**; controller tidak pernah membaca `$request->all()` mentah.
- Mass assignment dijaga `$fillable` eksplisit — `$guarded = []` dilarang.
- Kolom yang tidak boleh diisi pengguna (`status`, `booking_code`, `total`, `user_id` pada konteks customer) ditetapkan server, **tidak pernah** diambil dari request.
- Query selalu lewat Eloquent/Query Builder (parameter terikat). Bila terpaksa memakai `DB::raw`, wajib memakai *binding*, tidak boleh menyambung string.

## 9.5 Perlindungan Web

| Ancaman | Penanganan |
|---------|-----------|
| CSRF | Middleware `VerifyCsrfToken` bawaan Laravel; Inertia mengirim token `XSRF-TOKEN` otomatis |
| XSS | React escape bawaan; `dangerouslySetInnerHTML` dilarang; konten kaya (deskripsi katalog) dibersihkan dengan HTML Purifier bila kelak memakai editor WYSIWYG |
| Clickjacking | Header `X-Frame-Options: SAMEORIGIN` |
| MIME sniffing | `X-Content-Type-Options: nosniff` |
| Kebocoran referrer | `Referrer-Policy: strict-origin-when-cross-origin` |
| Injeksi skrip pihak ketiga | Content-Security-Policy: `default-src 'self'`; tanpa CDN — font & ikon di-*bundle* |
| Enumerasi | Pesan galat login netral ("Email atau password salah"), tidak membocorkan email mana yang terdaftar |
| Force browsing | Route model binding + Policy pada setiap sumber daya |

## 9.6 Keamanan Unggahan Berkas

- Hanya `jpg`, `jpeg`, `png`, `webp`; PDF khusus brosur.
- Maksimal 2 MB per berkas (brosur 5 MB).
- Validasi memakai `image` + `mimes` (memeriksa isi berkas, bukan ekstensi).
- Nama berkas di-generate ulang (`Str::uuid()`), nama asli dibuang.
- Disimpan di disk `public` dan **tidak pernah** dieksekusi (Nginx menonaktifkan PHP di `/storage`).
- Gambar diproses ulang (resize + konversi `webp`) sehingga muatan berbahaya yang disisipkan pada metadata ikut hilang.

## 9.7 Privasi Data

Data pribadi yang disimpan: nama, email, nomor WhatsApp, alamat (opsional), plat nomor, VIN,
riwayat servis.

| Aturan | Penerapan |
|--------|-----------|
| Minimalisasi | Halaman publik tidak pernah menampilkan nama lengkap, nomor telepon, atau plat pelanggan lain |
| Pelacakan publik | Hanya mengembalikan kode, jadwal, dan status — tanpa identitas |
| Akses staf | Advisor melihat data customer hanya lewat panel admin yang tercatat di activity log |
| Retensi | Booking & invoice disimpan permanen (kebutuhan garansi); activity log 12 bulan; log WA 12 bulan |
| Penghapusan akun | Permintaan hapus akun → akun dianonimkan (nama → "Pelanggan Terhapus", email/WA di-*hash*), data servis tetap ada untuk keperluan garansi kendaraan |

## 9.8 Logging & Pemantauan

- Log aplikasi: `daily`, disimpan 14 hari, level `warning` di produksi.
- Wajib tercatat: login gagal berulang, perubahan role, penghapusan booking, void invoice, gagal otorisasi (403).
- `APP_DEBUG=false` di produksi — halaman galat kustom 403/404/419/500 berbahasa Indonesia.
- Activity log menyimpan perubahan data penting beserta pelakunya ([07 §A12](07-modul-admin.md#a12--activity-log-adminactivity-log-sa-saja)).

## 9.9 Cadangan & Pemulihan

| Aspek | Ketentuan |
|-------|-----------|
| Basis data | `mysqldump` harian melalui cron, disimpan 14 hari, salinan luar-server mingguan |
| Berkas | `storage/app/public` disalin mingguan |
| Uji pemulihan | Restore diuji minimal sekali sebelum rilis dan setiap kuartal |
| Migrasi | Setiap migration wajib punya `down()` yang benar; migrasi merusak (drop kolom) memerlukan cadangan manual lebih dulu |

## 9.10 Daftar Periksa Sebelum Rilis

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `APP_KEY` dibuat ulang untuk produksi
- [ ] Akun Super Admin awal memakai password kuat dan bukan bawaan seeder
- [ ] Seeder demo (`DemoBookingSeeder`, `TestimonialSeeder`, customer contoh) tidak ikut dijalankan
- [ ] HTTPS aktif + `Secure` cookie + HSTS
- [ ] Header keamanan §9.5 terpasang di Nginx
- [ ] Rate limit login/registrasi/kontak/pelacakan terpasang dan diuji
- [ ] `/admin` diblokir di `robots.txt`
- [ ] Cadangan otomatis berjalan dan sudah diuji restore
- [ ] Uji otomatis untuk S1–S3 dan kepemilikan data lulus
- [ ] Nomor WhatsApp perusahaan di `.env` sudah benar dan diuji dari HP
