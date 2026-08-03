<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Vehicle;
use App\Rules\ValidPlateSegment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dasar bersama untuk menambah dan menyunting kendaraan.
 *
 * `user_id` dan `plate_full` tidak pernah ada di antara aturannya: yang
 * pertama ditentukan lewat relasi pemilik, yang kedua disusun model.
 */
abstract class VehicleRequest extends FormRequest
{
    /** Kendaraan yang sedang disunting — dikecualikan dari cek keunikan. */
    abstract protected function vehicleBeingEdited(): ?Vehicle;

    /**
     * Dijalankan SEBELUM validasi, sehingga kendaraan milik orang lain ditolak
     * 403 tanpa pernah menyentuh aturan apa pun — termasuk pemeriksaan
     * keunikan plat, yang kalau tidak akan membocorkan plat mana yang sudah
     * terdaftar di akun orang lain.
     */
    public function authorize(): bool
    {
        $vehicle = $this->vehicleBeingEdited();

        return $vehicle === null || $this->user()->can('update', $vehicle);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'car_model_id' => ['nullable', 'integer', 'exists:car_models,id'],

            // Wajib hanya bila kendaraannya bukan Chery — pertanyaan terbuka
            // Q3 menetapkan kendaraan non-Chery boleh didaftarkan.
            'model_name_manual' => ['nullable', 'required_without:car_model_id', 'string', 'max:120'],

            'plate_prefix' => ['required', new ValidPlateSegment(ValidPlateSegment::PREFIX)],
            'plate_number' => ['required', new ValidPlateSegment(ValidPlateSegment::NUMBER)],
            'plate_suffix' => ['required', new ValidPlateSegment(ValidPlateSegment::SUFFIX)],

            'year' => ['nullable', 'integer', 'min:1990', 'max:'.(int) date('Y') + 1],
            'color' => ['nullable', 'string', 'max:40'],
            'vin' => ['nullable', 'string', 'max:25'],
            'is_primary' => ['boolean'],
        ];
    }

    /**
     * Keunikan plat diperiksa terhadap bentuk gabungannya, bukan per segmen —
     * dan hanya dalam lingkup kendaraan milik pengguna ini. Mobil bekas
     * berpindah tangan, jadi plat yang sama boleh dimiliki orang lain.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['plate_prefix', 'plate_number', 'plate_suffix'])) {
                return;
            }

            $plateFull = Vehicle::composePlate(
                (string) $this->string('plate_prefix'),
                (string) $this->string('plate_number'),
                (string) $this->string('plate_suffix'),
            );

            $sudahAda = $this->user()
                ->vehicles()
                ->where('plate_full', $plateFull)
                ->whereKeyNot($this->vehicleBeingEdited()?->getKey())
                ->exists();

            if ($sudahAda) {
                $validator->errors()->add(
                    'plate_number',
                    "Kendaraan berplat {$plateFull} sudah terdaftar di akun Anda.",
                );
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'model_name_manual.required_without' => 'Pilih model Chery, atau ketik sendiri nama kendaraan Anda.',
            'car_model_id.exists' => 'Model yang dipilih tidak ada di katalog.',
            'year.min' => 'Tahun kendaraan mulai dari 1990.',
            'year.max' => 'Tahun kendaraan tidak boleh melebihi tahun depan.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'car_model_id' => 'model Chery',
            'model_name_manual' => 'nama kendaraan',
            'plate_prefix' => 'kode wilayah',
            'plate_number' => 'nomor plat',
            'plate_suffix' => 'huruf akhir plat',
            'year' => 'tahun',
            'color' => 'warna',
            'vin' => 'nomor rangka',
        ];
    }
}
