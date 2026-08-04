<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Saringan daftar pesan masuk (docs/07 §A10).
 *
 * Berkas tersendiri, bukan menumpang SearchRequest: yang dibutuhkan di sini
 * adalah saringan status baca, bukan kotak pencarian bebas. Nilai di luar
 * daftar ditolak, jadi controller tidak perlu menebak apa pun.
 */
class ContactMessageFilterRequest extends FormRequest
{
    /** Otorisasinya `viewAny` di controller. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['belum', 'sudah'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.in' => 'Saringan status hanya menerima "belum" atau "sudah".',
        ];
    }

    /** Status terpilih, atau null bila seluruh pesan ditampilkan. */
    public function status(): ?string
    {
        $status = $this->validated('status');

        return is_string($status) && $status !== '' ? $status : null;
    }
}
