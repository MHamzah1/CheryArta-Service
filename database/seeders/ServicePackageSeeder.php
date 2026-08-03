<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ServicePackageCategory;
use App\Models\ServicePackage;
use Illuminate\Database\Seeder;

/**
 * Paket layanan dari sistem lama.
 *
 * `code` disalin PERSIS dari prototipe `index (1).html` baris 1596–1619 —
 * termasuk campuran huruf besar-kecilnya (`Tiggo_8_Free`, bukan
 * `tiggo_8_free`). Kode inilah yang dipakai `booking:import-firebase` untuk
 * memetakan data lama, sehingga mengubahnya memutus jalur impor
 * (.claude/rules/30-database.md).
 *
 * Seeder produksi — idempoten, aman dijalankan ulang.
 */
class ServicePackageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->packages() as $sortOrder => $package) {
            ServicePackage::updateOrCreate(
                ['code' => $package['code']],
                [...$package, 'sort_order' => $sortOrder],
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function packages(): array
    {
        return [
            [
                'code' => 'first_maintenance_1000',
                'name' => 'First Maintenance (1.000 km/1 Bln)',
                'category' => ServicePackageCategory::FirstMaintenanceIce,
                'applicable_series' => null,
                'estimated_duration_minutes' => 60,
                'price' => 0,
                'is_free' => true,
            ],
            [
                'code' => 'first_maintenance_5000',
                'name' => 'First Maintenance (5.000 km/3 Bln)',
                'category' => ServicePackageCategory::FirstMaintenanceIce,
                'applicable_series' => null,
                'estimated_duration_minutes' => 60,
                'price' => 0,
                'is_free' => true,
            ],
            [
                'code' => 'first_maintenance_ev_5000',
                'name' => 'First Maintenance EV (5.000 km/6 Bln)',
                'category' => ServicePackageCategory::FirstMaintenanceEv,
                'applicable_series' => ['ev'],
                'estimated_duration_minutes' => 60,
                'price' => 0,
                'is_free' => true,
            ],
            [
                'code' => 'Tiggo_5X_Cross_Free',
                'name' => 'Tiggo 5X / Cross Series: Free Maintenance (5.000 km/6 Bln) - (10.000 km/12 Bln) - (20.000 km/24 Bln) - (30.000 km/36 Bln) - (40.000 km/48 Bln) - (60.000 km/72 Bln)',
                'category' => ServicePackageCategory::FreeMaintenance,
                'applicable_series' => ['tiggo_5x_cross'],
                'estimated_duration_minutes' => 90,
                'price' => 0,
                'is_free' => true,
            ],
            [
                'code' => 'Tiggo_8_Free',
                'name' => 'Tiggo 8 Series: Free Maintenance (15.000 km/12 Bln) - (30.000 km/36 Bln) - (45.000 km/48 Bln) - (60.000 km/72 Bln)',
                'category' => ServicePackageCategory::FreeMaintenance,
                'applicable_series' => ['tiggo_8'],
                'estimated_duration_minutes' => 90,
                'price' => 0,
                'is_free' => true,
            ],
            [
                'code' => 'EV_Free',
                'name' => 'EV Series: Free Maintenance (5.000 km/6 Bln) - (15.000 km/12 Bln) - (30.000 km/36 Bln) - (45.000 km/48 Bln) - (60.000 km/72 Bln)',
                'category' => ServicePackageCategory::FreeMaintenance,
                'applicable_series' => ['ev'],
                'estimated_duration_minutes' => 90,
                'price' => 0,
                'is_free' => true,
            ],
            [
                'code' => 'CSH_Free',
                'name' => 'CSH Series: Free Maintenance (1.000 km/1 Bln) - (15.000 km/12 Bln) - (30.000 km/36 Bln) - (45.000 km/48 Bln) - (60.000 km/72 Bln)',
                'category' => ServicePackageCategory::FreeMaintenance,
                'applicable_series' => ['csh'],
                'estimated_duration_minutes' => 90,
                'price' => 0,
                'is_free' => true,
            ],
            [
                // PERTANYAAN TERBUKA Q2 (docs/README.md): harga paket berbayar
                // belum diputuskan. Sengaja 0 dan `is_free = false` — bukan
                // menebak angka, tetapi juga tidak menandainya gratis.
                // Harganya diisi admin lewat layar F1.3.
                'code' => 'Other',
                'name' => 'Servis Lainnya',
                'category' => ServicePackageCategory::Other,
                'description' => 'Keluhan atau pekerjaan di luar paket perawatan berkala. Biaya dihitung setelah kendaraan diperiksa.',
                'applicable_series' => null,
                'estimated_duration_minutes' => 60,
                'price' => 0,
                'is_free' => false,
            ],
        ];
    }
}
