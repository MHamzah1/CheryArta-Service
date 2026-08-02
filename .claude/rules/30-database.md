# Aturan 30 — Database & Migration

Rujukan skema lengkap: `docs/04-skema-database.md`. Aturan di bawah wajib diikuti saat membuat
atau mengubah struktur.

## Migration

- Satu migration = satu perubahan yang koheren. Jangan menumpuk banyak tabel tak berkaitan.
- `down()` wajib benar-benar mengembalikan keadaan. Migration tanpa `down()` yang sah ditolak.
- Foreign key wajib eksplisit beserta perilaku hapusnya:

```php
$table->foreignId('user_id')->constrained()->cascadeOnDelete();      // ikut terhapus
$table->foreignId('vehicle_id')->constrained()->restrictOnDelete();  // lindungi data servis
$table->foreignId('car_model_id')->nullable()->constrained()->nullOnDelete();
```

- Setelah migration dibuat, jalankan `php artisan migrate` **dan** `php artisan migrate:rollback`
  sekali untuk membuktikan `down()` bekerja.
- Migration yang sudah jalan di produksi **tidak boleh disunting** — buat migration baru.

## Tipe Kolom

| Data | Tipe | Catatan |
|------|------|---------|
| Uang | `decimal(12,2)` | **Dilarang** float/double |
| Persentase/qty | `decimal(8,2)` | mendukung 0,5 jam jasa |
| Status/role | `string(20)` + PHP Enum | bukan tipe `ENUM` MySQL, agar nilai baru tidak butuh `ALTER TABLE` |
| Waktu kejadian | `timestamp nullable` | akhiran `_at` |
| Tanggal booking | `date` | terpisah dari `time` agar kuota per slot mudah dihitung |
| Jam booking | `time` | |
| Boolean | `boolean` + default | awalan `is_`/`has_` |
| Data fleksibel | `json` | hanya untuk spesifikasi katalog; **jangan** untuk data yang di-query |

## Index

Wajib ada index pada:

- Setiap foreign key (Laravel membuatnya otomatis lewat `constrained()`)
- Kolom yang dipakai memfilter daftar: `bookings(status, booking_date)`, `bookings(user_id, booking_date)`
- Kolom perhitungan kuota: `bookings(booking_date, booking_time)`
- Kolom unik: `booking_code`, `invoice_number`, `users.email`, `users.phone_wa`, `car_models.slug`
- Unik gabungan: `vehicles(user_id, plate_full)`

## Penghapusan Data

- `bookings`, `users`, `vehicles` memakai **soft delete**.
- Pembatalan booking memakai status `cancelled` + `cancel_reason` — **bukan** menghapus baris.
- Invoice tidak pernah dihapus; gunakan status `void`.
- Master data yang sudah dirujuk (model mobil, paket layanan) hanya boleh dinonaktifkan.

## Transaksi

Wajib `DB::transaction` untuk: pembuatan booking (dengan `lockForUpdate` pada perhitungan kuota),
penjadwalan ulang, penerbitan invoice, dan penerbitan nomor urut apa pun.

```php
// Pola wajib saat mengecek kuota — sistem lama gagal di titik ini
$count = Booking::whereDate('booking_date', $date)
    ->where('booking_time', $time)
    ->whereNotIn('status', BookingStatus::quotaReleasingValues())
    ->lockForUpdate()
    ->count();
```

## Seeder & Factory

- Seeder produksi (paket layanan, katalog, fasilitas, FAQ, template WA) **idempoten** — aman
  dijalankan ulang, gunakan `updateOrCreate`.
- Seeder demo dibungkus `if (app()->isLocal())`.
- Setiap model punya Factory agar uji tidak menulis data manual.
- Kode paket layanan lama (`first_maintenance_1000`, `Tiggo_8_Free`, `Other`, …) **harus**
  dipertahankan persis supaya data lama tetap bisa dipetakan.

## Yang Dilarang

- Menyimpan nomor telepon tanpa normalisasi (harus `62…`)
- Menyimpan plat sebagai satu string tanpa segmen (`plate_prefix/number/suffix` wajib ada)
- Kolom `password` di tabel selain `users`
- Menyimpan nilai turunan yang bisa dihitung (total baris invoice boleh disimpan karena harga bisa berubah; sisanya tidak)
- Menghapus baris audit (`booking_status_histories`, `activity_log`)
