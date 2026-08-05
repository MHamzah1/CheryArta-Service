<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Config;

/**
 * Penerbitan nomor invoice `INV/{YYYY}/{MM}/{NNNN}` (docs/05 §5.6).
 *
 * Satu-satunya tempat format itu ditulis. Menyalinnya ke service, ke uji, dan
 * ke tampilan PDF berarti tiga tempat yang perlahan berselisih — cacat B2
 * sistem lama.
 *
 * **Bulannya zona Asia/Jakarta, bukan UTC.** Invoice yang terbit pukul 00.30
 * WIB tanggal 1 September berada di bulan Agustus menurut UTC, dan akan
 * menyusup ke urutan bulan yang salah (temuan B7).
 */
final class InvoiceNumber
{
    private const PREFIX = 'INV';

    /**
     * Nomor berikutnya untuk bulan `$moment`.
     *
     * WAJIB dipanggil di dalam transaksi. Baris yang belum ada tidak bisa
     * dikunci, jadi dua penerbitan bersamaan bisa menyusun urutan yang sama —
     * unique index pada `invoices.invoice_number` yang menangkapnya, dan
     * InvoiceService yang mengulang transaksinya. Pola yang sama persis dengan
     * generator kode booking di F1.4.
     */
    public static function next(CarbonInterface $moment): string
    {
        $prefix = self::monthPrefix($moment);

        $terakhir = Invoice::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $urut = $terakhir === null
            ? 1
            : ((int) substr($terakhir, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }

    /** Bagian nomor yang sama untuk seluruh invoice dalam satu bulan. */
    public static function monthPrefix(CarbonInterface $moment): string
    {
        return self::PREFIX.'/'.$moment->copy()
            ->setTimezone(Config::string('booking.timezone'))
            ->format('Y/m').'/';
    }
}
