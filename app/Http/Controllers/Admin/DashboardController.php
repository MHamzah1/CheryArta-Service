<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\DashboardService;
use App\Services\SlotService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A1 — Dashboard admin (docs/07 §A1, roadmap 2.1.1–2.1.3).
 *
 * Layar yang menjawab "apa yang harus dikerjakan hari ini" dalam satu tatapan.
 * Sebelum ini `/admin` mengalihkan ke daftar booking — daftar terpaginasi yang
 * urut menurut saringan, bukan menurut urgensi (keputusan R8 dicabut di sini).
 *
 * Seluruh agregatnya milik DashboardService; controller hanya menyusun props.
 * `Inertia::optional` TIDAK dipakai: keempat bagian halaman ini tampil
 * bersamaan di layar pertama, jadi menundanya hanya menambah satu perjalanan
 * bolak-balik tanpa ada yang diuntungkan.
 */
class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard, SlotService $slots): Response
    {
        Gate::authorize('viewReports', Booking::class);

        $today = $slots->today();

        return Inertia::render('admin/dashboard', [
            'today' => $today->toDateString(),
            'kpi' => $dashboard->kpi($today),
            'trend' => $dashboard->trend($today),
            'trendDays' => Config::integer('booking.reports.trend_days'),
            'todayBookings' => $dashboard->todayBookings($today),
            'overdue' => $dashboard->overdueConfirmed($today),
            'occupancy' => $this->okupansi($today, $slots),
            // A7 (roadmap 2.3.5). Ditunda sampai Tahap 10 karena tabelnya baru
            // lahir di sini — dashboard tidak boleh menampilkan "0 draft" untuk
            // tabel yang belum ada.
            'awaitingWhatsApp' => $dashboard->awaitingWhatsApp(),
        ]);
    }

    /**
     * Okupansi hari ini — versi ringkas BACA-SAJA (keputusan grill #6).
     *
     * Angkanya datang dari `SlotService::availability()` yang sama dengan
     * halaman Jadwal, bukan hitungan kedua. Dua tempat yang menghitung kuota
     * dengan caranya sendiri adalah cara paling pasti membuat keduanya berbeda.
     *
     * Daftar booking per slot sengaja TIDAK ikut: itu pekerjaan halaman A3,
     * dan panel ini hanya menautkannya.
     *
     * @return array<string, mixed>
     */
    private function okupansi(\Carbon\CarbonImmutable $today, SlotService $slots): array
    {
        $tutup = $slots->isClosedOn($today);

        return [
            'quota_per_slot' => Config::integer('booking.quota_per_slot'),
            'closed_reason' => $tutup ? $slots->dateRejectionReason($today) : null,
            // Hari tutup tidak punya slot; mengirim daftar kosong lebih jujur
            // daripada mengirim sebelas baris "0/2" yang seolah bisa diisi.
            'slots' => $tutup ? [] : $slots->availability($today),
        ];
    }
}
