<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Panel ubah status booking (docs/05 §5.3, roadmap 1.5.3).
 *
 * Controller sengaja hanya tiga baris. Peta transisi yang sah ada di
 * App\Enums\BookingStatus, penulisannya beserta efek samping dan riwayat
 * status ada di BookingService — transisi tidak sah berujung
 * InvalidStatusTransitionException, yang ditangani terpusat di
 * bootstrap/app.php menjadi 422.
 */
class BookingStatusController extends Controller
{
    public function __invoke(
        UpdateBookingStatusRequest $request,
        Booking $booking,
        BookingService $bookings,
    ): RedirectResponse {
        Gate::authorize('updateStatus', $booking);

        $booking = $bookings->changeStatus(
            $booking,
            $request->targetStatus(),
            $request->user(),
            $request->validated('note'),
            $request->validated('odometer') === null ? null : (int) $request->validated('odometer'),
        );

        return back()->with(
            'success',
            "Status booking {$booking->booking_code} diubah menjadi \"{$booking->status->label()}\".",
        );
    }
}
