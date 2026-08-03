<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BookingStatus;
use App\Support\BookingFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Saringan daftar booking admin (docs/07 §A2).
 *
 * Query string pun divalidasi lewat Form Request, bukan dibaca mentah dengan
 * `$request->input()`. Bukan formalitas: nilainya masuk ke klausa `where` dan
 * ke penentuan arah `orderBy`, dan nilai yang tidak diperiksa akan
 * menghasilkan daftar kosong tanpa penjelasan.
 *
 * Galat di sini muncul sebagai pesan di atas form saringan — tautan lama yang
 * statusnya sudah tidak dikenal mengembalikan advisor ke daftar sebelumnya,
 * bukan ke halaman galat.
 */
class BookingFilterRequest extends FormRequest
{
    /** Otorisasinya ada di controller (`viewAny`) — lihat Admin\BookingController. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(BookingStatus::values())],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
            'paket' => ['nullable', 'integer', 'exists:service_packages,id'],
            'advisor' => ['nullable', 'integer', 'exists:users,id'],
            'urutan' => ['nullable', Rule::in([BookingFilters::URUTAN_TERBARU, BookingFilters::URUTAN_TERDEKAT])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal daripada tanggal mulai.',
            'dari.date_format' => 'Tanggal mulai tidak dikenali.',
            'sampai.date_format' => 'Tanggal akhir tidak dikenali.',
        ];
    }

    public function filters(): BookingFilters
    {
        return BookingFilters::fromValidated($this->validated());
    }
}
