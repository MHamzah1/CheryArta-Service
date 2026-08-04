<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Faq;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Pertanyaan yang tampil di halaman FAQ publik (docs/07 §A10).
 *
 * Jawaban disimpan sebagai teks biasa. Editor konten kaya sengaja tidak
 * dipakai: HTML dari admin tetap harus dibersihkan sebelum dirender, dan
 * `dangerouslySetInnerHTML` dilarang keras di proyek ini (temuan S5).
 */
class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        $faq = $this->route('faq');

        return $faq instanceof Faq
            ? ($this->user()?->can('update', $faq) ?? false)
            : ($this->user()?->can('create', Faq::class) ?? false);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:40'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'question.required' => 'Pertanyaan wajib diisi.',
            'question.max' => 'Pertanyaan maksimal 255 karakter.',
            'answer.required' => 'Jawaban wajib diisi.',
            'answer.max' => 'Jawaban maksimal 5000 karakter.',
            'category.max' => 'Kategori maksimal 40 karakter.',
        ];
    }
}
