<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Nomor WhatsApp Indonesia dalam bentuk apa pun yang lazim diketik pengguna:
 * `0812…`, `62812…`, `+62 812…`, atau berjeda tanda hubung.
 *
 * Validasinya menilai bentuk TERNORMALISASI, bukan bentuk mentah — kalau tidak,
 * `0812-3456-7890` dan `081234567890` akan dinilai berbeda padahal keduanya
 * nomor yang sama.
 */
final class IndonesianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PhoneNumber::isValid($value)) {
            $fail('Nomor WhatsApp tidak dikenali. Contoh yang benar: 0812-3456-7890.');
        }
    }
}
