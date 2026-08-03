<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Satu kotak pencarian untuk dua daftar A6: customer dan kendaraan
 * (docs/07 §A6, roadmap 1.5.6).
 *
 * Dipakai bersama karena keduanya memang punya kebutuhan yang sama — satu
 * kolom bebas, dicocokkan di server. Yang dicocokkan berbeda per daftar, dan
 * itu urusan controllernya masing-masing.
 */
class SearchRequest extends FormRequest
{
    /** Otorisasinya `viewAny` di masing-masing controller. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'cari' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** Kata kunci, atau null bila kolomnya kosong — bukan string kosong. */
    public function keyword(): ?string
    {
        $kata = trim((string) $this->validated('cari', ''));

        return $kata === '' ? null : $kata;
    }
}
