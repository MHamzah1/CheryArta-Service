<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CarCategory;
use App\Enums\FuelType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCarModelRequest;
use App\Http\Requests\Admin\UpdateCarModelRequest;
use App\Models\CarModel;
use App\Models\CarModelImage;
use App\Models\CarModelVariant;
use App\Models\Vehicle;
use App\Services\CarModelService;
use App\Support\SeriesCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A4 — Katalog Mobil (docs/07-modul-admin.md §A4). Super Admin saja.
 *
 * Varian dan galeri punya controller sendiri karena keduanya disunting dari
 * dalam halaman ini tanpa memuat ulang seluruh form model.
 */
class CarModelController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', CarModel::class);

        $carModels = CarModel::query()
            ->with('primaryImage')
            ->withCount([
                'variants',
                'images',
                // Kendaraan terhapus lunak ikut dihitung: barisnya masih
                // menunjuk ke model ini (lihat CarModel::isReferenced()).
                'vehicles' => $this->termasukTerhapus(...),
            ])
            ->ordered()
            ->paginate(25)
            ->through(fn (CarModel $carModel): array => [
                'id' => $carModel->id,
                'name' => $carModel->name,
                'slug' => $carModel->slug,
                'category_label' => $carModel->category->label(),
                'fuel_type_label' => $carModel->fuel_type->label(),
                'series_code' => $carModel->series_code,
                'price_start' => $carModel->price_start,
                'is_active' => $carModel->is_active,
                'sort_order' => $carModel->sort_order,
                'variants_count' => $carModel->variants_count,
                'images_count' => $carModel->images_count,
                'vehicles_count' => $carModel->vehicles_count,
                'thumbnail_url' => $carModel->primaryImage?->thumbnail_url,
                'thumbnail_alt' => $carModel->primaryImage?->alt,
                'can_delete' => $carModel->vehicles_count === 0,
            ]);

        return Inertia::render('admin/katalog/index', [
            'carModels' => $carModels,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', CarModel::class);

        return Inertia::render('admin/katalog/create', $this->pilihan());
    }

    public function store(StoreCarModelRequest $request, CarModelService $carModels): RedirectResponse
    {
        $carModel = $carModels->create($request->validated());

        // Sengaja ke halaman ubah, bukan ke daftar: varian dan galeri baru bisa
        // diisi setelah modelnya punya id.
        return redirect()
            ->route('admin.car-models.edit', $carModel)
            ->with('success', "Model \"{$carModel->name}\" berhasil dibuat. Lanjutkan dengan varian dan galerinya.");
    }

    public function edit(CarModel $carModel): Response
    {
        Gate::authorize('update', $carModel);

        $carModel->load([
            'variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
            'images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ]);

        return Inertia::render('admin/katalog/edit', [
            ...$this->pilihan(),
            'carModel' => [
                ...$carModel->only([
                    'id', 'name', 'slug', 'series_code', 'price_start',
                    'short_description', 'description', 'specs',
                    'brochure_url', 'is_active', 'sort_order',
                ]),
                'category' => $carModel->category->value,
                'fuel_type' => $carModel->fuel_type->value,
            ],
            'variants' => $carModel->variants->map($this->variantProps(...)),
            'images' => $carModel->images->map($this->imageProps(...)),
        ]);
    }

    public function update(
        UpdateCarModelRequest $request,
        CarModel $carModel,
        CarModelService $carModels,
    ): RedirectResponse {
        $carModels->update($carModel, $request->validated());

        return redirect()
            ->route('admin.car-models.edit', $carModel)
            ->with('success', "Model \"{$carModel->name}\" berhasil diperbarui.");
    }

    public function destroy(CarModel $carModel, CarModelService $carModels): RedirectResponse
    {
        Gate::authorize('delete', $carModel);

        $nama = $carModel->name;

        // Model yang sudah dipakai kendaraan pelanggan ditolak di dalam service
        // dan berujung toast penjelasan — lihat MasterDataInUseException.
        $carModels->delete($carModel);

        return redirect()
            ->route('admin.car-models.index')
            ->with('success', "Model \"{$nama}\" berhasil dihapus.");
    }

    public function destroyBrochure(CarModel $carModel, CarModelService $carModels): RedirectResponse
    {
        Gate::authorize('update', $carModel);

        $carModels->deleteBrochure($carModel);

        return back()->with('success', 'Brosur berhasil dihapus.');
    }

    /**
     * @param  Builder<Vehicle>  $query
     * @return Builder<Vehicle>
     */
    private function termasukTerhapus(Builder $query): Builder
    {
        return $query->withTrashed();
    }

    /** @return array<string, mixed> */
    private function variantProps(CarModelVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'name' => $variant->name,
            'price' => $variant->price,
            'specs' => $variant->specs,
            'is_active' => $variant->is_active,
            'sort_order' => $variant->sort_order,
        ];
    }

    /** @return array<string, mixed> */
    private function imageProps(CarModelImage $image): array
    {
        return [
            'id' => $image->id,
            // Galeri admin menampilkan versi kecil; URL penuh tidak pernah
            // dibutuhkan di sini dan hanya memperberat muatan halaman.
            'url' => $image->thumbnail_url,
            'alt' => $image->alt,
            'is_primary' => $image->is_primary,
            'sort_order' => $image->sort_order,
        ];
    }

    /** @return array<string, mixed> */
    private function pilihan(): array
    {
        return [
            'categories' => CarCategory::options(),
            'fuelTypes' => FuelType::options(),
            'seriesOptions' => SeriesCatalog::codes(),
        ];
    }
}
