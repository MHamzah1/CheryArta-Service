<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\CarModel;

class StoreCarModelRequest extends CarModelRequest
{
    protected function carModelBeingEdited(): ?CarModel
    {
        return null;
    }
}
