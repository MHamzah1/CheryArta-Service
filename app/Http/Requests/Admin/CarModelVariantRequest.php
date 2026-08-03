<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\CarModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Varian disunting dari dalam halaman modelnya (docs/07 §A4), sehingga hak
 * aksesnya mengikuti model induk — tidak ada policy tersendiri.
 *
 * Varian milik model lain tidak pernah sampai ke sini: rutenya memakai
 * scopeBindings(), jadi id yang bukan milik `{car_model}` berujung 404.
 */
class CarModelVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $carModel = $this->route('car_model');

        return $carModel instanceof CarModel && $this->user()->can('update', $carModel);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $carModel = $this->route('car_model');

        return [
            // Nama varian unik dalam satu model — "Premium" dua kali membuat
            // pilihan di form booking mustahil dibedakan.
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('car_model_variants', 'name')
                    ->where('car_model_id', $carModel instanceof CarModel ? $carModel->getKey() : null)
                    ->ignore($this->route('variant')),
            ],

            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],

            // Hanya selisih spesifikasi terhadap model dasar (docs/04 §4.2).
            'specs' => ['nullable', 'array', 'max:20'],
            'specs.*.key' => ['required', 'string', 'max:60', 'distinct:ignore_case'],
            'specs.*.value' => ['required', 'string', 'max:160'],

            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'between:0,65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.unique' => 'Model ini sudah punya varian dengan nama tersebut.',
            'specs.*.key.required' => 'Isi nama spesifikasi, atau hapus barisnya.',
            'specs.*.key.distinct' => 'Nama spesifikasi ini ditulis lebih dari sekali.',
            'specs.*.value.required' => 'Isi nilai spesifikasi, atau hapus barisnya.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama varian',
            'price' => 'harga',
            'is_active' => 'status aktif',
            'sort_order' => 'urutan tampil',
        ];
    }
}
