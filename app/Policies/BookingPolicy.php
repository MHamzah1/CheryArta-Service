<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Config;

/**
 * Dua audiens dengan aturan yang berbeda dalam satu policy.
 *
 * **Customer** hanya boleh menyentuh bookingnya sendiri, dan hanya selama
 * masih dalam batas H-1. Controller customer tetap mengambil datanya lewat
 * relasi (`$user->bookings()->where(...)`), sehingga booking milik orang lain
 * tidak pernah sampai ke sini; policy adalah lapis keduanya.
 *
 * **Staf** melihat seluruh booking — itu memang pekerjaannya (docs/09 §9.3).
 * Yang tetap dibatasi: penghapusan hanya Super Admin (docs/07 §A14).
 * Middleware `role` menjaga pintu masuk /admin, tetapi TIDAK menggantikan
 * policy ini (.claude/rules/50-keamanan.md).
 */
class BookingPolicy
{
    /** Daftar seluruh booking di panel admin. */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->isStaff() || $this->miliknya($user, $booking);
    }

    /** Booking walk-in atas nama pelanggan yang datang langsung (docs/07 §A2). */
    public function createWalkIn(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Dashboard admin (A1) dan halaman laporan (A9).
     *
     * Kedua role staf boleh melihat keduanya — dashboard yang dilihat advisor
     * identik dengan yang dilihat Super Admin, karena tidak satu pun elemennya
     * menyentuh pendapatan (docs/09 §9.3, PRD F2.1).
     */
    public function viewReports(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Tab **Pendapatan** di halaman laporan — Super Admin saja (docs/09 §9.3).
     *
     * Dipisahkan dari `viewReports` karena inilah satu-satunya laporan yang
     * dibatasi. Tab-nya TIDAK dirender untuk advisor, bukan dirender lalu
     * dinonaktifkan — tetapi yang menolak tetap server, bukan React (temuan S3).
     *
     * Catatan jujur: advisor tetap melihat total tiap invoice satu per satu di
     * A8, karena ia yang menerbitkannya. Pembatasan di sini soal kemudahan
     * agregat, bukan kerahasiaan angka.
     */
    public function viewRevenueReport(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Boleh mengubah status. Status TUJUAN mana yang sah ditentukan
     * App\Enums\BookingStatus, sengaja BUKAN di sini — policy menjawab
     * "siapa", state machine menjawab "boleh ke mana".
     *
     * Pemisahan itu yang membuat percobaan transisi mustahil dijawab 403:
     * advisornya memang berhak, perpindahannyalah yang tidak ada. Booking yang
     * sudah berakhir karena itu tetap lolos di sini, lalu ditolak 422 oleh
     * InvalidStatusTransitionException — dengan pesan yang menyebutkan status
     * terkininya.
     */
    public function updateStatus(User $user, Booking $booking): bool
    {
        return $user->isStaff();
    }

    /**
     * Penghapusan (soft delete) hanya Super Admin (docs/07 §A14).
     *
     * Advisor yang ingin membatalkan booking memakai transisi status
     * `cancelled` beserta alasannya — jejaknya tetap ada, dan itu memang yang
     * dibutuhkan bengkel. Menghapus baris dipakai untuk booking uji coba atau
     * salah input, bukan untuk pembatalan.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return $user->isSuperAdmin();
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
