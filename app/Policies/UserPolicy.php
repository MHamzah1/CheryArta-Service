<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Data customer di panel admin (docs/07 §A6, matriks §A14).
 *
 * Pembagiannya: **melihat** boleh oleh seluruh staf, **mengubah keadaan akun**
 * hanya Super Admin. Menonaktifkan akun dan mereset password adalah tindakan
 * yang mengunci pelanggan di luar sistemnya sendiri — advisor yang sedang
 * kesal pada satu pelanggan tidak boleh bisa melakukannya sendirian.
 *
 * Seluruh method memeriksa bahwa sasarannya memang customer. Akun staf
 * dikelola di A11 (`/admin/users`, Big Fase 2) dengan pengaman berbeda:
 * Super Admin terakhir tidak boleh dinonaktifkan, dan tidak boleh menurunkan
 * role dirinya sendiri. Sampai layar itu ada, jalur ini tidak boleh menjadi
 * pintu belakang untuk menonaktifkan sesama staf.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isStaff() && $target->isCustomer();
    }

    /** Nonaktifkan / aktifkan kembali akun customer — Super Admin saja. */
    public function toggleActive(User $user, User $target): bool
    {
        return $user->isSuperAdmin() && $target->isCustomer();
    }

    /**
     * Reset password customer — Super Admin saja.
     *
     * Tanpa notifikasi email (keputusan #4), pelanggan yang lupa password
     * menghubungi bengkel dan Super Admin mereset dari sini, lalu menyampaikan
     * password sementaranya lewat WhatsApp (docs/09 §9.2).
     */
    public function resetPassword(User $user, User $target): bool
    {
        return $user->isSuperAdmin() && $target->isCustomer();
    }
}
