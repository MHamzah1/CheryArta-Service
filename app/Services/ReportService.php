<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Support\ReportPeriod;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

/**
 * Agregat halaman laporan (A9 — docs/07-modul-admin.md, PRD F2.1).
 *
 * Laporan **Pendapatan** lahir di F2.4 bersama tabel `invoices` — lihat
 * `revenue()` di bawah. Ia satu-satunya laporan yang dibatasi Super Admin
 * (docs/09 §9.3), dan pembatasannya ditegakkan ReportController lewat
 * `viewRevenueReport`, bukan di sini.
 *
 * Seluruh kueri di berkas ini harus jalan di MySQL **dan** SQLite — uji Pest
 * memakai SQLite. Karena itu tidak ada `DATE_FORMAT` atau fungsi khas MySQL;
 * `DATE()` dipakai karena keduanya mengenalnya.
 */
final readonly class ReportService
{
    /**
     * Kolom export, dipertahankan persis seperti sistem lama supaya berkas
     * hasil unduhan tetap bisa ditempel ke spreadsheet yang sudah dipakai
     * bengkel — ditambah Kode Booking dan Advisor yang dulu tidak ada.
     *
     * @var list<string>
     */
    public const EXPORT_HEADERS = [
        'Date', 'Time', 'Name', 'Model', 'Plat Nomor', 'Service',
        'Keluhan', 'Phone', 'Status', 'Kode Booking', 'Advisor',
    ];

    /**
     * Rekap booking: total, pecahan per status, per paket layanan, per model.
     *
     * @return array<string, mixed>
     */
    public function bookingRecap(ReportPeriod $period): array
    {
        $dasar = fn () => Booking::query()
            ->whereBetween('booking_date', [$period->startDate(), $period->endDate()]);

        $perStatus = $dasar()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        // Seluruh status ditampilkan termasuk yang nol. Status yang hilang dari
        // daftar terbaca sebagai "tidak ada datanya", bukan "tidak pernah
        // terjadi" — dua hal yang sangat berbeda bagi orang yang membaca rekap.
        $status = array_map(
            fn (BookingStatus $s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'tone' => $s->tone(),
                'jumlah' => (int) ($perStatus[$s->value] ?? 0),
            ],
            BookingStatus::cases(),
        );

        return [
            'total' => (int) $perStatus->sum(),
            // Total yang benar-benar dikerjakan — dasar pembanding yang lebih
            // jujur daripada total mentah yang ikut menghitung pembatalan.
            'total_selesai' => (int) ($perStatus[BookingStatus::Completed->value] ?? 0),
            'per_status' => $status,
            'per_paket' => $this->perRelasi($period, 'service_package_id', 'service_packages'),
            'per_model' => $this->perModel($period),
        ];
    }

    /**
     * Okupansi: rata-rata pemakaian slot per hari dan per jam.
     *
     * Yang dihitung hanya booking yang BENAR-BENAR menempati kuota —
     * `cancelled` dan `no_show` melepaskannya kembali. Sistem lama menghitung
     * semuanya, sehingga slot bisa terlihat "penuh palsu" (temuan pada
     * BookingStatus::releasesQuota()).
     *
     * @return array<string, mixed>
     */
    public function occupancy(ReportPeriod $period): array
    {
        $kuota = Config::integer('booking.quota_per_slot');

        // `toBase()` sesudah scope Eloquent: hasilnya baris agregat, bukan model
        // Booking. Membiarkannya bertipe model membuat `$baris->jumlah` terlihat
        // seperti kolom tabel yang tidak ada.
        $perJam = Booking::query()
            ->occupyingQuota()
            ->whereBetween('booking_date', [$period->startDate(), $period->endDate()])
            ->selectRaw('booking_time, COUNT(*) as jumlah')
            ->groupBy('booking_time')
            ->orderBy('booking_time')
            ->toBase()
            ->get();

        // Jumlah hari bengkel BUKA dalam rentang — pembagi yang benar. Memakai
        // jumlah hari kalender akan menekan rata-rata setiap kali rentangnya
        // memuat hari Minggu, dan menyalahkan bengkel atas hari tutupnya.
        $hariBuka = Booking::query()
            ->whereBetween('booking_date', [$period->startDate(), $period->endDate()])
            ->distinct()
            ->count('booking_date');

        $pembagi = max($hariBuka, 1);

        $jam = $perJam->map(function (object $baris) use ($pembagi, $kuota): array {
            $jumlah = (int) $baris->jumlah;

            return [
                'time' => substr((string) $baris->booking_time, 0, 5),
                'total' => $jumlah,
                'rata_rata' => round($jumlah / $pembagi, 2),
                // Berapa persen kuota jam itu terpakai sepanjang periode.
                'persen' => $kuota > 0 ? (int) round(($jumlah / ($pembagi * $kuota)) * 100) : 0,
            ];
        })->all();

        $totalTerisi = array_sum(array_column($jam, 'total'));

        return [
            'quota_per_slot' => $kuota,
            'hari_beroperasi' => $hariBuka,
            'total_terisi' => $totalTerisi,
            'rata_rata_per_hari' => round($totalTerisi / $pembagi, 2),
            'per_jam' => $jam,
            // Jam tersibuk — pertanyaan yang paling sering ditanyakan pemilik
            // bengkel, dan yang menentukan apakah kuota 2/slot masih cukup.
            'jam_tersibuk' => $this->jamTersibuk($jam),
        ];
    }

    /**
     * Customer baru: jumlah registrasi dalam periode.
     *
     * Dihitung dari `created_at`, bukan `booking_date` — di sinilah "kapan
     * dibuat" memang pertanyaannya, berbeda dengan grafik tren dashboard
     * (keputusan grill #2).
     *
     * @return array<string, mixed>
     */
    public function newCustomers(ReportPeriod $period): array
    {
        $mulai = $period->start->startOfDay();
        $selesai = $period->end->endOfDay();

        $perHari = User::query()
            ->where('role', UserRole::Customer->value)
            ->whereBetween('created_at', [$mulai, $selesai])
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as jumlah')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->pluck('jumlah', 'tanggal');

        $deret = [];

        for ($tanggal = $period->start; $tanggal->lessThanOrEqualTo($period->end); $tanggal = $tanggal->addDay()) {
            $kunci = $tanggal->toDateString();

            $deret[] = ['date' => $kunci, 'count' => (int) ($perHari[$kunci] ?? 0)];
        }

        return [
            'total' => (int) $perHari->sum(),
            'rata_rata_per_hari' => round($perHari->sum() / max($period->dayCount(), 1), 2),
            'per_hari' => $deret,
        ];
    }

    /**
     * Jam dengan pemakaian terbanyak. Seri diputus oleh jam yang lebih pagi,
     * karena itu yang lebih dulu ditawarkan ke pelanggan.
     *
     * @param  list<array{time: string, total: int, rata_rata: float, persen: int}>  $jam
     */
    private function jamTersibuk(array $jam): ?string
    {
        $tersibuk = null;

        foreach ($jam as $baris) {
            if ($tersibuk === null || $baris['total'] > $tersibuk['total']) {
                $tersibuk = $baris;
            }
        }

        return $tersibuk['time'] ?? null;
    }

    /**
     * Pecahan menurut satu relasi bernama, urut terbanyak lebih dulu.
     *
     * `join` dipakai alih-alih `with`, karena yang dibutuhkan hanya namanya
     * untuk dikelompokkan — memuat seluruh model paket hanya untuk mengambil
     * satu kolom adalah pemborosan yang tidak terlihat sampai datanya banyak.
     *
     * @return list<array{label: string, jumlah: int}>
     */
    /**
     * Laporan Pendapatan (docs/07 §A9, keputusan grill Q9) — Super Admin saja.
     *
     * **Dikelompokkan menurut `issued_at`**, bukan `paid_at` dan bukan tanggal
     * booking. Alasannya: memakai `paid_at` membuat invoice yang belum dibayar
     * tidak muncul di mana pun, sehingga piutang menjadi tak terlihat — justru
     * angka yang paling ingin diketahui pemilik bengkel.
     *
     * Tiga angka, bukan satu. "Rp 12 juta" yang ternyata separuhnya belum masuk
     * kas adalah laporan yang salah dibaca. `draft` dan `void` dikecualikan:
     * yang satu belum pernah menjadi tagihan, yang lain tagihannya dicabut.
     *
     * @return array<string, mixed>
     */
    public function revenue(ReportPeriod $period): array
    {
        $diterbitkan = $this->rekapInvoice($period, null);
        $lunas = $this->rekapInvoice($period, InvoiceStatus::Paid);

        return [
            'issued_count' => $diterbitkan['count'],
            'issued_total' => $diterbitkan['total'],
            'paid_count' => $lunas['count'],
            'paid_total' => $lunas['total'],
            'unpaid_count' => $diterbitkan['count'] - $lunas['count'],
            // Dihitung sebagai SELISIH, bukan dijumlah ulang dari baris
            // berstatus `issued`. Dengan begitu ketiga angka mustahil berselisih
            // satu sama lain — "diterbitkan − lunas = belum dibayar" selalu benar
            // menurut konstruksinya, bukan menurut harapan.
            'unpaid_total' => number_format(
                round((float) $diterbitkan['total'] - (float) $lunas['total'], 2),
                2,
                '.',
                '',
            ),
            'by_package' => $this->pendapatanPer($period, 'service_packages.name'),
            'by_payment_method' => $this->pendapatanPerMetode($period),
        ];
    }

    /**
     * @return array{count: int, total: string}
     */
    private function rekapInvoice(ReportPeriod $period, ?InvoiceStatus $status): array
    {
        $baris = $this->invoiceDasar($period)
            ->when($status, fn (Builder $q, InvoiceStatus $s) => $q->where('invoices.status', $s->value))
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(invoices.total), 0) as nilai')
            ->toBase()
            ->first();

        return [
            'count' => (int) ($baris->jumlah ?? 0),
            'total' => number_format((float) ($baris->nilai ?? 0), 2, '.', ''),
        ];
    }

    /**
     * Pecahan pendapatan per paket layanan.
     *
     * @return list<array{label: string, count: int, total: string}>
     */
    private function pendapatanPer(ReportPeriod $period, string $kolomLabel): array
    {
        return $this->invoiceDasar($period)
            ->join('bookings', 'invoices.booking_id', '=', 'bookings.id')
            ->join('service_packages', 'bookings.service_package_id', '=', 'service_packages.id')
            ->selectRaw("{$kolomLabel} as label, COUNT(*) as jumlah, SUM(invoices.total) as nilai")
            ->groupBy($kolomLabel)
            ->orderByDesc('nilai')
            ->toBase()
            ->get()
            ->map(fn (object $baris): array => [
                'label' => (string) $baris->label,
                'count' => (int) $baris->jumlah,
                'total' => number_format((float) $baris->nilai, 2, '.', ''),
            ])
            ->all();
    }

    /**
     * Pecahan per metode pembayaran — hanya invoice yang sudah lunas yang
     * punya metode, jadi baris ini menjelaskan uang yang benar-benar masuk.
     *
     * @return list<array{label: string, count: int, total: string}>
     */
    private function pendapatanPerMetode(ReportPeriod $period): array
    {
        $perMetode = $this->invoiceDasar($period)
            ->where('invoices.status', InvoiceStatus::Paid->value)
            ->whereNotNull('invoices.payment_method')
            ->selectRaw('invoices.payment_method, COUNT(*) as jumlah, SUM(invoices.total) as nilai')
            ->groupBy('invoices.payment_method')
            ->toBase()
            ->get()
            ->keyBy('payment_method');

        // Seluruh metode ditampilkan termasuk yang nol, dengan alasan yang sama
        // seperti status pada rekap booking: metode yang hilang dari daftar
        // terbaca sebagai "tidak ada datanya", bukan "tidak pernah dipakai".
        return array_map(
            fn (PaymentMethod $metode): array => [
                'label' => $metode->label(),
                'count' => (int) ($perMetode[$metode->value]->jumlah ?? 0),
                'total' => number_format((float) ($perMetode[$metode->value]->nilai ?? 0), 2, '.', ''),
            ],
            PaymentMethod::cases(),
        );
    }

    /**
     * Dasar seluruh kueri pendapatan: rentang atas `issued_at`, hanya status
     * yang benar-benar menjadi tagihan.
     *
     * @return Builder<Invoice>
     */
    private function invoiceDasar(ReportPeriod $period): Builder
    {
        // Seluruh kolom dikualifikasi: kueri ini di-`join` ke `bookings`, yang
        // punya kolom `status` sendiri (dan `created_at`/`updated_at` juga).
        return Invoice::query()
            ->countsAsRevenue()
            ->whereNotNull('invoices.issued_at')
            ->whereBetween('invoices.issued_at', [
                $period->startDate().' 00:00:00',
                $period->endDate().' 23:59:59',
            ]);
    }

    /** @return list<array{label: string, jumlah: int}> */
    private function perRelasi(ReportPeriod $period, string $foreignKey, string $table): array
    {
        return Booking::query()
            ->whereBetween('booking_date', [$period->startDate(), $period->endDate()])
            ->join($table, "bookings.{$foreignKey}", '=', "{$table}.id")
            ->selectRaw("{$table}.name as label, COUNT(*) as jumlah")
            ->groupBy("{$table}.name")
            ->orderByDesc('jumlah')
            ->toBase()
            ->get()
            ->map(fn (object $baris): array => [
                'label' => (string) $baris->label,
                'jumlah' => (int) $baris->jumlah,
            ])
            ->all();
    }

    /**
     * Pecahan menurut model mobil, lewat kendaraan.
     *
     * Kendaraan non-Chery tidak punya `car_model_id` — ia memakai
     * `model_name_manual`. `COALESCE` menyatukan keduanya supaya unit itu tetap
     * terhitung alih-alih hilang diam-diam dari rekap.
     *
     * @return list<array{label: string, jumlah: int}>
     */
    private function perModel(ReportPeriod $period): array
    {
        return Booking::query()
            ->whereBetween('booking_date', [$period->startDate(), $period->endDate()])
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('car_models', 'vehicles.car_model_id', '=', 'car_models.id')
            ->selectRaw('COALESCE(car_models.name, vehicles.model_name_manual, ?) as label, COUNT(*) as jumlah', ['Model tidak diketahui'])
            ->groupBy('label')
            ->orderByDesc('jumlah')
            ->toBase()
            ->get()
            ->map(fn (object $baris): array => [
                'label' => (string) $baris->label,
                'jumlah' => (int) $baris->jumlah,
            ])
            ->all();
    }

    /**
     * Baris mentah untuk export (docs/07 §A9), termasuk baris judulnya.
     *
     * Menerima kueri yang SUDAH tersaring dari pemanggil — itulah yang membuat
     * "export mengikuti filter aktif" berlaku di dua layar sekaligus tanpa
     * saringannya ditulis dua kali.
     *
     * Memakai `cursor()`: export bisa menyentuh ribuan baris, dan memuat
     * semuanya ke memori sekaligus adalah cara paling mudah membuat proses PHP
     * mati di tengah unduhan.
     *
     * @param  Builder<Booking>  $query
     * @return Generator<int, list<string>>
     */
    public function exportRows(Builder $query): Generator
    {
        yield self::EXPORT_HEADERS;

        foreach ($query->with(['user:id,name,phone_wa', 'vehicle.carModel:id,name', 'servicePackage:id,name', 'handledBy:id,name'])->cursor() as $booking) {
            yield [
                $booking->booking_date->toDateString(),
                substr((string) $booking->booking_time, 0, 5),
                $booking->user->name,
                $booking->vehicle->display_model,
                $booking->vehicle->plate_full,
                $booking->servicePackage->name,
                (string) ($booking->complaint ?? ''),
                (string) ($booking->user->phone_wa ?? ''),
                $booking->status->label(),
                $booking->booking_code,
                // Booking yang belum dipegang siapa pun menghasilkan sel kosong,
                // bukan baris yang hilang dari export.
                (string) $booking->handledBy?->name,
            ];
        }
    }
}
