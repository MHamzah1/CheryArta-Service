<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\BookingStatus;
use RuntimeException;

/**
 * Invoice tidak bisa dibuat untuk booking ini (keputusan grill Q1 & Q3).
 *
 * Dua sebabnya, dan keduanya sengaja bukan 403:
 *
 * 1. **Booking belum selesai.** Advisornya berhak membuat invoice; bookingnya
 *    yang belum sampai di tahap itu.
 * 2. **Sudah punya invoice yang masih berlaku.** Ini bukan penolakan wewenang
 *    melainkan tabrakan keadaan — biasanya dua advisor yang membuka booking
 *    yang sama, atau tombol yang ditekan dua kali. Sejak `booking_id` tidak
 *    lagi unik, database tidak lagi menangkapnya; yang menangkapnya adalah
 *    pemeriksaan terkunci di InvoiceService, dan pesannya harus menjelaskan
 *    apa yang sudah ada di sana.
 *
 * Penanganannya terpusat di bootstrap/app.php menjadi galat inline, polanya
 * sama dengan SlotUnavailableException di F1.4.
 */
final class InvoiceUnavailableException extends RuntimeException
{
    /** Kolom form yang harus disorot. */
    public const FIELD = 'invoice';

    public static function bookingNotCompleted(BookingStatus $status): self
    {
        return new self(sprintf(
            'Invoice hanya bisa dibuat untuk booking yang sudah selesai. Booking ini berstatus "%s".',
            $status->label(),
        ));
    }

    public static function alreadyExists(?string $number): self
    {
        return new self($number === null
            ? 'Booking ini sudah punya invoice draft. Buka invoice itu alih-alih membuat yang baru.'
            : sprintf('Booking ini sudah punya invoice %s. Batalkan (void) invoice itu dulu bila ingin menggantinya.', $number));
    }
}
