<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\CarModel;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCarModelImageRequest extends FormRequest
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
        // Gambar tanpa teks alt tidak boleh ada, termasuk lewat penyuntingan.
        return [
            'alt' => ['required', 'string', 'max:160'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'alt.required' => 'Teks alt wajib diisi agar gambar terbaca pembaca layar.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['alt' => 'teks alt'];
    }
}
