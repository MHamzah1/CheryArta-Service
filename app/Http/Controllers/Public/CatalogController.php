<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CarCategory;
use App\Enums\FuelType;
use App\Http\Controllers\Controller;
use App\Models\CarModel;
use App\Models\CarModelVariant;
use App\Support\PublicContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Katalog publik (docs/06 §6.4). Sumber datanya A4 — apa yang disunting Super
 * Admin di `/admin/katalog` langsung tampil di sini.
 *
 * Model nonaktif TIDAK pernah bisa dibuka lewat URL: scope `active()` ikut ke
 * dalam pencarian slug, bukan diperiksa setelah barisnya terlanjur diambil.
 */
class CatalogController extends Controller
{
    private const PER_HALAMAN = 12;

    public function index(Request $request): Response
    {
        $kategori = $this->pilihan($request->query('kategori'), CarCategory::values());
        $bahanBakar = $this->pilihan($request->query('bahan_bakar'), FuelType::values());

        $carModels = CarModel::query()
            ->active()
            ->with('primaryImage')
            ->when($kategori !== null, fn ($query) => $query->where('category', $kategori))
            ->when($bahanBakar !== null, fn ($query) => $query->where('fuel_type', $bahanBakar))
            ->ordered()
            ->paginate(self::PER_HALAMAN)
            ->withQueryString()
            ->through(PublicContent::carModelCard(...));

        return Inertia::render('katalog/index', [
            'carModels' => $carModels,
            'categories' => CarCategory::options(),
            'fuelTypes' => FuelType::options(),
            'filters' => [
                'kategori' => $kategori,
                'bahan_bakar' => $bahanBakar,
            ],
        ]);
    }

    public function show(string $slug): Response
    {
        $carModel = CarModel::query()
            ->active()
            ->where('slug', $slug)
            ->with([
                'variants' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name'),
                'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            ])
            ->firstOrFail();

        return Inertia::render('katalog/show', [
            'carModel' => [
                'name' => $carModel->name,
                'slug' => $carModel->slug,
                'category_label' => $carModel->category->label(),
                'fuel_type_label' => $carModel->fuel_type->label(),
                'price_start' => $carModel->price_start,
                'short_description' => $carModel->short_description,
                'description' => $carModel->description,
                'specs' => $carModel->specs,
                'brochure_url' => $carModel->brochure_url,
            ],
            'images' => $carModel->images->map(PublicContent::carModelImage(...)),
            'variants' => $carModel->variants->map(fn (CarModelVariant $variant): array => [
                'id' => $variant->id,
                'name' => $variant->name,
                'price' => $variant->price,
                'specs' => $variant->specs,
            ]),
            // Paket yang berlaku untuk seri model ini — jembatan dari katalog
            // ke alur booking, bukan sekadar daftar harga.
            'servicePackages' => PublicContent::servicePackages(),
            'relatedModels' => $this->modelLain($carModel),
        ]);
    }

    /**
     * Nilai saringan yang sah, atau null. Nilai asing dibuang diam-diam
     * alih-alih menjadi galat: URL katalog sering disalin-tempel orang, dan
     * halaman katalog kosong dengan pesan galat bukan jawaban yang berguna.
     *
     * @param  list<string>  $sah
     */
    private function pilihan(mixed $nilai, array $sah): ?string
    {
        return is_string($nilai) && in_array($nilai, $sah, true) ? $nilai : null;
    }

    /** @return list<array<string, mixed>> */
    private function modelLain(CarModel $carModel): array
    {
        return CarModel::query()
            ->active()
            ->whereKeyNot($carModel->id)
            ->with('primaryImage')
            ->ordered()
            ->limit(3)
            ->get()
            ->map(PublicContent::carModelCard(...))
            ->all();
    }
}
