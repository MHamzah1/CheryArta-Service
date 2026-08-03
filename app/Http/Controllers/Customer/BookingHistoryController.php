<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Support\BookingPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Riwayat & booking mendatang milik customer (US-C5).
 *
 * Seluruh data diambil lewat relasi `$user->bookings()`, sehingga booking
 * orang lain tidak pernah masuk ke kueri sejak awal
 * (.claude/rules/50-keamanan.md).
 */
class BookingHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        // Disaring di database, bukan diambil semua lalu disaring di PHP
        // (.claude/rules/10-backend-laravel.md).
        $vehicleId = $request->integer('kendaraan') ?: null;

        $bookings = $request->user()
            ->bookings()
            ->with(['vehicle.carModel', 'servicePackage'])
            ->when($vehicleId !== null, fn ($query) => $query->where('vehicle_id', $vehicleId))
            ->orderByDesc('booking_date')
            ->orderByDesc('booking_time')
            ->paginate(10)
            ->withQueryString()
            ->through(BookingPresenter::summary(...));

        return Inertia::render('riwayat/index', [
            'bookings' => $bookings,
            'vehicles' => $request->user()
                ->vehicles()
                ->orderByDesc('is_primary')
                ->orderBy('plate_full')
                ->get(['id', 'plate_full'])
                ->all(),
            'filters' => ['kendaraan' => $vehicleId],
            'upcomingCount' => $request->user()->bookings()->upcoming()->count(),
        ]);
    }
}
