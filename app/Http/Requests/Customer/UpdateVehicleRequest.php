<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Vehicle;

class UpdateVehicleRequest extends VehicleRequest
{
    /**
     * Diambil dari route binding. Controller sudah membatasinya pada
     * kendaraan milik pengguna yang sedang masuk, jadi nilai ini tidak pernah
     * merujuk kendaraan orang lain.
     */
    protected function vehicleBeingEdited(): ?Vehicle
    {
        $vehicle = $this->route('vehicle');

        return $vehicle instanceof Vehicle ? $vehicle : null;
    }
}
