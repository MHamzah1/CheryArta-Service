<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AssetType;
use App\Enums\CarCategory;
use App\Enums\FuelType;
use App\Models\CarModel;
use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dasar bersama untuk menambah dan menyunting model katalog (docs/07 §A4).
 *
 * `slug` boleh dikosongkan — CarModelService yang menurunkannya dari nama.
 * Yang tidak boleh adalah slug ketikan yang bertabrakan, karena URL katalog
 * publik memakainya.
 */
abstract class CarModelRequest extends FormRequest
{
    /** Model yang sedang disunting — dikecualikan dari cek keunikan slug. */
    abstract protected function carModelBeingEdited(): ?CarModel;

    public function authorize(): bool
    {
        $carModel = $this->carModelBeingEdited();

        return $carModel === null
            ? $this->user()->can('create', CarModel::class)
            : $this->user()->can('update', $carModel);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],

            'slug' => [
                'nullable',
                'string',
                'max:140',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('car_models', 'slug')->ignore($this->carModelBeingEdited()),
            ],

            'category' => ['required', Rule::in(CarCategory::values())],
            'fuel_type' => ['required', Rule::in(FuelType::values())],

            // Menentukan paket Free Maintenance mana yang relevan untuk model
            // ini (docs/07 §A4), karena itu bentuknya harus sama dengan kode
            // seri di service_packages.applicable_series.
            'series_code' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],

            // Null dibaca katalog sebagai "hubungi kami", bukan gratis.
            'price_start' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],

            'short_description' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],

            // Pasangan kunci–nilai, bukan objek: urutannya perlu terjaga dan
            // kunci kembar harus bisa ditolak. Penyimpanannya jadi objek
            // dikerjakan CarModelService.
            'specs' => ['nullable', 'array', 'max:30'],
            'specs.*.key' => ['required', 'string', 'max:60', 'distinct:ignore_case'],
            'specs.*.value' => ['required', 'string', 'max:160'],

            'brochure' => ['nullable', ...UploadRules::for(AssetType::Document)],

            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'between:0,65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung. Contoh: tiggo-8-pro.',
            'slug.unique' => 'Slug ini sudah dipakai model lain.',
            'series_code.regex' => 'Kode seri hanya boleh berisi huruf kecil, angka, dan garis bawah. Contoh: tiggo_8.',
            'category.in' => 'Kategori yang dipilih tidak dikenal.',
            'fuel_type.in' => 'Jenis bahan bakar yang dipilih tidak dikenal.',
            'specs.max' => 'Spesifikasi paling banyak 30 baris.',
            'specs.*.key.required' => 'Isi nama spesifikasi, atau hapus barisnya.',
            'specs.*.key.distinct' => 'Nama spesifikasi ini ditulis lebih dari sekali.',
            'specs.*.value.required' => 'Isi nilai spesifikasi, atau hapus barisnya.',
            'brochure.mimes' => 'Brosur harus berkas PDF.',
            'brochure.max' => 'Ukuran brosur paling besar 5 MB.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama model',
            'slug' => 'slug',
            'category' => 'kategori',
            'fuel_type' => 'bahan bakar',
            'series_code' => 'kode seri',
            'price_start' => 'harga mulai',
            'short_description' => 'deskripsi singkat',
            'description' => 'deskripsi',
            'brochure' => 'brosur',
            'is_active' => 'status aktif',
            'sort_order' => 'urutan tampil',
        ];
    }
}
