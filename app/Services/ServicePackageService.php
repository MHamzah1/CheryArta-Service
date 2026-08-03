<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\MasterDataInUseException;
use App\Models\ServicePackage;
use Illuminate\Support\Facades\DB;

/**
 * Aturan seputar paket layanan (docs/07-modul-admin.md §A5).
 *
 * Service tidak menyentuh request(), session(), atau auth() — semuanya
 * diterima lewat parameter (.claude/rules/10-backend-laravel.md).
 */
class ServicePackageService
{
    /** @param  array<string, mixed>  $data */
    public function create(array $data): ServicePackage
    {
        return ServicePackage::create($this->rapikan($data));
    }

    /** @param  array<string, mixed>  $data */
    public function update(ServicePackage $package, array $data): ServicePackage
    {
        $package->update($this->rapikan($data));

        return $package;
    }

    /**
     * Paket yang sudah dipakai booking hanya boleh dinonaktifkan — menghapusnya
     * membuat riwayat servis lama kehilangan keterangan pekerjaannya
     * (.claude/rules/30-database.md).
     */
    public function delete(ServicePackage $package): void
    {
        if ($package->isReferenced()) {
            throw new MasterDataInUseException(
                "Paket \"{$package->name}\" sudah dipakai booking, jadi tidak bisa dihapus. "
                .'Nonaktifkan saja agar tidak lagi muncul di form booking.',
            );
        }

        DB::transaction(fn () => $package->delete());
    }

    /**
     * Dua hal yang tidak boleh dipercayakan ke browser:
     *
     * 1. Paket gratis wajib berharga 0 — kalau tidak, invoice bisa menagih
     *    pekerjaan yang antarmukanya menulis "Gratis".
     * 2. `applicable_series` kosong berarti "berlaku untuk semua seri", dan
     *    skema menyatakannya dengan null, bukan array kosong. ServicePackage::
     *    scopeForSeries() bergantung pada perbedaan ini.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rapikan(array $data): array
    {
        $data['is_free'] = (bool) ($data['is_free'] ?? false);
        $data['price'] = $data['is_free'] ? 0 : ($data['price'] ?? 0);

        // Kolomnya NOT NULL berdefault 0, sedangkan input kosong sampai ke sini
        // sebagai null (ConvertEmptyStringsToNull).
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $series = array_values(array_filter(
            (array) ($data['applicable_series'] ?? []),
            static fn (mixed $code): bool => is_string($code) && $code !== '',
        ));

        $data['applicable_series'] = $series === [] ? null : $series;

        return $data;
    }
}
