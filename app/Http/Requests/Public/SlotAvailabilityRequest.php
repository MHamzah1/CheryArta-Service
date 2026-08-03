<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Enums\BookingSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Parameter `GET /booking/slots` (docs/03 §3.4).
 *
 * Endpoint ini terbuka untuk tamu, jadi apa pun yang masuk diperlakukan
 * sebagai tidak dipercaya — termasuk `sumber`, yang menentukan apakah aturan
 * H-1 berlaku.
 */
class SlotAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'sumber' => ['nullable', Rule::in(BookingSource::values())],
        ];
    }

    /**
     * Sumber yang benar-benar dipakai untuk menghitung aturan tanggal.
     *
     * `walk_in` melewati aturan H-1, dan karena itu HANYA dihormati bila yang
     * bertanya adalah staf yang login. Pengunjung yang mengetik
     * `?sumber=walk_in` di URL tetap mendapat aturan web biasa — keputusannya
     * dihitung di server, tidak diambil dari apa yang dikirim peramban
     * (temuan S3).
     */
    public function source(): BookingSource
    {
        $diminta = $this->input('sumber');

        return $diminta === BookingSource::WalkIn->value && $this->user()?->isStaff() === true
            ? BookingSource::WalkIn
            : BookingSource::Web;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal wajib diisi.',
            'date.date_format' => 'Format tanggal harus YYYY-MM-DD.',
        ];
    }
}
