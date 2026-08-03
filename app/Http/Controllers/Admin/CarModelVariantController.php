<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CarModelVariantRequest;
use App\Models\CarModel;
use App\Models\CarModelVariant;
use App\Services\CarModelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Varian model — tabel di dalam halaman ubah model (docs/07 §A4, roadmap 1.3.3).
 *
 * Semua aksinya kembali ke halaman yang sama (`back()`), sehingga penambahan
 * varian tidak melempar admin keluar dari pekerjaannya.
 */
class CarModelVariantController extends Controller
{
    public function store(CarModelVariantRequest $request, CarModel $carModel, CarModelService $carModels): RedirectResponse
    {
        $variant = $carModels->addVariant($carModel, $request->validated());

        return back()->with('success', "Varian \"{$variant->name}\" berhasil ditambahkan.");
    }

    public function update(
        CarModelVariantRequest $request,
        CarModel $carModel,
        CarModelVariant $variant,
        CarModelService $carModels,
    ): RedirectResponse {
        $carModels->updateVariant($variant, $request->validated());

        return back()->with('success', "Varian \"{$variant->name}\" berhasil diperbarui.");
    }

    public function destroy(CarModel $carModel, CarModelVariant $variant, CarModelService $carModels): RedirectResponse
    {
        Gate::authorize('update', $carModel);

        $nama = $variant->name;
        $carModels->deleteVariant($variant);

        return back()->with('success', "Varian \"{$nama}\" berhasil dihapus.");
    }
}
