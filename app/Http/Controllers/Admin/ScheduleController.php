<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\WhatsAppTemplateKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleRequest;
use App\Models\Booking;
use App\Services\SlotService;
use App\Services\WhatsApp\WhatsAppNotifier;
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
    public function __invoke(ScheduleRequest $request, SlotService $slots, WhatsAppNotifier $whatsapp): Response
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
            'rows' => $this->baris($date, $slots, $whatsapp),
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
    private function baris(CarbonImmutable $date, SlotService $slots, WhatsAppNotifier $whatsapp): array
    {
        $perSlot = Booking::query()
            ->occupyingQuota()
            ->where('booking_date', $date->toDateString())
            // `phone_wa` ikut diambil karena draft pengingat membutuhkannya di
            // server. Ia TIDAK ikut ke props — lihat kartuDenganPengingat().
            ->with(['user:id,name,phone_wa', 'vehicle.carModel:id,name', 'servicePackage:id,name'])
            ->orderBy('booking_time')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Booking $booking): string => SlotTime::short($booking->booking_time));

        return array_map(
            fn (array $slot): array => [
                ...$slot,
                'bookings' => ($perSlot[$slot['time']] ?? collect())
                    ->map(fn (Booking $booking): array => $this->kartuDenganPengingat($booking, $whatsapp))
                    ->values()
                    ->all(),
            ],
            $slots->availability($date),
        );
    }

    /**
     * Kartu jadwal + tombol pengingat H-1 (docs/07 §A3, keputusan grill Q8).
     *
     * Layar inilah satu-satunya yang sudah menjawab "siapa saja yang servis
     * besok", jadi di sinilah pengingat paling murah ditekan — bukan dengan
     * membuka delapan halaman detail untuk delapan pengingat.
     *
     * `reminder` bernilai null bila bookingnya belum dikonfirmasi, jadwalnya
     * sudah lewat, pelanggannya tidak punya nomor WhatsApp, atau template
     * pengingat sedang dinonaktifkan. Tombolnya tidak dirender sama sekali —
     * tombol tanpa tujuan lebih buruk daripada tidak ada tombol.
     *
     * @return array<string, mixed>
     */
    private function kartuDenganPengingat(Booking $booking, WhatsAppNotifier $whatsapp): array
    {
        $reminder = $booking->isRemindable()
            ? $whatsapp->draft($booking, WhatsAppTemplateKey::BookingReminder)
            : null;

        return [
            ...BookingPresenter::scheduleCard($booking),
            'reminder' => $reminder?->toArray(),
        ];
    }
}
