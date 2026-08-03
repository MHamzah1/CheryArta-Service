<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Rules\IndonesianPhone;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Nomor dinormalisasi SEBELUM divalidasi supaya aturan `unique` menilai
     * bentuk yang sama dengan yang tersimpan. Tanpa ini `0812-3456-7890` dan
     * `081234567890` lolos sebagai dua akun berbeda.
     */
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone_wa');

        if (is_string($phone) && $phone !== '') {
            $this->merge(['phone_wa' => PhoneNumber::normalize($phone)]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Panjang mengikuti kolomnya (users.name varchar 120,
            // users.email varchar 150) — bukan 255 seperti bawaan starter kit,
            // yang membuat masukan panjang lolos validasi lalu ditolak MySQL.
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            'phone_wa' => ['required', 'string', new IndonesianPhone, 'unique:users,phone_wa'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 120 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak benar.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk atau gunakan email lain.',
            'phone_wa.required' => 'Nomor WhatsApp wajib diisi.',
            'phone_wa.unique' => 'Nomor WhatsApp ini sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'phone_wa' => 'nomor WhatsApp',
            'password' => 'kata sandi',
        ];
    }
}
