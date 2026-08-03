<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Slot yang diminta tidak bisa dipakai — biasanya karena kuotanya sudah habis
 * diambil orang lain di antara saat halaman dimuat dan saat tombol ditekan.
 *
 * Dilempar dari BookingService **di dalam transaksi**, setelah baris slotnya
 * dikunci. Ini lapis kedua: Form Request sudah memeriksa hal yang sama lebih
 * dulu dengan pesan yang ramah, tetapi pemeriksaan itu tidak mengunci apa pun
 * dan karena itu tidak bisa dipercaya sebagai penentu akhir (docs/04 §4.2).
 *
 * Penanganannya terpusat di bootstrap/app.php: pesan muncul inline di bawah
 * kolom jam, sama seperti galat validasi lain.
 */
final class SlotUnavailableException extends RuntimeException
{
    /** Kolom form yang harus disorot; jam, bukan tanggal. */
    public const FIELD = 'booking_time';
}
