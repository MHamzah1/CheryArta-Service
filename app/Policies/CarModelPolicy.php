<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CarModel;
use App\Models\User;

/**
 * Katalog mobil adalah master data: hanya Super Admin yang boleh menyentuhnya
 * (docs/07-modul-admin.md §A14).
 *
 * Rute-nya sudah dijaga middleware `role:super_admin`, tetapi policy ini tetap
 * ada sebagai lapis kedua — docs/09 §9.3 menegaskan middleware TIDAK
 * menggantikan Policy. Bila kelak ada jalur lain menuju sumber daya ini
 * (perintah artisan, aksi tertanam di layar lain), penolakannya tetap terjadi.
 *
 * Aturan "sudah dirujuk ⇒ tidak bisa dihapus" TIDAK ada di sini melainkan di
 * CarModelService, agar berlaku juga untuk jalur yang tidak lewat controller.
 */
class CarModelPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->superAdmin($user);
    }

    public function view(User $user, CarModel $carModel): bool
    {
        return $this->superAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->superAdmin($user);
    }

    public function update(User $user, CarModel $carModel): bool
    {
        return $this->superAdmin($user);
    }

    public function delete(User $user, CarModel $carModel): bool
    {
        return $this->superAdmin($user);
    }

    private function superAdmin(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin && $user->is_active;
    }
}
