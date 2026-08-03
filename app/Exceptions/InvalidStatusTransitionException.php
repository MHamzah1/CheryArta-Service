<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\BookingStatus;
use RuntimeException;

/**
 * Perpindahan status yang tidak ada di state machine (docs/05 §5.3).
 *
 * Dilempar dari BookingService, bukan diperiksa di controller: peta transisi
 * yang sah hidup di App\Enums\BookingStatus, dan menuliskannya ulang sebagai
 * rangkaian `if` di controller adalah persis cara cacat B2 sistem lama bermula.
 *
 * Penyebabnya biasanya bukan iseng, melainkan dua advisor yang membuka booking
 * yang sama: yang satu menekan "Selesai" setelah yang lain membatalkannya.
 * Karena itu pesannya menyebutkan status terkini, bukan sekadar "tidak sah".
 *
 * Penanganannya terpusat di bootstrap/app.php — 422 untuk permintaan JSON,
 * galat inline di panel ubah status untuk Inertia.
 */
final class InvalidStatusTransitionException extends RuntimeException
{
    /** Kolom form yang harus disorot. */
    public const FIELD = 'status';

    public static function between(BookingStatus $from, BookingStatus $to): self
    {
        return new self(sprintf(
            'Booking berstatus "%s" tidak bisa diubah menjadi "%s". Muat ulang halaman untuk melihat status terkini.',
            $from->label(),
            $to->label(),
        ));
    }
}
