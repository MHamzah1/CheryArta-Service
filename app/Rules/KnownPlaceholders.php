<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Config;

/**
 * Menolak `{{typo}}` sebelum ia sampai ke pelanggan (docs/08 §8.4).
 *
 * Pesannya menyebut nama placeholder yang salah, bukan sekadar "tidak valid":
 * Super Admin yang mengetik `{{harga}}` perlu tahu bahwa yang salah adalah
 * kata itu, dan apa saja yang tersedia sebagai gantinya
 * (.claude/rules/10 — "Bukan 'Data tidak valid'").
 *
 * Daftar yang sah datang dari `config('whatsapp.allowed_placeholders')`, dan
 * tests/Unit/WhatsAppPlaceholderTest.php memastikan daftar itu identik dengan
 * yang benar-benar dirender App\Support\WhatsAppPlaceholders.
 */
final class KnownPlaceholders implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/i', $value, $matches);

        /** @var list<string> $sah */
        $sah = Config::array('whatsapp.allowed_placeholders');

        $asing = array_values(array_unique(array_diff($matches[1], $sah)));

        if ($asing === []) {
            return;
        }

        $fail(sprintf(
            'Placeholder %s tidak dikenali. Yang tersedia: %s.',
            implode(', ', array_map(fn (string $n): string => '{{'.$n.'}}', $asing)),
            implode(', ', array_map(fn (string $n): string => '{{'.$n.'}}', $sah)),
        ));
    }
}
