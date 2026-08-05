<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * "Senin, 3 Agustus 2026" — di sisi server.
 *
 * `lib/format.ts` melakukan hal yang sama untuk React, dan itu memang harus
 * terpisah: yang satu berjalan di peramban, yang lain di PHP. Yang TIDAK boleh
 * adalah beberapa salinan di sisi PHP — dan sampai berkas ini lahir memang ada
 * dua (SlotService dan WhatsAppPlaceholders), masing-masing dengan array nama
 * bulannya sendiri. PDF invoice akan menjadi yang ketiga; itulah cacat B2
 * sistem lama tumbuh persis di depan mata.
 *
 * Zona waktu selalu zona bengkel, bukan zona server maupun zona peramban —
 * tanggal yang dihitung dalam UTC meleset satu hari saat diakses dini hari
 * (temuan B7).
 */
final class TanggalIndonesia
{
    private const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    private const BULAN = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /** "Senin, 3 Agustus 2026" */
    public static function lengkap(CarbonInterface $date): string
    {
        return self::namaHari($date).', '.self::tanpaHari($date);
    }

    /** "3 Agustus 2026" — tanpa nama hari, untuk baris yang sudah sempit. */
    public static function tanpaHari(CarbonInterface $date): string
    {
        return $date->day.' '.self::BULAN[$date->month - 1].' '.$date->year;
    }

    public static function namaHari(CarbonInterface $date): string
    {
        return self::HARI[$date->dayOfWeek];
    }
}
