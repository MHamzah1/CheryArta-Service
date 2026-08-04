<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FacilityRequest;
use App\Http\Requests\Admin\ReorderContentRequest;
use App\Models\Facility;
use App\Services\ContentOrderService;
use App\Services\FacilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A10 — Fasilitas landing page (docs/07 §A10). Super Admin saja.
 *
 * Tabelnya sudah terisi sejak Tahap 3 lewat FacilitySeeder (**R4**), jadi
 * layar ini menyalakan pengelolaannya tanpa migrasi data apa pun.
 */
class FacilityController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Facility::class);

        return Inertia::render('admin/konten/fasilitas/index', [
            // Tanpa paginasi: jumlahnya ditetapkan delapan di docs/07 §A10 dan
            // urutannya diatur dengan menyeret — memaginasi daftar yang bisa
            // diseret berarti baris tidak bisa dipindah lintas halaman.
            'facilities' => Facility::query()->ordered()->get()->map(fn (Facility $f): array => [
                'id' => $f->id,
                'title' => $f->title,
                'description' => $f->description,
                'image_url' => $f->image_url,
                'is_active' => $f->is_active,
            ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Facility::class);

        return Inertia::render('admin/konten/fasilitas/form', ['facility' => null]);
    }

    public function store(FacilityRequest $request, FacilityService $facilities): RedirectResponse
    {
        $facility = $facilities->create($request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.facilities.index')
            ->with('success', "Fasilitas \"{$facility->title}\" berhasil ditambahkan.");
    }

    public function edit(Facility $facility): Response
    {
        Gate::authorize('update', $facility);

        return Inertia::render('admin/konten/fasilitas/form', [
            'facility' => [
                'id' => $facility->id,
                'title' => $facility->title,
                'description' => $facility->description,
                'image_url' => $facility->image_url,
                'is_active' => $facility->is_active,
            ],
        ]);
    }

    public function update(FacilityRequest $request, Facility $facility, FacilityService $facilities): RedirectResponse
    {
        $facilities->update($facility, $request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.facilities.index')
            ->with('success', "Fasilitas \"{$facility->title}\" berhasil diperbarui.");
    }

    public function destroy(Facility $facility, FacilityService $facilities): RedirectResponse
    {
        Gate::authorize('delete', $facility);

        $nama = $facility->title;
        $facilities->delete($facility);

        return redirect()
            ->route('admin.facilities.index')
            ->with('success', "Fasilitas \"{$nama}\" berhasil dihapus.");
    }

    public function reorder(ReorderContentRequest $request, ContentOrderService $order): RedirectResponse
    {
        $order->apply(Facility::class, $request->ids());

        return back()->with('success', 'Urutan fasilitas berhasil disimpan.');
    }
}
