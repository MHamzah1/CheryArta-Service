<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Satu segmen plat nomor Indonesia.
 *
 * Prefix wilayah sah bila 1 ATAU 2 huruf — "B" (Jakarta) sama sahnya dengan
 * "AB" (Yogyakarta). Sistem lama memaksa dua huruf sehingga menolak plat
 * Jakarta yang jumlahnya justru paling banyak (temuan B1).
 */
final class ValidPlateSegment implements ValidationRule
{
    public const PREFIX = 'prefix';

    public const NUMBER = 'number';

    public const SUFFIX = 'suffix';

    public function __construct(private readonly string $segment) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Bagian plat nomor ini tidak sah.');

            return;
        }

        $value = trim($value);

        $ok = match ($this->segment) {
            self::PREFIX => preg_match('/^[A-Za-z]{1,2}$/', $value) === 1,
            self::NUMBER => preg_match('/^[0-9]{1,4}$/', $value) === 1,
            self::SUFFIX => preg_match('/^[A-Za-z]{1,3}$/', $value) === 1,
            default => false,
        };

        if ($ok) {
            return;
        }

        $fail(match ($this->segment) {
            self::PREFIX => 'Kode wilayah berisi 1 sampai 2 huruf, contohnya B atau AB.',
            self::NUMBER => 'Nomor plat berisi 1 sampai 4 angka.',
            self::SUFFIX => 'Huruf akhir plat berisi 1 sampai 3 huruf.',
            default => 'Bagian plat nomor ini tidak sah.',
        });
    }
}
