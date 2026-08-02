# Chery Arta — Website Service & Booking

Laravel 12 · Inertia.js 2 · React 19 + TypeScript · Tailwind 4 · MySQL 8

Menggantikan prototipe lama `index (1).html` (satu berkas HTML + Firebase). Firebase dihapus total.

## Aturan wajib

Baca dan ikuti berkas berikut sebelum menulis kode:

@.claude/rules/00-konteks-proyek.md
@.claude/rules/10-backend-laravel.md
@.claude/rules/20-frontend-react-inertia.md
@.claude/rules/30-database.md
@.claude/rules/40-ui-design-system.md
@.claude/rules/50-keamanan.md
@.claude/rules/60-testing-dan-git.md

## Rancangan

Seluruh keputusan produk, skema, dan alur bisnis ada di `docs/` — mulai dari
[docs/README.md](docs/README.md). Bila kode dan dokumen bertentangan, **dokumen yang benar**;
perbarui kodenya, atau perbarui dokumennya bila keputusannya memang berubah.

## Perintah proyek

| Perintah | Guna |
|----------|------|
| `/buat-modul <Model> <audiens>` | Modul CRUD lengkap sesuai rancangan |
| `/buat-halaman <rute>` | Halaman Inertia baru + controller + tipe |
| `/cek-kualitas` | Seluruh gerbang kualitas, lalu perbaiki |
| `/db-segar` | Bangun ulang database + seeder |
| `/lanjut-fase [n]` | Periksa progres roadmap dan lanjutkan |
| `/audit-paritas` | Bandingkan dengan prototipe lama |

## Perintah pengembangan

```bash
php artisan serve          # server pengembangan
npm run dev                # Vite
php artisan test           # Pest
php artisan migrate:fresh --seed
```

## Tiga hal yang paling sering keliru di proyek ini

1. **Angka aturan booking** (kuota 2, H-1, daftar slot, hari tutup) hanya boleh hidup di
   `config/booking.php`. Sistem lama menuliskannya di tiga tempat dan akhirnya saling bertabrakan.
2. **Otorisasi dihitung di server**, dikirim ke React sebagai props boolean. Menyembunyikan tombol
   bukan pengaman.
3. **Bahasa antarmuka Indonesia** dengan istilah yang konsisten: Booking, Servis, Kendaraan,
   Paket Layanan.
