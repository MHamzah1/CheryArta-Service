# Aturan 10 — Backend (Laravel)

## Controller

- **Tipis.** Tugasnya: otorisasi → validasi (Form Request) → panggil Service → `Inertia::render` / `redirect`.
- Tidak ada logika bisnis, tidak ada query kompleks, tidak ada `if` berlapis di controller.
- Maksimal ±40 baris per method. Lebih dari itu, pindahkan ke Service.
- Kelompokkan per audiens: `Http/Controllers/{Public,Customer,Admin,Auth}`.

```php
public function store(StoreBookingRequest $request, BookingService $bookings): RedirectResponse
{
    $booking = $bookings->create($request->user(), $request->validated());

    return redirect()
        ->route('customer.booking.show', $booking)
        ->with('success', "Booking {$booking->booking_code} berhasil dibuat.");
}
```

## Validasi

- Selalu lewat **Form Request**. Dilarang `$request->validate()` di controller.
- Dilarang membaca `$request->all()`, `$request->input('apa_saja')` untuk hal yang tidak divalidasi.
- Pesan galat berbahasa Indonesia dan spesifik. Bukan "Data tidak valid", melainkan
  "Jam 09:00 pada 3 Agustus 2026 sudah penuh."
- Aturan yang dipakai berulang dibungkus Rule object: `ValidPlateSegment`, `AvailableSlot`,
  `NotSunday`, `IndonesianPhone`.

## Service Layer

Empat service inti (`app/Services/`): `SlotService`, `BookingService`, `InvoiceService`,
`WhatsAppNotifier`.

- Semua aturan bisnis tinggal di sini, bukan di model, controller, atau React.
- Service tidak boleh menyentuh `request()`, `session()`, atau `auth()` — terima apa yang
  dibutuhkan lewat parameter agar bisa diuji.
- Operasi yang menyentuh beberapa tabel dibungkus `DB::transaction`.

## Model

- `$fillable` eksplisit. **`$guarded = []` dilarang.**
- `casts()` untuk enum, tanggal, boolean, json, dan `decimal:2`.
- Scope untuk filter yang berulang: `scopeActive`, `scopeUpcoming`, `scopeOnDate`.
- Accessor untuk nilai turunan (`plate_full`, `estimated_finish_at`).
- Relasi diberi tipe balik yang jelas (`BelongsTo`, `HasMany`).
- Model **tidak** mengirim notifikasi dan tidak memanggil Service.

## Enum

Aturan status hidup di enum, bukan tersebar dalam `if`:

```php
enum BookingStatus: string
{
    case Pending = 'pending';
    // …

    public function label(): string { … }          // "Menunggu Konfirmasi"
    public function canTransitionTo(self $to): bool { … }
    public function isReschedulable(): bool { return in_array($this, [self::Pending, self::Confirmed]); }
    public function releasesQuota(): bool { return in_array($this, [self::Cancelled, self::NoShow]); }
}
```

## Query

- Dilarang N+1: gunakan `with()`; aktifkan `Model::preventLazyLoading()` di lingkungan lokal.
- Daftar panjang selalu dipaginasi (`paginate(25)`), tidak pernah `all()`.
- Filter dijalankan di database, bukan `->get()` lalu `->filter()` di PHP.
- `DB::raw` hanya dengan binding; dilarang menyambung string ke dalam query.

## Otorisasi

- Setiap sumber daya punya Policy. Controller memanggil `$this->authorize(...)` atau
  `Gate::authorize(...)` — tanpa kecuali.
- Data milik pengguna diambil lewat relasi: `$request->user()->bookings()->findOrFail($id)`,
  bukan `Booking::findOrFail($id)` lalu diperiksa kemudian.
- Middleware `role:` melindungi grup rute, tetapi **tidak menggantikan** Policy.

## Waktu & Uang

- Semua tanggal memakai `Carbon` dengan zona `Asia/Jakarta` (`config('booking.timezone')`).
- Dilarang `date()`, `strtotime()`, atau `new DateTime()` telanjang.
- Uang memakai `decimal:2`; dilarang float. Perhitungan total selalu di server.

## Konfigurasi

- Angka aturan bisnis (kuota, lead time, daftar slot, hari tutup) **hanya** di `config/booking.php`.
- `env()` hanya boleh dipanggil di dalam berkas `config/*`. Di tempat lain gunakan `config()`,
  karena `config:cache` membuat `env()` mengembalikan null.

## Penamaan Rute

`admin.bookings.index`, `customer.booking.create`, `public.catalog.show`. URL berbahasa Indonesia
(`/jadwal`, `/kendaraan`, `/riwayat`) agar konsisten dengan antarmuka.
