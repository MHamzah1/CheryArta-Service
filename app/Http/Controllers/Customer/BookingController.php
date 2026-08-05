<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CancelBookingRequest;
use App\Http\Requests\Customer\RescheduleBookingRequest;
use App\Http\Requests\Customer\StoreBookingRequest;
use App\Models\Booking;
use App\Models\ServicePackage;
use App\Services\BookingService;
use App\Services\SlotService;
use App\Support\BookingPresenter;
use App\Support\InvoicePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Booking servis milik customer (docs/05 §5.2 & §5.4).
 *
 * Controller tetap tipis: otorisasi → Form Request → Service → render/redirect.
 * Seluruh aturan slot ada di SlotService, seluruh penulisan data di
 * BookingService (.claude/rules/10-backend-laravel.md).
 */
class BookingController extends Controller
{
    public function create(Request $request, SlotService $slots): Response
    {
        $vehicles = $request->user()
            ->vehicles()
            ->with('carModel:id,name,series_code')
            ->orderByDesc('is_primary')
            ->orderBy('plate_full')
            ->get();

        return Inertia::render('booking/create', [
            'vehicles' => $vehicles->map(BookingPresenter::vehicleOption(...))->all(),
            'servicePackages' => $this->paketAktif(),
            'slotRules' => BookingPresenter::slotRules($slots),
        ]);
    }

    public function store(StoreBookingRequest $request, BookingService $bookings): RedirectResponse
    {
        $booking = $bookings->create($request->user(), $request->validated());

        return redirect()
            ->route('customer.booking.success', $booking)
            ->with('success', "Booking {$booking->booking_code} berhasil dibuat.");
    }

    /** Halaman sukses dipisah dari detail: isinya konfirmasi, bukan pemantauan. */
    public function success(Booking $booking): Response
    {
        Gate::authorize('view', $booking);

        $booking->load(['vehicle.carModel', 'servicePackage']);

        return Inertia::render('booking/sukses', [
            'booking' => BookingPresenter::detail($booking),
        ]);
    }

    public function show(Request $request, Booking $booking, SlotService $slots): Response
    {
        Gate::authorize('view', $booking);

        $booking->load([
            'vehicle.carModel',
            'servicePackage',
            'rescheduledFrom:id,booking_code,booking_date,booking_time',
            'statusHistories' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
        ]);

        return Inertia::render('booking/show', [
            'booking' => BookingPresenter::detail($booking),
            'timeline' => $booking->statusHistories->map(BookingPresenter::timelineEntry(...))->all(),

            // Keputusan boleh/tidak dihitung server, dikirim sebagai boolean.
            // Menyembunyikan tombol bukan pengaman (.claude/rules/20).
            'canReschedule' => $request->user()->can('reschedule', $booking),
            'canCancel' => $request->user()->can('cancel', $booking),

            'cancelReasons' => CancelBookingRequest::ALASAN_UMUM,
            'slotRules' => BookingPresenter::slotRules($slots),

            // Invoice hanya muncul setelah DITERBITKAN — patokannya `issued_at`,
            // bukan status (keputusan grill Q7). Draft yang masih disunting
            // advisor bukan urusan pelanggan; yang sudah terbit lalu di-void
            // tetap tampil, karena justru itu yang perlu ia ketahui.
            'invoice' => $this->invoiceTerbit($booking),
        ]);
    }

    public function reschedule(
        RescheduleBookingRequest $request,
        Booking $booking,
        BookingService $bookings,
    ): RedirectResponse {
        $baru = $bookings->reschedule($booking, $request->validated(), $request->user());

        return redirect()
            ->route('customer.booking.show', $baru)
            ->with('success', "Jadwal berhasil dipindahkan. Kode booking baru Anda: {$baru->booking_code}.");
    }

    public function cancel(CancelBookingRequest $request, Booking $booking, BookingService $bookings): RedirectResponse
    {
        $bookings->cancel($booking, $request->alasanLengkap(), $request->user());

        return redirect()
            ->route('customer.booking.show', $booking)
            ->with('success', "Booking {$booking->booking_code} telah dibatalkan.");
    }

    /**
     * Paket yang boleh dipilih. `applicable_series` ikut dikirim supaya
     * antarmuka bisa menyorot paket yang cocok dengan kendaraan terpilih —
     * server tetap hanya mewajibkan paketnya aktif (docs/05 §5.8).
     *
     * @return list<array<string, mixed>>
     */
    private function paketAktif(): array
    {
        return ServicePackage::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'category', 'description', 'applicable_series', 'estimated_duration_minutes', 'price', 'is_free'])
            ->map(BookingPresenter::packageOption(...))
            ->all();
    }

    /**
     * Invoice yang boleh dilihat pemiliknya dari detail booking.
     *
     * Patokannya `issued_at`, bukan status (keputusan grill Q7): yang masih
     * draft belum menjadi tagihan, sedangkan yang pernah terbit lalu di-void
     * justru harus tetap terlihat.
     *
     * @return array<string, mixed>|null
     */
    private function invoiceTerbit(Booking $booking): ?array
    {
        $invoice = $booking->invoices()
            ->whereNotNull('issued_at')
            ->latest('id')
            ->first();

        return $invoice === null ? null : InvoicePresenter::summary($invoice);
    }
}
