<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Akun staf internal (docs/07 §A11). Super Admin saja.
 *
 * **Password tidak ada di sini dengan sengaja.** Admin tidak pernah
 * mengetikkannya; server yang membuatnya acak dan menandai
 * `must_reset_password` (docs/07 §A11). Menerimanya dari form berarti Super
 * Admin mengetahui password orang lain secara permanen.
 *
 * `role` dibatasi dua nilai staf — `customer` ditolak di sini, dan sasaran
 * ber-role `customer` ditolak lagi oleh UserPolicy. Dua lapis karena keduanya
 * menjaga hal berbeda: yang ini menjaga nilai masukan, yang itu menjaga baris
 * sasaran.
 */
class StaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            ? ($this->user()?->can('updateStaff', $target) ?? false)
            : ($this->user()?->can('createStaff', User::class) ?? false);
    }

    /**
     * Nomor dinormalisasi SEBELUM divalidasi, supaya aturan `unique` menilai
     * bentuk yang benar-benar tersimpan (`62…`) — bukan apa pun yang diketik.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone_wa')) {
            $this->merge(['phone_wa' => PhoneNumber::normalize($this->string('phone_wa')->toString())]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $target = $this->route('user');
        $id = $target instanceof User ? $target->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'phone_wa' => ['required', 'string', 'max:20', Rule::unique('users', 'phone_wa')->ignore($id)],
            'role' => ['required', Rule::in([UserRole::SuperAdmin->value, UserRole::ServiceAdvisor->value])],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak sesuai.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'phone_wa.required' => 'Nomor WhatsApp wajib diisi.',
            'phone_wa.unique' => 'Nomor WhatsApp ini sudah dipakai akun lain.',
            'role.required' => 'Peran wajib dipilih.',
            'role.in' => 'Peran hanya boleh Super Admin atau Service Advisor. Akun pelanggan dikelola di menu Customer.',
        ];
    }
}
