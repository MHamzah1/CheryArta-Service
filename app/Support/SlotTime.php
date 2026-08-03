<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Penyeragam bentuk jam slot.
 *
 * MySQL mengembalikan kolom TIME sebagai `09:00:00`, sedangkan SQLite (dipakai
 * uji) mengembalikan persis apa yang ditulis. Tanpa penyeragaman, kueri
 * `where('booking_time', '09:00')` lulus di uji tetapi tidak pernah cocok di
 * produksi — persis jenis cacat yang paling mahal karena baru terlihat setelah
 * rilis.
 *
 * Karena itu satu bentuk kanonis dipakai di mana-mana: `H:i:s`.
 */
final class SlotTime
{
    /** `09:00` atau `09:00:00` → `09:00:00`. */
    public static function normalize(string $time): string
    {
        return substr(trim($time), 0, 5).':00';
    }

    /** `09:00:00` → `09:00`. Bentuk yang dipakai config dan props Inertia. */
    public static function short(string $time): string
    {
        return substr(trim($time), 0, 5);
    }
}
