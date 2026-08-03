<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form kontak publik (docs/10 F1.6.3).
 *
 * Form ini terbuka untuk tamu, jadi hanya lima kolom di bawah yang boleh
 * datang dari pengirim. `is_read`, `read_by`, dan `ip_address` ditentukan
 * server dan sengaja tidak `fillable` di App\Models\ContactMessage.
 */
class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:150'],
            'phone' => ['nullable', 'string', new IndonesianPhone],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi agar kami tahu harus membalas kepada siapa.',
            'name.max' => 'Nama maksimal 120 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak dikenali. Contoh: nama@email.com.',
            'subject.required' => 'Subjek wajib diisi — sebutkan singkat keperluan Anda.',
            'subject.max' => 'Subjek maksimal 160 karakter.',
            'message.required' => 'Pesan wajib diisi.',
            'message.min' => 'Pesan terlalu singkat. Tuliskan minimal 10 karakter agar kami bisa membantu.',
            'message.max' => 'Pesan maksimal 2.000 karakter. Untuk keterangan panjang, silakan hubungi kami lewat WhatsApp.',
        ];
    }
}
