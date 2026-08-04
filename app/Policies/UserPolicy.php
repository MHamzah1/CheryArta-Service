<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
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

    /*
    |--------------------------------------------------------------------------
    | A11 — akun STAF (docs/07 §A11, roadmap 2.2.3)
    |--------------------------------------------------------------------------
    |
    | Terpisah dari method di atas dengan sengaja. Yang di atas menuntut
    | sasarannya `customer`, yang di bawah menuntut sasarannya STAF. Memakai
    | nama method yang sama untuk keduanya akan membuat satu jalur menjadi
    | pintu belakang bagi yang lain — persis yang dijaga catatan di kepala
    | berkas ini.
    |
    */

    public function viewAnyStaff(User $user): bool
    {
        return $this->superAdminAktif($user);
    }

    public function createStaff(User $user): bool
    {
        return $this->superAdminAktif($user);
    }

    /**
     * Akun pelanggan tidak dikelola di A11 — pengelolaannya milik A6, yang
     * pengamannya berbeda. Sasaran `customer` ditolak di sini apa pun perannya
     * si pelaku (docs/07 §A11).
     */
    public function updateStaff(User $user, User $target): bool
    {
        return $this->superAdminAktif($user) && $target->isStaff();
    }

    public function resetStaffPassword(User $user, User $target): bool
    {
        return $this->updateStaff($user, $target);
    }

    /**
     * A12 Activity Log (docs/07 §A12). Isinya memuat nilai sebelum dan sesudah
     * dari seluruh modul, termasuk data pelanggan — jadi Super Admin saja.
     */
    public function viewActivityLog(User $user): bool
    {
        return $this->superAdminAktif($user);
    }

    /**
     * Aturan "tidak boleh menjatuhkan diri sendiri" dan "Super Admin aktif
     * terakhir" TIDAK ada di sini melainkan di UserService: keduanya menuntut
     * hitungan yang terkunci di dalam transaksi, dan Policy dipanggil di luar
     * transaksi apa pun.
     */
    private function superAdminAktif(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin && $user->is_active;
    }
}
