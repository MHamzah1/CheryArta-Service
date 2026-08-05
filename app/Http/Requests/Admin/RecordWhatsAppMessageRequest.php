<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\WhatsAppTemplateKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mencatat bahwa advisor membuka WhatsApp untuk sebuah booking (docs/08 §8.2).
 *
 * **Hanya `template_key`.** Isi pesannya TIDAK ikut dikirim klien: server
 * menyusunnya ulang dari template yang berlaku saat itu. Menerima teks dari
 * request akan menjadikan `whatsapp_messages.rendered_message` — kolom audit —
 * isian pengguna, dan pada saat itu ia berhenti membuktikan apa pun
 * (.claude/rules/50, keputusan grill Q2).
 */
class RecordWhatsAppMessageRequest extends FormRequest
{
    /** Otorisasinya ada di controller (`view` pada booking-nya). */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'template_key' => ['required', Rule::enum(WhatsAppTemplateKey::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'template_key.required' => 'Jenis pesan tidak dikenali. Muat ulang halaman lalu coba lagi.',
        ];
    }

    public function templateKey(): WhatsAppTemplateKey
    {
        return WhatsAppTemplateKey::from((string) $this->validated('template_key'));
    }
}
