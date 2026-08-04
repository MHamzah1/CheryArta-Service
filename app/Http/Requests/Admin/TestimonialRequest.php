<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Testimonial;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Testimoni pelanggan di landing page (docs/07 §A10).
 *
 * Diketik admin, bukan dikirim pelanggan (keputusan grill #5). Tidak ada form
 * publik dan tidak ada antrean moderasi — `is_published` hanyalah saklar
 * tampil atau sembunyi.
 */
class TestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $testimonial = $this->route('testimonial');

        return $testimonial instanceof Testimonial
            ? ($this->user()?->can('update', $testimonial) ?? false)
            : ($this->user()?->can('create', Testimonial::class) ?? false);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'car_model' => ['nullable', 'string', 'max:120'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'content' => ['required', 'string', 'max:2000'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Nama pelanggan wajib diisi.',
            'customer_name.max' => 'Nama pelanggan maksimal 120 karakter.',
            'rating.required' => 'Rating wajib dipilih.',
            'rating.min' => 'Rating paling rendah 1 bintang.',
            'rating.max' => 'Rating paling tinggi 5 bintang.',
            'content.required' => 'Isi testimoni wajib diisi.',
            'content.max' => 'Isi testimoni maksimal 2000 karakter.',
        ];
    }
}
