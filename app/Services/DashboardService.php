<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Support\BookingPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;

/**
 * Agregat dashboard admin (A1 — docs/07-modul-admin.md, PRD F2.1).
 *
 * Seluruh angka dihitung DI DATABASE. Mengambil baris lalu menjumlahkannya di
 * PHP adalah bentuk lain dari temuan S8 sistem lama — dan pada tabel booking
 * yang terus tumbuh, ia berubah dari lambat menjadi mustahil.
 *
 * Service tidak menyentuh `request()`, `auth()`, atau `session()`: seluruh
 * masukannya adalah tanggal "hari ini" yang diberikan pemanggil. Itu yang
 * membuatnya bisa diuji tanpa menembak rute (.claude/rules/10).
 */
final readonly class DashboardService
{
    /**
     * Empat kartu KPI (PRD F2.1 §A).
     *
     * Empat kueri COUNT terpisah, bukan satu kueri berkondisi: masing-masing
     * jatuh persis ke index `bookings(status, booking_date)`, dan yang dibaca
     * manusia enam bulan lagi adalah bentuk yang ini.
     *
     * @return array<string, int>
     */
    public function kpi(CarbonImmutable $today): array
    {
        $hariIni = $today->toDateString();

        return [
            // Booking hari ini — yang batal bukan pekerjaan hari ini.
            'booking_hari_ini' => Booking::query()
                ->where('booking_date', $hariIni)
                ->where('status', '!=', BookingStatus::Cancelled->value)
                ->count(),

            // Perlu konfirmasi — SELURUH pending yang tanggalnya belum lewat
            // (keputusan grill #3). Angka yang menyembunyikan booking minggu
            // depan membuat orang merasa sudah beres padahal belum.
            'perlu_konfirmasi' => Booking::query()
                ->where('status', BookingStatus::Pending->value)
                ->where('booking_date', '>=', $hariIni)
                ->count(),

            // Sedang dikerjakan — tanpa batas tanggal. Unit yang menginap di
            // bengkel tetap harus terhitung.
            'sedang_dikerjakan' => Booking::query()
                ->where('status', BookingStatus::InProgress->value)
                ->count(),

            // Selesai bulan ini — bulan kalender berjalan, menurut
            // booking_date (konsisten dengan keputusan grill #2).
            'selesai_bulan_ini' => Booking::query()
                ->where('status', BookingStatus::Completed->value)
                ->whereBetween('booking_date', [
                    $today->startOfMonth()->toDateString(),
                    $today->endOfMonth()->toDateString(),
                ])
                ->count(),
        ];
    }

    /**
     * Kartu "Belum dikabari" (docs/07 §A1, roadmap 2.3.5, keputusan grill Q6).
     *
     * Menghitung BOOKING, bukan baris draft. Baris `whatsapp_messages` hanya
     * lahir ketika advisor menekan tombolnya (keputusan grill Q1), sehingga
     * menghitung baris berstatus `generated` justru melewatkan kelalaian yang
     * ditakutkan: advisor yang tidak membuka WhatsApp sama sekali.
     *
     * Definisinya sendiri hidup di `Booking::scopeAwaitingWhatsApp()` — dipakai
     * bersama saringan `wa=belum` di daftar booking, supaya angka di kartu dan
     * isi daftarnya tidak pernah berselisih.
     */
    public function awaitingWhatsApp(): int
    {
        return Booking::query()->awaitingWhatsApp()->count();
    }

    /**
     * Deret grafik tren (PRD F2.1 §B).
     *
     * Dikelompokkan menurut `booking_date` — beban bengkel, bukan tren
     * permintaan masuk (keputusan grill #2).
     *
     * Deret tanggalnya dibangun LENGKAP di sini, lalu diisi dari hasil kueri.
     * Menyerahkannya ke pustaka grafik berarti hari tanpa booking hilang dari
     * sumbu X, dan garisnya berbohong: penurunan terlihat seperti kekosongan.
     *
     * @return list<array{date: string, count: int}>
     */
    public function trend(CarbonImmutable $today): array
    {
        $hari = Config::integer('booking.reports.trend_days');
        $mulai = $today->subDays($hari - 1);

        $terhitung = Booking::query()
            ->whereBetween('booking_date', [$mulai->toDateString(), $today->toDateString()])
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->selectRaw('booking_date, COUNT(*) as jumlah')
            ->groupBy('booking_date')
            ->pluck('jumlah', 'booking_date');

        $deret = [];

        for ($i = 0; $i < $hari; $i++) {
            $tanggal = $mulai->addDays($i)->toDateString();

            $deret[] = [
                'date' => $tanggal,
                'count' => (int) ($terhitung[$tanggal] ?? 0),
            ];
        }

        return $deret;
    }

    /**
     * Tabel booking hari ini (PRD F2.1 §D).
     *
     * Urutannya sama persis dengan halaman Jadwal (A3): jam, lalu id sebagai
     * pemutus seri. Dua layar yang menampilkan hari yang sama dengan urutan
     * berbeda membuat orang mengira salah satunya keliru.
     *
     * @return list<array<string, mixed>>
     */
    public function todayBookings(CarbonImmutable $today): array
    {
        return Booking::query()
            ->where('booking_date', $today->toDateString())
            ->where('status', '!=', BookingStatus::Cancelled->value)
            // Tanpa eager load, satu tabel 10 baris menembakkan 40 kueri —
            // cara N+1 masuk lewat pintu belakang (.claude/rules/10).
            ->with(['user:id,name', 'vehicle.carModel:id,name', 'servicePackage:id,name', 'handledBy:id,name'])
            ->orderBy('booking_time')
            ->orderBy('id')
            ->get()
            ->map(BookingPresenter::dashboardRow(...))
            ->all();
    }

    /**
     * Peringatan calon `no_show` (PRD F2.1 §E).
     *
     * `confirmed` yang tanggalnya SUDAH LEWAT — ambangnya tanggal, bukan jam
     * (keputusan grill #4). Ambang jam akan membanjiri peringatan sepanjang
     * hari kerja untuk booking yang advisornya memang belum sempat menyentuh.
     *
     * Inilah yang sebelumnya tidak terlihat di mana pun: booking seperti ini
     * tidak muncul di jadwal hari ini dan tenggelam di daftar booking.
     *
     * @return list<array<string, mixed>>
     */
    public function overdueConfirmed(CarbonImmutable $today): array
    {
        return Booking::query()
            ->where('status', BookingStatus::Confirmed->value)
            ->where('booking_date', '<', $today->toDateString())
            ->with(['user:id,name', 'vehicle.carModel:id,name', 'servicePackage:id,name', 'handledBy:id,name'])
            // Terlama lebih dulu: yang paling lama tertinggal paling mendesak.
            ->orderBy('booking_date')
            ->orderBy('booking_time')
            ->orderBy('id')
            ->get()
            ->map(BookingPresenter::dashboardRow(...))
            ->all();
    }
}
