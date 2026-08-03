<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BookingSource;
use App\Enums\UserRole;
use App\Models\Vehicle;
use App\Rules\AvailableSlot;
use App\Rules\BookableDate;
use App\Rules\IndonesianPhone;
use App\Rules\ValidPlateSegment;
use App\Services\SlotService;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Booking walk-in yang diisikan advisor (docs/07 §A2, roadmap 1.5.4).
 *
 * Satu form, empat kemungkinan bentuk masukan: pelanggan lama atau baru,
 * dikali kendaraan lama atau baru. Aturannya ditulis berpasangan
 * (`required_without`) supaya keempatnya sah tanpa satu pun percabangan `if`
 * di controller.
 *
 * `status`, `source`, dan `booking_code` TIDAK ada di sini dan tidak boleh
 * pernah ada — ketiganya ditentukan server (.claude/rules/50-keamanan.md).
 */
class StoreWalkInBookingRequest extends FormRequest
{
    /** Otorisasinya `createWalkIn` di controller — lihat Admin\BookingController. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Nomor dinormalisasi SEBELUM divalidasi supaya aturan `unique` menilai
     * bentuk yang sama dengan yang akhirnya tersimpan — alasan yang sama
     * seperti RegisterRequest.
     */
    protected function prepareForValidation(): void
    {
        $phone = $this->input('customer.phone_wa');

        if (is_string($phone) && $phone !== '') {
            $this->merge([
                'customer' => [
                    ...(array) $this->input('customer', []),
                    'phone_wa' => PhoneNumber::normalize($phone),
                ],
            ]);
        }
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(SlotService $slots): array
    {
        return [
            // --- Pelanggan: yang sudah ada, atau yang dibuatkan sekarang ----
            // Hanya akun berrole customer. Tanpa klausa ini advisor bisa
            // memesankan servis atas nama akun staf, dan data itu ikut
            // tercampur ke daftar customer di A6.
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', UserRole::Customer->value)
                    ->whereNull('deleted_at'),
            ],

            'customer' => ['required_without:user_id', 'array'],
            'customer.name' => ['required_with:customer', 'string', 'max:120'],
            'customer.email' => ['required_with:customer', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            'customer.phone_wa' => ['required_with:customer', 'string', new IndonesianPhone, 'unique:users,phone_wa'],
            'customer.address' => ['nullable', 'string', 'max:255'],

            // --- Kendaraan: dipilih dari milik pelanggan, atau didaftarkan ---
            // Kepemilikannya diperiksa di withValidator(), bukan dengan
            // `exists` biasa: pemiliknya baru diketahui setelah `user_id`
            // dibaca, dan `exists` tanpa klausa pemilik berarti kendaraan
            // orang lain bisa dipesankan servis hanya dengan menebak id.
            'vehicle_id' => ['nullable', 'integer'],

            'vehicle' => ['required_without:vehicle_id', 'array'],
            'vehicle.car_model_id' => ['nullable', 'integer', 'exists:car_models,id'],

            // Wajib hanya bila kendaraannya memang sedang didaftarkan DAN
            // modelnya tidak dipilih dari katalog. `required_without` biasa
            // salah di sini: saat blok `vehicle` tidak dikirim sama sekali,
            // `vehicle.car_model_id` juga absen — dan aturannya justru ikut
            // menyala untuk pelanggan yang memilih kendaraan tersimpan.
            'vehicle.model_name_manual' => [
                'nullable',
                Rule::requiredIf(fn (): bool => is_array($this->input('vehicle'))
                    && blank($this->input('vehicle.car_model_id'))),
                'string',
                'max:120',
            ],
            'vehicle.plate_prefix' => ['required_with:vehicle', new ValidPlateSegment(ValidPlateSegment::PREFIX)],
            'vehicle.plate_number' => ['required_with:vehicle', new ValidPlateSegment(ValidPlateSegment::NUMBER)],
            'vehicle.plate_suffix' => ['required_with:vehicle', new ValidPlateSegment(ValidPlateSegment::SUFFIX)],
            'vehicle.year' => ['nullable', 'integer', 'min:1990', 'max:'.((int) date('Y') + 1)],
            'vehicle.color' => ['nullable', 'string', 'max:40'],

            // --- Jadwal -----------------------------------------------------
            'service_package_id' => [
                'required',
                'integer',
                Rule::exists('service_packages', 'id')->where('is_active', true),
            ],

            // Sumber walk-in yang membuat aturan H-1 dilewati — keputusannya
            // ada di BookingSource::bypassesLeadTime(), bukan di sini. Kuota
            // slot tetap ditegakkan lewat AvailableSlot.
            'booking_date' => ['required', 'date_format:Y-m-d', new BookableDate($slots, BookingSource::WalkIn)],
            'booking_time' => ['required', 'string', new AvailableSlot($slots)],

            'odometer' => ['nullable', 'integer', 'between:0,999999'],
            'complaint' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->periksaKepemilikanKendaraan($validator);
            $this->periksaPlatKembar($validator);
        });
    }

    /**
     * Kendaraan yang dipilih harus milik pelanggan yang dipilih.
     *
     * Pelanggan yang baru dibuatkan belum punya kendaraan sama sekali, jadi
     * `vehicle_id` untuknya selalu salah — pesannya menyebutkan itu, bukan
     * sekadar "tidak valid".
     */
    private function periksaKepemilikanKendaraan(Validator $validator): void
    {
        $vehicleId = $this->integer('vehicle_id');

        if ($vehicleId === 0) {
            return;
        }

        $userId = $this->integer('user_id');

        if ($userId === 0) {
            $validator->errors()->add(
                'vehicle_id',
                'Pelanggan baru belum punya kendaraan tersimpan. Isikan data kendaraannya.',
            );

            return;
        }

        $miliknya = Vehicle::query()
            ->whereKey($vehicleId)
            ->where('user_id', $userId)
            ->exists();

        if (! $miliknya) {
            $validator->errors()->add('vehicle_id', 'Kendaraan tersebut tidak terdaftar atas nama pelanggan ini.');
        }
    }

    /**
     * Plat kembar dalam satu akun ditolak — aturan yang sama dengan
     * VehicleRequest milik customer, dinilai terhadap bentuk gabungannya.
     */
    private function periksaPlatKembar(Validator $validator): void
    {
        $userId = $this->integer('user_id');

        if ($userId === 0 || ! is_array($this->input('vehicle'))) {
            return;
        }

        if ($validator->errors()->hasAny(['vehicle.plate_prefix', 'vehicle.plate_number', 'vehicle.plate_suffix'])) {
            return;
        }

        $plateFull = Vehicle::composePlate(
            (string) $this->input('vehicle.plate_prefix'),
            (string) $this->input('vehicle.plate_number'),
            (string) $this->input('vehicle.plate_suffix'),
        );

        $sudahAda = Vehicle::query()
            ->where('user_id', $userId)
            ->where('plate_full', $plateFull)
            ->exists();

        if ($sudahAda) {
            $validator->errors()->add(
                'vehicle.plate_number',
                "Kendaraan berplat {$plateFull} sudah terdaftar atas nama pelanggan ini — pilih saja dari daftarnya.",
            );
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'customer.required_without' => 'Pilih pelanggan yang sudah terdaftar, atau isikan data pelanggan baru.',
            'customer.name.required_with' => 'Nama pelanggan wajib diisi.',
            'customer.email.required_with' => 'Email pelanggan wajib diisi.',
            'customer.email.unique' => 'Email ini sudah terdaftar. Cari pelanggannya di kolom pencarian.',
            'customer.phone_wa.required_with' => 'Nomor WhatsApp pelanggan wajib diisi.',
            'customer.phone_wa.unique' => 'Nomor WhatsApp ini sudah terdaftar. Cari pelanggannya di kolom pencarian.',
            'user_id.exists' => 'Pelanggan yang dipilih tidak ditemukan.',
            'vehicle.required_without' => 'Pilih kendaraan, atau isikan data kendaraan baru.',
            'vehicle.model_name_manual.required' => 'Pilih model Chery, atau ketik sendiri nama kendaraannya.',
            'service_package_id.required' => 'Pilih paket layanan.',
            'service_package_id.exists' => 'Paket layanan yang dipilih sudah tidak tersedia.',
            'booking_date.required' => 'Pilih tanggal servis.',
            'booking_time.required' => 'Pilih jam servis.',
            'odometer.between' => 'Odometer di antara 0 dan 999.999 km.',
            'complaint.max' => 'Keluhan paling panjang 1.000 karakter.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'user_id' => 'pelanggan',
            'customer.name' => 'nama pelanggan',
            'customer.email' => 'email pelanggan',
            'customer.phone_wa' => 'nomor WhatsApp',
            'customer.address' => 'alamat',
            'vehicle_id' => 'kendaraan',
            'vehicle.car_model_id' => 'model Chery',
            'vehicle.model_name_manual' => 'nama kendaraan',
            'vehicle.plate_prefix' => 'kode wilayah',
            'vehicle.plate_number' => 'nomor plat',
            'vehicle.plate_suffix' => 'huruf akhir plat',
            'vehicle.year' => 'tahun',
            'vehicle.color' => 'warna',
            'service_package_id' => 'paket layanan',
            'booking_date' => 'tanggal servis',
            'booking_time' => 'jam servis',
            'odometer' => 'odometer',
            'complaint' => 'keluhan',
        ];
    }
}
