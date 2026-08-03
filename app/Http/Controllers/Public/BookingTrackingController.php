<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\BookingPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Cek Service" tanpa login (docs/05 §5.7).
 *
 * Pengganti fitur lama yang membiarkan siapa pun mengambil seluruh basis data
 * lalu menyaringnya di browser (temuan S8). Tiga pembatas menggantikannya:
 *
 * 1. Pencarian memakai **kode booking**, bukan plat nomor — plat mudah dilihat
 *    orang lain di parkiran, kode booking tidak.
 * 2. Yang dikembalikan hanya kode, jadwal, status, dan perkiraan selesai —
 *    lihat BookingPresenter::publicTracking().
 * 3. Rate limit 10 permintaan per menit, dipasang di rute, agar kode tidak bisa
 *    ditebak dengan mencoba berulang kali.
 */
class BookingTrackingController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $kode = trim((string) $request->query('kode', ''));

        return Inertia::render('cek-service', [
            'kode' => $kode,
            'booking' => $kode === '' ? null : $this->cari($kode),
            // Dibedakan dari `booking === null` saat kode belum diisi sama
            // sekali, supaya halaman tidak menuduh pengguna salah sebelum ia
            // sempat mengetik apa pun.
            'sudahDicari' => $kode !== '',
        ]);
    }

    /** @return array<string, mixed>|null */
    private function cari(string $kode): ?array
    {
        $booking = Booking::query()
            ->where('booking_code', $kode)
            ->with('servicePackage:id')
            ->first();

        return $booking === null ? null : BookingPresenter::publicTracking($booking);
    }
}
