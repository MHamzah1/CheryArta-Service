<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pencarian pelanggan di form booking walk-in (roadmap 1.5.4).
 *
 * Form walk-in memuat ulang dirinya sendiri lewat kunjungan Inertia parsial
 * saat advisor mengetik nama pelanggan, jadi kedua parameter ini datang dari
 * query string — dan karena itu divalidasi seperti masukan lain, bukan dibaca
 * mentah dengan `$request->input()` (.claude/rules/10).
 */
class WalkInLookupRequest extends FormRequest
{
    /** Otorisasinya `createWalkIn` di controller — lihat Admin\BookingController. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'cari' => ['nullable', 'string', 'max:100'],

            // Hanya akun customer. Tanpa klausa role, id akun staf yang
            // ditebak akan membuka nama dan nomor WhatsApp rekan kerja di
            // panel — kebocoran kecil yang mudah terlewat.
            'pelanggan' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', UserRole::Customer->value)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    /** Kata kunci pencarian, atau null bila kolomnya kosong. */
    public function keyword(): ?string
    {
        $kata = trim((string) $this->validated('cari', ''));

        return $kata === '' ? null : $kata;
    }

    public function customerId(): ?int
    {
        $id = $this->validated('pelanggan');

        return $id === null ? null : (int) $id;
    }
}
