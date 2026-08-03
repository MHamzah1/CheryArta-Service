<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Dari mana booking berasal.
 *
 * Membedakan keduanya penting karena aturannya berbeda: booking `walk_in`
 * dibuat admin untuk pelanggan yang sudah berdiri di depan meja, sehingga
 * aturan H-1 tidak berlaku (`config('booking.walk_in_bypasses_lead_time')`).
 * Kuota slot tetap ditegakkan untuk keduanya.
 */
enum BookingSource: string
{
    case Web = 'web';
    case WalkIn = 'walk_in';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Online',
            self::WalkIn => 'Datang Langsung',
        };
    }

    /** Apakah sumber ini boleh melewati aturan H-1. */
    public function bypassesLeadTime(): bool
    {
        return $this === self::WalkIn
            && (bool) config('booking.walk_in_bypasses_lead_time');
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
