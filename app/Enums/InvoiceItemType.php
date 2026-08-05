<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis baris invoice (docs/04 §4.2).
 *
 * Dua nilai, bukan lebih. Menambah "diskon" atau "pajak" sebagai jenis item
 * akan membuat totalnya bisa dihitung dua cara — lewat item dan lewat kolom
 * `discount`/`tax` — dan keduanya pasti berselisih suatu hari (cacat B2).
 */
enum InvoiceItemType: string
{
    case Jasa = 'jasa';
    case Part = 'part';

    public function label(): string
    {
        return match ($this) {
            self::Jasa => 'Jasa',
            self::Part => 'Sparepart',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $type): array => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
