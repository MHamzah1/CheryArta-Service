<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CarModel;
use App\Models\ServicePackage;

/**
 * Daftar kode seri yang boleh dipilih di `service_packages.applicable_series`.
 *
 * Sumbernya bukan daftar tetap di kode, melainkan `car_models.series_code` yang
 * dikelola admin — plus kode yang sudah terlanjur dipakai paket layanan.
 * Bagian kedua itu penting: sistem lama punya paket `CSH_Free` sedangkan model
 * seri CSH-nya belum masuk katalog, dan tanpa penggabungan ini paket tersebut
 * mustahil disunting tanpa lebih dulu kehilangan serinya.
 *
 * Dipakai dua kali — sebagai pilihan di antarmuka dan sebagai aturan validasi —
 * dan justru karena itu hanya boleh hidup di satu tempat.
 */
final class SeriesCatalog
{
    /** @return list<string> */
    public static function codes(): array
    {
        $dariKatalog = CarModel::query()
            ->whereNotNull('series_code')
            ->distinct()
            ->pluck('series_code')
            ->all();

        $dariPaket = ServicePackage::query()
            ->whereNotNull('applicable_series')
            ->pluck('applicable_series')
            ->flatten()
            ->all();

        $codes = array_values(array_unique(array_filter(
            [...$dariKatalog, ...$dariPaket],
            static fn (mixed $code): bool => is_string($code) && $code !== '',
        )));

        sort($codes);

        return $codes;
    }
}
