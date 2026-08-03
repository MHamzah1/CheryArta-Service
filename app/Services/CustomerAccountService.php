<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Tindakan Super Admin atas akun pelanggan (docs/07 §A6, §A14).
 *
 * Keduanya sengaja tidak berupa `$user->update()` di controller: masing-masing
 * punya aturan yang harus berlaku di mana pun ia dipanggil, dan A11
 * (`/admin/users`, Big Fase 2) akan memanggilnya lagi.
 *
 * Service tidak menyentuh request(), session(), atau auth()
 * (.claude/rules/10-backend-laravel.md).
 */
class CustomerAccountService
{
    /**
     * Panjang password sementara. Cukup panjang untuk tidak bisa ditebak,
     * cukup pendek untuk dibacakan lewat telepon.
     */
    private const PANJANG_PASSWORD_SEMENTARA = 10;

    /**
     * Menonaktifkan atau mengaktifkan kembali akun.
     *
     * Akun nonaktif ditolak saat login (docs/09 §9.2) tetapi datanya tetap
     * utuh — booking dan riwayat servisnya masih dibutuhkan untuk garansi
     * kendaraan, jadi tidak ada jalur hapus permanen di sini sama sekali
     * (docs/07 §A6).
     *
     * @return bool Keadaan aktif setelah diubah.
     */
    public function toggleActive(User $customer): bool
    {
        $customer->forceFill(['is_active' => ! $customer->is_active])->save();

        return $customer->is_active;
    }

    /**
     * Mengatur ulang password menjadi satu password sementara.
     *
     * Tanpa notifikasi email (keputusan #4), password sementaranya
     * dikembalikan ke pemanggil untuk disampaikan Super Admin lewat WhatsApp.
     * Ia TIDAK disimpan di mana pun dalam bentuk terbaca, dan `must_reset_password`
     * menandai akunnya sebagai yang passwordnya harus diganti pemiliknya.
     *
     * @return string Password sementara — hanya ada di memori, tampil sekali.
     */
    public function resetPassword(User $customer): string
    {
        $sementara = Str::password(self::PANJANG_PASSWORD_SEMENTARA, symbols: false);

        // `password` punya cast `hashed`, jadi yang tersimpan adalah hash-nya.
        $customer->forceFill([
            'password' => $sementara,
            'must_reset_password' => true,
            'remember_token' => null,
        ])->save();

        return $sementara;
    }
}
