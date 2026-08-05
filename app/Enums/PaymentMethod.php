<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cara pelanggan membayar (docs/04 §4.2).
 *
 * **Pencatatan saja.** Tidak ada payment gateway, tidak ada verifikasi ke bank,
 * tidak ada rekonsiliasi otomatis — yang tersimpan hanyalah apa yang diketik
 * advisor saat menandai lunas.
 *
 * Enum, bukan `config`, mengikuti .claude/rules/30: kolomnya varchar + PHP Enum.
 * Menambah "QRIS" kelak berarti menambah satu case di sini — tidak perlu
 * `ALTER TABLE`.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Edc = 'edc';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Transfer => 'Transfer',
            self::Edc => 'Kartu (EDC)',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $method): string => $method->value, self::cases());
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $method): array => ['value' => $method->value, 'label' => $method->label()],
            self::cases(),
        );
    }
}
