<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ServicePackageCategory;
use App\Models\ServicePackage;
use App\Support\SeriesCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dasar bersama untuk menambah dan menyunting paket layanan (docs/07 §A5).
 *
 * Batas 15–480 menit dan kategori yang sah datang dari sini, bukan dari
 * React: validasi di browser hanya kenyamanan (temuan B3 sistem lama).
 */
abstract class ServicePackageRequest extends FormRequest
{
    /** Paket yang sedang disunting — dikecualikan dari cek keunikan kode. */
    abstract protected function packageBeingEdited(): ?ServicePackage;

    public function authorize(): bool
    {
        $package = $this->packageBeingEdited();

        return $package === null
            ? $this->user()->can('create', ServicePackage::class)
            : $this->user()->can('update', $package);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Kode lama sistem lama (`Tiggo_8_Free`) memakai huruf besar dan
            // garis bawah, jadi polanya tidak boleh dipersempit ke huruf kecil.
            'code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('service_packages', 'code')->ignore($this->packageBeingEdited()),
            ],

            'name' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::in(ServicePackageCategory::values())],
            'description' => ['nullable', 'string', 'max:2000'],

            'applicable_series' => ['nullable', 'array'],
            'applicable_series.*' => ['string', 'distinct', Rule::in(SeriesCatalog::codes())],

            'estimated_duration_minutes' => ['required', 'integer', 'between:15,480'],

            // Harga diabaikan bila paketnya gratis — penormalannya di
            // ServicePackageService, bukan di sini.
            'price' => ['nullable', 'required_if:is_free,false', 'numeric', 'min:0', 'max:999999999.99'],

            'is_free' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'between:0,65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, dan garis bawah — tanpa spasi.',
            'code.unique' => 'Kode ini sudah dipakai paket layanan lain.',
            'category.in' => 'Kategori yang dipilih tidak dikenal.',
            'applicable_series.*.in' => 'Seri yang dipilih tidak ada di katalog mobil.',
            'applicable_series.*.distinct' => 'Ada seri yang terpilih lebih dari sekali.',
            'estimated_duration_minutes.between' => 'Perkiraan durasi antara 15 dan 480 menit.',
            'price.required_if' => 'Isi harga paket, atau tandai paket ini sebagai gratis.',
            'price.min' => 'Harga tidak boleh minus.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'kode paket',
            'name' => 'nama paket',
            'category' => 'kategori',
            'description' => 'deskripsi',
            'applicable_series' => 'seri kendaraan',
            'estimated_duration_minutes' => 'perkiraan durasi',
            'price' => 'harga',
            'is_free' => 'status gratis',
            'is_active' => 'status aktif',
            'sort_order' => 'urutan tampil',
        ];
    }
}
