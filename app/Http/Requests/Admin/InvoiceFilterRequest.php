<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Saringan daftar invoice (docs/07 §A8).
 *
 * Alasan query string ikut divalidasi sama dengan BookingFilterRequest:
 * nilainya masuk ke klausa `where`, dan yang tidak diperiksa menghasilkan
 * daftar kosong tanpa penjelasan.
 */
class InvoiceFilterRequest extends FormRequest
{
    /** Otorisasinya ada di controller (`viewAny`) — lihat Admin\InvoiceController. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            // Nomor invoice, kode booking, atau nama pelanggan.
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(InvoiceStatus::values())],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ];
    }

    /**
     * Bentuk yang menempel di query string dan mengisi kembali form saringan.
     *
     * @return array{cari: string|null, status: string|null, dari: string|null, sampai: string|null}
     */
    public function filters(): array
    {
        return [
            'cari' => $this->stringOrNull('cari'),
            'status' => $this->stringOrNull('status'),
            'dari' => $this->stringOrNull('dari'),
            'sampai' => $this->stringOrNull('sampai'),
        ];
    }

    private function stringOrNull(string $key): ?string
    {
        $value = $this->validated($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal daripada tanggal mulai.',
            'dari.date_format' => 'Tanggal mulai tidak dikenali.',
            'sampai.date_format' => 'Tanggal akhir tidak dikenali.',
            'status.in' => 'Status invoice tidak dikenali.',
        ];
    }
}
