<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ServicePackage;

class UpdateServicePackageRequest extends ServicePackageRequest
{
    protected function packageBeingEdited(): ?ServicePackage
    {
        $package = $this->route('service_package');

        return $package instanceof ServicePackage ? $package : null;
    }
}
