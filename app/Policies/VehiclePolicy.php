<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

/**
 * Kendaraan hanya boleh disentuh pemiliknya.
 *
 * Controller tetap mengambil datanya lewat relasi
 * (`$user->vehicles()->findOrFail()`), sehingga baris milik orang lain tidak
 * pernah sampai ke policy ini. Policy-nya ada sebagai lapis kedua — bila kelak
 * ada jalur yang mengambil kendaraan secara global, penolakannya tetap terjadi
 * (.claude/rules/50-keamanan.md).
 */
class VehiclePolicy
{
    /**
     * Daftar seluruh unit terdaftar di panel admin (docs/07 §A6).
     *
     * Menjawab pertanyaan yang paling sering muncul di meja servis — "unit ini
     * terakhir servis kapan?" — sehingga seluruh staf membutuhkannya.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->isStaff() || $this->miliknya($user, $vehicle);
    }

    /** Menyunting kendaraan tetap milik pemiliknya; staf melihat, tidak mengubah. */
    public function update(User $user, Vehicle $vehicle): bool
    {
        return $this->miliknya($user, $vehicle);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $this->miliknya($user, $vehicle);
    }

    private function miliknya(User $user, Vehicle $vehicle): bool
    {
        return $vehicle->user_id === $user->id;
    }
}
