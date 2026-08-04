<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Dasar untuk sumber daya yang hanya boleh disentuh Super Admin.
 *
 * Keempat sumber daya konten A10 punya aturan yang persis sama (docs/07 §A10,
 * matriks docs/09 §9.3): Super Admin boleh, advisor ditolak, customer dan tamu
 * tidak berlaku. Menyalin empat berkas identik berarti empat tempat yang harus
 * diubah bersamaan bila aturannya bergeser — dan yang terlewat akan menjadi
 * lubang yang tidak kelihatan.
 *
 * Policy tetap ada meski rutenya sudah dijaga middleware `role:super_admin`.
 * docs/09 §9.3 menegaskan middleware TIDAK menggantikan Policy: bila kelak ada
 * jalur lain menuju sumber daya ini, penolakannya tetap terjadi.
 */
abstract class SuperAdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->superAdmin($user);
    }

    public function view(User $user): bool
    {
        return $this->superAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->superAdmin($user);
    }

    public function update(User $user): bool
    {
        return $this->superAdmin($user);
    }

    public function delete(User $user): bool
    {
        return $this->superAdmin($user);
    }

    /**
     * Akun nonaktif ditolak sekalipun perannya benar — akun yang dicabut
     * haknya tidak boleh tetap bisa mengubah isi situs.
     */
    protected function superAdmin(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin && $user->is_active;
    }
}
