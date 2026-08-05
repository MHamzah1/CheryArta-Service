<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\WhatsAppTemplate;
use App\Rules\KnownPlaceholders;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;

/**
 * Menyunting teks pesan WhatsApp (docs/07 §A7).
 *
 * Tidak ada `key` di sini. Himpunan kuncinya milik App\Enums\WhatsAppTemplateKey
 * dan hanya diisi seeder: layar ini menyunting isi, tidak pernah menambah
 * pemicu baru yang tidak akan pernah terpanggil kode (keputusan grill Q5).
 */
class WhatsAppTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('whatsapp_template');

        return $template instanceof WhatsAppTemplate
            && ($this->user()?->can('update', $template) ?? false);
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'body' => [
                'required',
                'string',
                'max:'.Config::integer('whatsapp.max_template_body_length'),
                new KnownPlaceholders,
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama template wajib diisi.',
            'name.max' => 'Nama template maksimal 100 karakter.',
            'body.required' => 'Isi pesan wajib diisi.',
            'is_active.required' => 'Tentukan apakah pesan ini ditawarkan atau tidak.',
            'body.max' => sprintf(
                'Isi pesan maksimal %d karakter. Batas ini lebih pendek daripada batas WhatsApp karena placeholder '
                .'akan memanjang saat dirender — tanpa ruang itu, penutup pesan terpotong tanpa peringatan.',
                Config::integer('whatsapp.max_template_body_length'),
            ),
        ];
    }
}
