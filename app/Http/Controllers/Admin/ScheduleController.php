<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleRequest;
use App\Models\Booking;
use App\Services\SlotService;
use App\Support\BookingPresenter;
use App\Support\SlotTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A3 — Jadwal / Okupansi harian (docs/07 §A3, roadmap 1.5.5).
 *
 * Layar yang dibuka advisor saat menerima telepon: satu tatapan menjawab
 * "jam berapa yang masih kosong hari Kamis?". Sistem lama tidak punya
 * jawabannya sama sekali — slot penuh baru ketahuan setelah pelanggan
 * menekan submit.
 */
class ScheduleController extends Controller
{
    public function __invoke(ScheduleRequest $request, SlotService $slots): Response
    {
        Gate::authorize('viewAny', Booking::class);

        $date = $request->selectedDate($slots);

        return Inertia::render('admin/jadwal/index', [
            'date' => $date->toDateString(),
            // Navigasi ← hari → disiapkan server: React tidak boleh menghitung
            // sendiri "besok" dari zona waktu peramban (temuan B7).
            'previousDate' => $date->subDay()->toDateString(),
            'nextDate' => $date->addDay()->toDateString(),
            'isToday' => $date->equalTo($slots->today()),
            'closedReason' => $slots->isClosedOn($date)
                ? $slots->dateRejectionReason($date)
                : null,
            'quotaPerSlot' => Config::integer('booking.quota_per_slot'),
            'rows' => $this->baris($date, $slots),
        ]);
    }

    /**
     * Satu baris per slot, berisi booking yang menempatinya.
     *
     * Bookingnya diambil dengan SATU kueri untuk seluruh hari lalu
     * dikelompokkan di PHP — sebelas kueri untuk sebelas baris adalah cara N+1
     * masuk lewat pintu belakang (.claude/rules/10).
     *
     * @return list<array{time: string, remaining: int, is_full: bool, bookings: list<array<string, mixed>>}>
     */
    private function baris(CarbonImmutable $date, SlotService $slots): array
    {
        $perSlot = Booking::query()
            ->occupyingQuota()
            ->where('booking_date', $date->toDateString())
            ->with(['user:id,name', 'vehicle.carModel:id,name', 'servicePackage:id,name'])
            ->orderBy('booking_time')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Booking $booking): string => SlotTime::short($booking->booking_time));

        return array_map(
            fn (array $slot): array => [
                ...$slot,
                'bookings' => ($perSlot[$slot['time']] ?? collect())
                    ->map(BookingPresenter::scheduleCard(...))
                    ->values()
                    ->all(),
            ],
            $slots->availability($date),
        );
    }
}
