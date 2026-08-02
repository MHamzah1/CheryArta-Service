<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalisasi nomor telepon Indonesia.
 *
 * Sistem lama menyimpan nomor apa adanya termasuk tanda hubung hasil format
 * otomatis (0812-3456-7890) — bentuk itu tidak bisa dipakai untuk tautan
 * wa.me. Lihat docs/08-notifikasi-whatsapp.md §8.3.
 *
 * Aturan: database SELALU menyimpan bentuk ternormalisasi (62xxxxxxxxxx),
 * tampilan memakai forDisplay().
 */
final class PhoneNumber
{
    /**
     * 0812-3456-7890  → 6281234567890
     * +62 812 3456 78 → 62812345678
     * 81234567890     → 6281234567890
     */
    public static function normalize(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return '';
        }

        return match (true) {
            str_starts_with($digits, '62') => $digits,
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => $digits,
        };
    }

    /** 6281234567890 → 0812-3456-7890 */
    public static function forDisplay(string $normalized): string
    {
        $digits = preg_replace('/\D+/', '', $normalized) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        }

        $length = strlen($digits);

        if ($length < 9) {
            return $digits;
        }

        return implode('-', [
            substr($digits, 0, 4),
            substr($digits, 4, 4),
            substr($digits, 8),
        ]);
    }

    /** Valid bila, setelah dinormalisasi, berbentuk 62 + 8xx + 7-11 digit. */
    public static function isValid(string $raw): bool
    {
        return preg_match('/^628[1-9][0-9]{6,11}$/', self::normalize($raw)) === 1;
    }
}
