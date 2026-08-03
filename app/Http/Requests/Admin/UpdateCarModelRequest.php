<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\CarModel;

class UpdateCarModelRequest extends CarModelRequest
{
    protected function carModelBeingEdited(): ?CarModel
    {
        $carModel = $this->route('car_model');

        return $carModel instanceof CarModel ? $carModel : null;
    }
}
