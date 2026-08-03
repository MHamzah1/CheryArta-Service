<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ServicePackage;
use App\Models\User;

/**
 * Paket layanan menentukan pilihan di form booking dan estimasi biaya invoice,
 * sehingga hanya Super Admin yang boleh mengubahnya (docs/07 §A5 & §A14).
 *
 * Alasan policy ini tetap ada meski rutenya sudah dijaga middleware: lihat
 * catatan di CarModelPolicy.
 */
class ServicePackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->superAdmin($user);
    }

    public function view(User $user, ServicePackage $servicePackage): bool
    {
        return $this->superAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->superAdmin($user);
    }

    public function update(User $user, ServicePackage $servicePackage): bool
    {
        return $this->superAdmin($user);
    }

    public function delete(User $user, ServicePackage $servicePackage): bool
    {
        return $this->superAdmin($user);
    }

    private function superAdmin(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin && $user->is_active;
    }
}
