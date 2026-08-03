<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\CarModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Urutan galeri hasil seret.
 *
 * Aturan `exists` dibatasi ke `car_model_id` model yang sedang dibuka: tanpa
 * itu, mengganti id di dalam permintaan bisa mengubah urutan galeri model lain
 * (.claude/rules/50-keamanan.md — "apakah pengguna bisa mengganti ID di URL
 * untuk menyentuh data orang lain?").
 */
class ReorderCarModelImageRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('car_model_images', 'id')
                    ->where('car_model_id', $carModel instanceof CarModel ? $carModel->getKey() : null),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'ids.*.exists' => 'Ada gambar yang bukan milik model ini.',
            'ids.*.distinct' => 'Ada gambar yang tercantum lebih dari sekali.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['ids' => 'urutan gambar'];
    }
}
