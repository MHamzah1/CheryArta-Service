<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCarModelImageRequest;
use App\Http\Requests\Admin\StoreCarModelImageRequest;
use App\Http\Requests\Admin\UpdateCarModelImageRequest;
use App\Models\CarModel;
use App\Models\CarModelImage;
use App\Services\CarModelGalleryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Galeri katalog lewat Cloudinary (docs/07 §A4, roadmap 1.3.4).
 *
 * Controller tidak pernah menyentuh SDK penyedia — semuanya lewat
 * CarModelGalleryService → ImageUploader (docs/12 §12.5).
 */
class CarModelImageController extends Controller
{
    public function store(
        StoreCarModelImageRequest $request,
        CarModel $carModel,
        CarModelGalleryService $gallery,
    ): RedirectResponse {
        /** @var list<\Illuminate\Http\UploadedFile> $files */
        $files = array_values($request->file('images', []));
        /** @var list<string> $alts */
        $alts = array_values($request->validated('alts', []));

        $jumlah = $gallery->addImages($carModel, $files, $alts);

        return back()->with('success', "{$jumlah} gambar berhasil diunggah.");
    }

    public function update(
        UpdateCarModelImageRequest $request,
        CarModel $carModel,
        CarModelImage $image,
        CarModelGalleryService $gallery,
    ): RedirectResponse {
        $gallery->updateAlt($image, (string) $request->validated('alt'));

        return back()->with('success', 'Teks alt gambar berhasil diperbarui.');
    }

    public function primary(CarModel $carModel, CarModelImage $image, CarModelGalleryService $gallery): RedirectResponse
    {
        Gate::authorize('update', $carModel);

        $gallery->markPrimary($image);

        return back()->with('success', 'Gambar utama berhasil diganti.');
    }

    public function reorder(
        ReorderCarModelImageRequest $request,
        CarModel $carModel,
        CarModelGalleryService $gallery,
    ): RedirectResponse {
        /** @var list<int> $ids */
        $ids = array_values($request->validated('ids', []));

        $gallery->reorder($carModel, $ids);

        return back()->with('success', 'Urutan gambar berhasil disimpan.');
    }

    public function destroy(CarModel $carModel, CarModelImage $image, CarModelGalleryService $gallery): RedirectResponse
    {
        Gate::authorize('update', $carModel);

        $gallery->remove($image);

        return back()->with('success', 'Gambar berhasil dihapus.');
    }
}
