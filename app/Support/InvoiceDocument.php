<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * PDF invoice (roadmap 2.4.3).
 *
 * Dipakai dua controller — staf dan pemilik invoice — sehingga berkasnya tidak
 * berbeda tergantung siapa yang mengunduh. Apakah seseorang BOLEH mengunduh
 * dijawab InvoicePolicy, bukan di sini.
 *
 * **Tanpa satu pun gambar raster** (keputusan grill Q6). Ekstensi PHP `gd`
 * tidak terpasang di lingkungan pengembangan ini dan belum terbukti ada di
 * runtime Railway — dan karena `gd` hanya *suggest* pada dompdf, ketiadaannya
 * tidak menjatuhkan `composer install`, melainkan baru muncul saat PDF pertama
 * dirender di produksi. Kop suratnya karena itu berupa TEKS dari
 * `config/company.php`; tidak ada logo, tidak ada stempel.
 */
final class InvoiceDocument
{
    /**
     * Nama berkas unduhan: `Invoice-INV-2026-08-0001.pdf`.
     *
     * Garis miring nomor invoice diganti tanda hubung — Windows menolak nama
     * berkas yang memuatnya, dan peramban akan memotong namanya diam-diam.
     */
    public static function filename(Invoice $invoice): string
    {
        $nomor = $invoice->invoice_number ?? 'DRAFT-'.$invoice->getKey();

        return 'Invoice-'.str_replace('/', '-', $nomor).'.pdf';
    }

    public static function download(Invoice $invoice): Response
    {
        $invoice->loadMissing(['items', 'booking.user', 'booking.vehicle.carModel', 'booking.servicePackage']);

        return Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->setPaper('a4')
            ->download(self::filename($invoice));
    }
}
