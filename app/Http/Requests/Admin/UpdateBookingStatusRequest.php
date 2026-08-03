<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Panel ubah status di detail booking (docs/05 §5.3, docs/07 §A2).
 *
 * Yang divalidasi di sini hanya BENTUK masukannya. Apakah perpindahannya sah
 * ditentukan App\Enums\BookingStatus dan ditegakkan BookingService di dalam
 * transaksi — memeriksanya di sini juga berarti aturan yang sama hidup di dua
 * tempat, persis cara cacat B2 sistem lama bermula.
 */
class UpdateBookingStatusRequest extends FormRequest
{
    /** Otorisasinya `updateStatus` di controller — lihat Admin\BookingStatusController. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(BookingStatus::values())],

            // Alasan pembatalan wajib (docs/05 §5.4). Untuk status lain,
            // catatan ini opsional dan ikut terlihat customer di timeline.
            'note' => [
                Rule::requiredIf(fn (): bool => $this->input('status') === BookingStatus::Cancelled->value),
                'nullable',
                'string',
                'max:255',
            ],

            'odometer' => ['nullable', 'integer', 'between:0,999999', 'gte:'.$this->odometerMinimal()],
        ];
    }

    public function targetStatus(): BookingStatus
    {
        return BookingStatus::from((string) $this->validated('status'));
    }

    /**
     * Odometer tidak boleh mundur dari catatan terakhir kendaraan
     * (docs/05 §5.8) — angka yang mengecil hampir selalu salah ketik.
     */
    private function odometerMinimal(): int
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking
            ? ($booking->vehicle->last_odometer ?? 0)
            : 0;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status tujuan lebih dahulu.',
            'status.in' => 'Status yang dipilih tidak dikenali.',
            'note.required' => 'Alasan pembatalan wajib diisi dan akan terlihat oleh pelanggan.',
            'note.max' => 'Catatan paling panjang 255 karakter.',
            'odometer.gte' => 'Odometer tidak boleh lebih kecil daripada catatan terakhir kendaraan ('
                .number_format((float) $this->odometerMinimal(), 0, ',', '.').' km).',
            'odometer.between' => 'Odometer di antara 0 dan 999.999 km.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'status' => 'status',
            'note' => 'catatan',
            'odometer' => 'odometer',
        ];
    }
}
