<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Booking;
use App\Rules\AvailableSlot;
use App\Rules\BookableDate;
use App\Services\SlotService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Penjadwalan ulang mandiri (US-C6, docs/05 §5.4).
 *
 * Hanya tanggal dan jam yang boleh berubah — kendaraan dan paket layanan
 * mengikuti booking lama. Mengizinkannya berubah di sini berarti menyediakan
 * jalur kedua untuk membuat booking yang melewati sebagian pemeriksaan.
 */
class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking && $this->user()->can('reschedule', $booking);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(SlotService $slots): array
    {
        $booking = $this->route('booking');
        $abaikan = $booking instanceof Booking ? $booking->getKey() : null;

        return [
            'booking_date' => ['required', 'date_format:Y-m-d', new BookableDate($slots)],

            // Booking yang sedang dipindah tidak boleh menghalangi dirinya
            // sendiri: kuotanya dilepas dalam transaksi yang sama.
            'booking_time' => ['required', 'string', new AvailableSlot($slots, $abaikan)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'booking_date.required' => 'Pilih tanggal baru.',
            'booking_date.date_format' => 'Tanggal yang dipilih tidak dikenali.',
            'booking_time.required' => 'Pilih jam baru.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'booking_date' => 'tanggal servis',
            'booking_time' => 'jam servis',
        ];
    }
}
