<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Saringan daftar akun staf (docs/07 §A11).
 *
 * `customer` sengaja TIDAK ada di daftar peran yang boleh disaring: A11 hanya
 * menampilkan akun staf, dan menerima nilainya di sini akan membuat saringan
 * yang tidak pernah menghasilkan apa pun — atau lebih buruk, mengesankan
 * bahwa pelanggan bisa muncul di layar ini.
 */
class StaffUserFilterRequest extends FormRequest
{
    /** Otorisasinya `viewAnyStaff` di controller. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'role' => ['nullable', Rule::in([UserRole::SuperAdmin->value, UserRole::ServiceAdvisor->value])],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'role.in' => 'Saringan peran hanya menerima Super Admin atau Service Advisor.',
            'status.in' => 'Saringan status hanya menerima "aktif" atau "nonaktif".',
        ];
    }

    public function role(): ?string
    {
        $role = $this->validated('role');

        return is_string($role) && $role !== '' ? $role : null;
    }

    public function status(): ?string
    {
        $status = $this->validated('status');

        return is_string($status) && $status !== '' ? $status : null;
    }
}
