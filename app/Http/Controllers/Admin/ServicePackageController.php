<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ServicePackageCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServicePackageRequest;
use App\Http\Requests\Admin\UpdateServicePackageRequest;
use App\Models\ServicePackage;
use App\Services\ServicePackageService;
use App\Support\SeriesCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A5 — Paket Layanan (docs/07-modul-admin.md §A5). Super Admin saja.
 *
 * Rutenya dijaga middleware `role:super_admin`; Gate::authorize di tiap method
 * adalah lapis keduanya (docs/09 §9.3).
 */
class ServicePackageController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', ServicePackage::class);

        $packages = ServicePackage::query()
            ->ordered()
            ->paginate(25)
            ->through(fn (ServicePackage $package): array => [
                'id' => $package->id,
                'code' => $package->code,
                'name' => $package->name,
                'category' => $package->category->value,
                'category_label' => $package->category->label(),
                'applicable_series' => $package->applicable_series ?? [],
                'estimated_duration_minutes' => $package->estimated_duration_minutes,
                'price' => $package->price,
                'is_free' => $package->is_free,
                'is_active' => $package->is_active,
                'sort_order' => $package->sort_order,
                // Dihitung server, dikirim sebagai boolean — UI tidak pernah
                // menyimpulkan sendiri (.claude/rules/20).
                'can_delete' => ! $package->isReferenced(),
            ]);

        return Inertia::render('admin/paket-layanan/index', [
            'packages' => $packages,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ServicePackage::class);

        return Inertia::render('admin/paket-layanan/create', $this->pilihan());
    }

    public function store(StoreServicePackageRequest $request, ServicePackageService $packages): RedirectResponse
    {
        $package = $packages->create($request->validated());

        return redirect()
            ->route('admin.service-packages.index')
            ->with('success', "Paket layanan \"{$package->name}\" berhasil ditambahkan.");
    }

    public function edit(ServicePackage $servicePackage): Response
    {
        Gate::authorize('update', $servicePackage);

        return Inertia::render('admin/paket-layanan/edit', [
            ...$this->pilihan(),
            'package' => $servicePackage,
        ]);
    }

    public function update(
        UpdateServicePackageRequest $request,
        ServicePackage $servicePackage,
        ServicePackageService $packages,
    ): RedirectResponse {
        $packages->update($servicePackage, $request->validated());

        return redirect()
            ->route('admin.service-packages.index')
            ->with('success', "Paket layanan \"{$servicePackage->name}\" berhasil diperbarui.");
    }

    public function destroy(ServicePackage $servicePackage, ServicePackageService $packages): RedirectResponse
    {
        Gate::authorize('delete', $servicePackage);

        $nama = $servicePackage->name;

        // Paket yang sudah dirujuk booking ditolak di dalam service dan
        // berujung toast penjelasan — lihat MasterDataInUseException.
        $packages->delete($servicePackage);

        return redirect()
            ->route('admin.service-packages.index')
            ->with('success', "Paket layanan \"{$nama}\" berhasil dihapus.");
    }

    /** @return array<string, mixed> */
    private function pilihan(): array
    {
        return [
            'categories' => ServicePackageCategory::options(),
            'seriesOptions' => SeriesCatalog::codes(),
        ];
    }
}
