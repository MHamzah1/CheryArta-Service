<?php

declare(strict_types=1);

namespace App\Enums;

enum FuelType: string
{
    case Ice = 'ice';
    case Hybrid = 'hybrid';
    case Ev = 'ev';

    public function label(): string
    {
        return match ($this) {
            self::Ice => 'Bensin',
            self::Hybrid => 'Hybrid',
            self::Ev => 'Listrik',
        };
    }

    /**
     * Kategori paket perawatan pertama yang relevan untuk jenis bahan bakar
     * ini. Kendaraan listrik tidak memakai jadwal servis mesin pembakaran —
     * inilah alasan `first_maintenance_ev` dipisah di service_packages.
     */
    public function firstMaintenanceCategory(): ServicePackageCategory
    {
        return $this === self::Ev
            ? ServicePackageCategory::FirstMaintenanceEv
            : ServicePackageCategory::FirstMaintenanceIce;
    }

    public static function options(): array
    {
        return array_map(
            fn (self $fuel) => ['value' => $fuel->value, 'label' => $fuel->label()],
            self::cases(),
        );
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
