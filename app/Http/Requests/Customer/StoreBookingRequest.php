<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Rules\AvailableSlot;
use App\Rules\BookableDate;
use App\Services\SlotService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Booking baru dari halaman customer (docs/05 §5.8).
 *
 * Yang TIDAK ada di antara aturannya, dan tidak boleh pernah ada:
 * `user_id`, `booking_code`, dan `status` — ketiganya ditentukan server
 * (.claude/rules/50-keamanan.md).
 */
class StoreBookingRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(SlotService $slots): array
    {
        return [
            // Kepemilikan diperiksa di sini, bukan sekadar `exists`: tanpa
            // klausa user_id, mengganti id di request cukup untuk memesan
            // servis atas nama kendaraan orang lain.
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')
                    ->where('user_id', $this->user()->getKey())
                    ->whereNull('deleted_at'),
            ],

            'service_package_id' => [
                'required',
                'integer',
                Rule::exists('service_packages', 'id')->where('is_active', true),
            ],

            'booking_date' => ['required', 'date_format:Y-m-d', new BookableDate($slots)],
            'booking_time' => ['required', 'string', new AvailableSlot($slots)],

            'odometer' => ['nullable', 'integer', 'between:0,999999'],
            'complaint' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vehicle_id.required' => 'Pilih kendaraan yang akan diservis.',
            'vehicle_id.exists' => 'Kendaraan yang dipilih tidak ada di daftar kendaraan Anda.',
            'service_package_id.required' => 'Pilih paket layanan.',
            'service_package_id.exists' => 'Paket layanan yang dipilih sudah tidak tersedia.',
            'booking_date.required' => 'Pilih tanggal servis.',
            'booking_date.date_format' => 'Tanggal yang dipilih tidak dikenali.',
            'booking_time.required' => 'Pilih jam servis.',
            'odometer.between' => 'Odometer di antara 0 dan 999.999 km.',
            'complaint.max' => 'Keluhan paling panjang 1.000 karakter.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'vehicle_id' => 'kendaraan',
            'service_package_id' => 'paket layanan',
            'booking_date' => 'tanggal servis',
            'booking_time' => 'jam servis',
            'odometer' => 'odometer',
            'complaint' => 'keluhan',
        ];
    }
}
