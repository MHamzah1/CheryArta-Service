<?php

declare(strict_types=1);

namespace App\Enums;

enum ServicePackageCategory: string
{
    case FirstMaintenanceIce = 'first_maintenance_ice';
    case FirstMaintenanceEv = 'first_maintenance_ev';
    case FreeMaintenance = 'free_maintenance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FirstMaintenanceIce => 'Perawatan Pertama',
            self::FirstMaintenanceEv => 'Perawatan Pertama (Listrik)',
            self::FreeMaintenance => 'Servis Berkala Gratis',
            self::Other => 'Layanan Lain',
        };
    }

    /**
     * Paket pada kategori ini gratis menurut program purnajual Chery.
     * Kategori `Other` berbayar dan harganya ditentukan admin — lihat
     * pertanyaan terbuka Q2 di docs/README.md.
     */
    public function isFreeByDefault(): bool
    {
        return $this !== self::Other;
    }

    public static function options(): array
    {
        return array_map(
            fn (self $category) => ['value' => $category->value, 'label' => $category->label()],
            self::cases(),
        );
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
