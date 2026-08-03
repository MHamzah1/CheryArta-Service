<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ServicePackage;

class StoreServicePackageRequest extends ServicePackageRequest
{
    protected function packageBeingEdited(): ?ServicePackage
    {
        return null;
    }
}
