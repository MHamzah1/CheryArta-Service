<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pembatalan mandiri (US-C6).
 *
 * Alasan selalu wajib: tanpa alasan, advisor tidak punya bahan untuk
 * membedakan pelanggan yang berubah rencana dari jadwal yang memang salah
 * ditawarkan (docs/05 §5.4).
 */
class CancelBookingRequest extends FormRequest
{
    /** Pilihan baku yang ditawarkan di antarmuka, ditegakkan juga di server. */
    public const ALASAN_UMUM = [
        'Berubah rencana',
        'Sudah servis di tempat lain',
        'Salah pilih jadwal',
        'Lainnya',
    ];

    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking && $this->user()->can('cancel', $booking);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason_choice' => ['required', Rule::in(self::ALASAN_UMUM)],

            // Wajib hanya bila alasannya "Lainnya" — kalau tidak, isian bebas
            // ini melengkapi pilihan di atas.
            'reason_note' => ['nullable', 'required_if:reason_choice,Lainnya', 'string', 'max:200'],
        ];
    }

    /** Alasan gabungan yang tersimpan di `cancel_reason` dan ikut ke riwayat status. */
    public function alasanLengkap(): string
    {
        $pilihan = (string) $this->validated('reason_choice');
        $catatan = trim((string) ($this->validated('reason_note') ?? ''));

        if ($catatan === '') {
            return $pilihan;
        }

        return $pilihan === 'Lainnya' ? $catatan : "{$pilihan} — {$catatan}";
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason_choice.required' => 'Pilih alasan pembatalan.',
            'reason_choice.in' => 'Alasan pembatalan yang dipilih tidak dikenali.',
            'reason_note.required_if' => 'Tuliskan alasan pembatalan Anda.',
            'reason_note.max' => 'Alasan paling panjang 200 karakter.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'reason_choice' => 'alasan pembatalan',
            'reason_note' => 'keterangan alasan',
        ];
    }
}
