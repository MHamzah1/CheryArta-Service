<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderContentRequest;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Models\Testimonial;
use App\Services\ContentOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A10 — Testimoni (docs/07 §A10). Super Admin saja.
 *
 * Testimoni **diketik admin**, bukan dikirim pelanggan (keputusan grill #5):
 * tidak ada form publik yang mengirimnya, jadi tidak ada antrean moderasi.
 * `is_published` hanyalah saklar tampil atau sembunyi di landing page.
 */
class TestimonialController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Testimonial::class);

        return Inertia::render('admin/konten/testimoni/index', [
            'testimonials' => Testimonial::query()->ordered()->get()->map(fn (Testimonial $t): array => [
                'id' => $t->id,
                'customer_name' => $t->customer_name,
                'car_model' => $t->car_model,
                'rating' => $t->rating,
                'content' => $t->content,
                'is_published' => $t->is_published,
            ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Testimonial::class);

        return Inertia::render('admin/konten/testimoni/form', ['testimonial' => null]);
    }

    public function store(TestimonialRequest $request): RedirectResponse
    {
        $testimonial = Testimonial::create([
            ...$request->validated(),
            'sort_order' => (int) Testimonial::query()->max('sort_order') + 1,
        ]);

        return redirect()
            ->route('admin.testimonials.index')
            ->with('success', "Testimoni dari {$testimonial->customer_name} berhasil ditambahkan.");
    }

    public function edit(Testimonial $testimonial): Response
    {
        Gate::authorize('update', $testimonial);

        return Inertia::render('admin/konten/testimoni/form', [
            'testimonial' => [
                'id' => $testimonial->id,
                'customer_name' => $testimonial->customer_name,
                'car_model' => $testimonial->car_model,
                'rating' => $testimonial->rating,
                'content' => $testimonial->content,
                'is_published' => $testimonial->is_published,
            ],
        ]);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($request->validated());

        return redirect()
            ->route('admin.testimonials.index')
            ->with('success', 'Testimoni berhasil diperbarui.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        Gate::authorize('delete', $testimonial);

        $nama = $testimonial->customer_name;
        $testimonial->delete();

        return redirect()
            ->route('admin.testimonials.index')
            ->with('success', "Testimoni dari {$nama} berhasil dihapus.");
    }

    public function reorder(ReorderContentRequest $request, ContentOrderService $order): RedirectResponse
    {
        $order->apply(Testimonial::class, $request->ids());

        return back()->with('success', 'Urutan testimoni berhasil disimpan.');
    }
}
