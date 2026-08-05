<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\InvoiceStatus;
use RuntimeException;

/**
 * Perpindahan status invoice yang tidak ada di state machine (docs/05 §5.6).
 *
 * Dilempar dari InvoiceService dengan barisnya terkunci — polanya sama persis
 * dengan InvalidStatusTransitionException di F1.5, dan alasannya juga sama:
 * peta transisi hidup di App\Enums\InvoiceStatus, dan menuliskannya ulang
 * sebagai rangkaian `if` di controller adalah cara cacat B2 bermula.
 *
 * **Sengaja 422, bukan 403.** Advisor yang menekan "Terbitkan" pada invoice
 * yang baru saja diterbitkan rekannya memang BERHAK menerbitkan invoice;
 * perpindahannya yang tidak ada. Menjawab 403 akan menyalahkan orang yang benar.
 */
final class InvalidInvoiceTransitionException extends RuntimeException
{
    /** Kolom form yang harus disorot. */
    public const FIELD = 'status';

    public static function between(InvoiceStatus $from, InvoiceStatus $to): self
    {
        return new self(sprintf(
            'Invoice berstatus "%s" tidak bisa diubah menjadi "%s". Muat ulang halaman untuk melihat keadaan terkini.',
            $from->label(),
            $to->label(),
        ));
    }

    public static function notEditable(InvoiceStatus $status): self
    {
        return new self(sprintf(
            'Invoice berstatus "%s" tidak bisa disunting lagi. Batalkan (void) invoice ini lalu buat penggantinya.',
            $status->label(),
        ));
    }

    public static function needsItem(): self
    {
        return new self('Invoice tanpa item tidak bisa diterbitkan. Tambahkan minimal satu baris jasa atau sparepart.');
    }
}
