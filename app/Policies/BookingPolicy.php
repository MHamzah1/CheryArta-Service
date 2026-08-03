<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Config;

/**
 * Booking hanya boleh disentuh pemiliknya — staf punya jalurnya sendiri di
 * `/admin` (F1.5), dengan policy yang berbeda aturannya.
 *
 * Controller tetap mengambil datanya lewat relasi
 * (`$user->bookings()->where(...)`), sehingga booking milik orang lain tidak
 * pernah sampai ke policy ini. Policy-nya ada sebagai lapis kedua
 * (.claude/rules/50-keamanan.md).
 */
class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $this->miliknya($user, $booking);
    }

    /**
     * Menjadwal ulang dan membatalkan memakai syarat yang sama persis
     * (docs/05 §5.4): status masih `pending`/`confirmed` DAN tanggalnya belum
     * melewati batas H-1.
     *
     * Dua-duanya dihitung di sini, lalu dikirim ke React sebagai props boolean
     * — menyembunyikan tombolnya saja bukan pengaman.
     */
    public function reschedule(User $user, Booking $booking): bool
    {
        return $this->miliknya($user, $booking)
            && $booking->status->isReschedulable()
            && $this->masihDalamBatasWaktu($booking);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $this->reschedule($user, $booking);
    }

    private function miliknya(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    /**
     * Batas waktu perubahan mandiri, dibaca dari config — bukan angka yang
     * ditulis ulang di sini (temuan B2 sistem lama).
     */
    private function masihDalamBatasWaktu(Booking $booking): bool
    {
        $batas = now(Config::string('booking.timezone'))
            ->startOfDay()
            ->addDays(Config::integer('booking.reschedule_min_days_before'));

        return ! $booking->booking_date->startOfDay()->lessThan($batas);
    }
}
