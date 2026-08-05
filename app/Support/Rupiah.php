<?php

declare(strict_types=1);

namespace App\Support;

/**
 * "Rp 450.000" — di sisi server.
 *
 * Pasangan `formatRupiah()` di `lib/format.ts`, dan keduanya memang harus ada:
 * yang satu berjalan di peramban, yang lain di PHP. Dipakai PDF invoice dan
 * placeholder `{{ringkasan_biaya}}` — dua tempat yang tidak pernah melewati
 * React sama sekali.
 *
 * Bentuknya wajib sama persis dengan versi TypeScript-nya: pelanggan yang
 * membandingkan angka di layar dengan angka di PDF tidak boleh menemukan dua
 * cara penulisan untuk satu nilai.
 */
final class Rupiah
{
    /** Tanpa desimal — harga bengkel tidak pernah berakhir di angka sen. */
    public static function format(float|int|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}
