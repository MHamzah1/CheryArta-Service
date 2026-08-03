<?php

declare(strict_types=1);

namespace App\Enums;

enum CarCategory: string
{
    case Suv = 'suv';
    case Sedan = 'sedan';
    case Mpv = 'mpv';
    case Ev = 'ev';

    public function label(): string
    {
        return match ($this) {
            self::Suv => 'SUV',
            self::Sedan => 'Sedan',
            self::Mpv => 'MPV',
            self::Ev => 'Listrik',
        };
    }

    /** Pilihan untuk dropdown & filter katalog. */
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
