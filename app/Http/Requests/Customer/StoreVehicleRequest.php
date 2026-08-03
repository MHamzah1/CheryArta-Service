<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Vehicle;

class StoreVehicleRequest extends VehicleRequest
{
    protected function vehicleBeingEdited(): ?Vehicle
    {
        return null;
    }
}
