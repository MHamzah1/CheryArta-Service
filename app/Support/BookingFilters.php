<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Filter daftar booking admin (docs/07 §A2), dijalankan DI SERVER.
 *
 * Sistem lama mengirim seluruh isi tabel ke browser lalu menyaringnya di sana
 * (temuan S8) — data seluruh pelanggan terbaca siapa pun yang membuka
 * DevTools. Di sini penyaringan terjadi di kueri, dan yang sampai ke React
 * hanya 25 baris halaman berjalan.
 *
 * Bentuknya objek nilai supaya nilai yang sama dipakai untuk tiga hal
 * sekaligus tanpa ditulis ulang: menyusun kueri, mengisi kembali form filter
 * di React, dan menempel di query string agar tautannya bisa dibagikan.
 */
final readonly class BookingFilters
{
    /** Urutan bawaan: terbaru dulu, karena yang dicari advisor biasanya baru saja terjadi. */
    public const URUTAN_TERBARU = 'terbaru';

    /** Jadwal terdekat lebih dulu — dipakai bersama saringan "Mendatang". */
    public const URUTAN_TERDEKAT = 'terdekat';

    /** Nilai saringan WhatsApp: hanya booking yang pelanggannya belum dikabari. */
    public const WA_BELUM = 'belum';

    public function __construct(
        public ?string $cari = null,
        public ?string $status = null,
        public ?string $dari = null,
        public ?string $sampai = null,
        public ?int $paket = null,
        public ?int $advisor = null,
        public string $urutan = self::URUTAN_TERBARU,
        public ?string $wa = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            cari: self::teks($validated['cari'] ?? null),
            status: self::teks($validated['status'] ?? null),
            dari: self::teks($validated['dari'] ?? null),
            sampai: self::teks($validated['sampai'] ?? null),
            paket: isset($validated['paket']) ? (int) $validated['paket'] : null,
            advisor: isset($validated['advisor']) ? (int) $validated['advisor'] : null,
            urutan: ($validated['urutan'] ?? null) === self::URUTAN_TERDEKAT
                ? self::URUTAN_TERDEKAT
                : self::URUTAN_TERBARU,
            wa: ($validated['wa'] ?? null) === self::WA_BELUM ? self::WA_BELUM : null,
        );
    }

    /** @param  Builder<Booking>  $query */
    public function apply(Builder $query): void
    {
        $query
            ->when($this->cari !== null, fn (Builder $q) => $this->cari($q))
            ->when($this->status !== null, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->dari !== null, fn (Builder $q) => $q->where('booking_date', '>=', $this->dari))
            ->when($this->sampai !== null, fn (Builder $q) => $q->where('booking_date', '<=', $this->sampai))
            ->when($this->paket !== null, fn (Builder $q) => $q->where('service_package_id', $this->paket))
            ->when($this->advisor !== null, fn (Builder $q) => $q->where('handled_by', $this->advisor))
            // Definisinya hidup di Booking::scopeAwaitingWhatsApp — satu tempat
            // yang sama dengan kartu dashboard, supaya angka di kartu dan isi
            // daftar ini tidak pernah berselisih.
            ->when($this->wa === self::WA_BELUM, fn (Builder $q) => $q->awaitingWhatsApp());

        $arah = $this->urutan === self::URUTAN_TERDEKAT ? 'asc' : 'desc';

        $query->orderBy('booking_date', $arah)
            ->orderBy('booking_time', $arah)
            // Pemutus seri: tanpa ini urutan dua booking pada slot yang sama
            // bisa berbeda antar halaman, sehingga satu baris muncul dua kali
            // dan baris lain tidak pernah terlihat.
            ->orderBy('id', $arah);
    }

    /**
     * Satu kolom pencarian untuk tiga hal yang diketik advisor: kode booking,
     * nama pelanggan, atau plat nomor — mempertahankan perilaku sistem lama
     * yang memang satu kotak pencarian.
     *
     * @param  Builder<Booking>  $query
     */
    private function cari(Builder $query): void
    {
        $kata = $this->cari ?? '';

        // Plat diketik orang dengan spasi ("B 1234 ABC") tetapi tersimpan
        // dengan tanda hubung — tanpa penyeragaman ini pencarian plat yang
        // paling wajar justru tidak pernah menemukan apa pun.
        $plat = Str::upper(preg_replace('/[\s-]+/', '-', trim($kata)) ?? $kata);

        $query->where(function (Builder $inner) use ($kata, $plat): void {
            $inner
                ->where('booking_code', 'like', '%'.$kata.'%')
                ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', '%'.$kata.'%'))
                ->orWhereHas('vehicle', fn (Builder $v) => $v->where('plate_full', 'like', '%'.$plat.'%'));
        });
    }

    /**
     * Bentuk yang dikirim balik ke React untuk mengisi ulang form filter.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cari' => $this->cari,
            'status' => $this->status,
            'dari' => $this->dari,
            'sampai' => $this->sampai,
            'paket' => $this->paket,
            'advisor' => $this->advisor,
            'urutan' => $this->urutan,
            'wa' => $this->wa,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->cari === null
            && $this->status === null
            && $this->dari === null
            && $this->sampai === null
            && $this->paket === null
            && $this->advisor === null
            && $this->wa === null;
    }

    /** String kosong dari form yang dikosongkan berarti "tanpa filter", bukan "cari string kosong". */
    private static function teks(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
