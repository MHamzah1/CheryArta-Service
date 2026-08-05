<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Diterbitkan',
            self::Paid => 'Lunas',
            self::Void => 'Dibatalkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'noshow',
            self::Issued => 'confirmed',
            self::Paid => 'done',
            self::Void => 'cancel',
        };
    }

    /** Invoice yang sudah diterbitkan tidak boleh disunting lagi. */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Ikut dijumlahkan laporan Pendapatan (docs/07 §A9, keputusan grill Q9).
     *
     * `draft` dikecualikan karena belum pernah menjadi tagihan; `void` karena
     * tagihannya dicabut.
     */
    public function countsAsRevenue(): bool
    {
        return in_array($this, [self::Issued, self::Paid], true);
    }

    /**
     * Perpindahan yang sah (docs/05 §5.6).
     *
     * **`paid → void` sengaja ADA**, berbeda dari versi enum ini yang lahir di
     * F1.0 (keputusan grill Q2). Tanpa jalur itu, satu klik "Tandai Lunas" yang
     * keliru hanya bisa diperbaiki lewat DBeaver — dan jalan pintas semacam itu
     * yang membuat sistem lama tidak bisa dipercaya. Wewenangnya sudah sempit:
     * void milik Super Admin saja (docs/09 §9.3), ditegakkan InvoicePolicy.
     *
     * Kelas ini menjawab "boleh ke mana", BUKAN "siapa" — pemisahan yang sama
     * seperti BookingStatus sejak F1.5. Karena itu percobaan transisi tidak sah
     * berujung 422, bukan 403.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, match ($this) {
            self::Draft => [self::Issued, self::Void],
            self::Issued => [self::Paid, self::Void],
            self::Paid => [self::Void],
            self::Void => [],
        }, true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }

    /**
     * Nilai yang ikut dijumlahkan laporan Pendapatan.
     *
     * @return list<string>
     */
    public static function revenueValues(): array
    {
        return array_values(array_map(
            fn (self $status): string => $status->value,
            array_filter(self::cases(), fn (self $status): bool => $status->countsAsRevenue()),
        ));
    }

    /** @return list<array{value: string, label: string, tone: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'tone' => $status->tone(),
            ],
            self::cases(),
        );
    }
}
