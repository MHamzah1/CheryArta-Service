<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

/**
 * Aturan seputar kendaraan milik customer.
 *
 * Service tidak menyentuh request(), session(), atau auth() — semuanya
 * diterima lewat parameter (.claude/rules/10-backend-laravel.md).
 */
class VehicleService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $owner, array $data): Vehicle
    {
        return DB::transaction(function () use ($owner, $data): Vehicle {
            // Kendaraan pertama otomatis jadi kendaraan utama — tanpa ini
            // customer yang hanya punya satu mobil tetap harus menandainya
            // sendiri sebelum bisa memesan servis.
            $data['is_primary'] = $owner->vehicles()->exists()
                ? (bool) ($data['is_primary'] ?? false)
                : true;

            $vehicle = $owner->vehicles()->create($data);

            if ($vehicle->is_primary) {
                $this->turunkanUtamaLain($owner, $vehicle);
            }

            return $vehicle;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $data): Vehicle {
            $vehicle->update($data);

            if ($vehicle->is_primary) {
                $this->turunkanUtamaLain($vehicle->user, $vehicle);
            }

            return $vehicle;
        });
    }

    public function delete(Vehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle): void {
            $owner = $vehicle->user;
            $tadinyaUtama = $vehicle->is_primary;

            $vehicle->delete();

            // Jangan tinggalkan pemilik tanpa kendaraan utama sama sekali.
            if ($tadinyaUtama) {
                $owner->vehicles()->oldest('id')->first()?->update(['is_primary' => true]);
            }
        });
    }

    /**
     * Hanya boleh ada satu kendaraan utama per pemilik. Ditegakkan di sini,
     * bukan lewat unique index: MySQL tidak bisa menyatakan "satu true per
     * user_id" tanpa kolom bantu.
     */
    private function turunkanUtamaLain(User $owner, Vehicle $kecuali): void
    {
        $owner->vehicles()
            ->whereKeyNot($kecuali->getKey())
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
}
