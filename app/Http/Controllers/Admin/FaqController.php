<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Http\Requests\Admin\ReorderContentRequest;
use App\Models\Faq;
use App\Services\ContentOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A10 — FAQ (docs/07 §A10). Super Admin saja.
 *
 * Tidak memakai Service: menulis FAQ hanyalah menyimpan kolom hasil validasi,
 * tanpa aturan bisnis, berkas, maupun kekangan lintas tabel. Membungkusnya
 * dalam Service hanya menambah satu lapis yang tidak memutuskan apa pun.
 */
class FaqController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Faq::class);

        return Inertia::render('admin/konten/faq/index', [
            'faqs' => Faq::query()->ordered()->get()->map(fn (Faq $f): array => [
                'id' => $f->id,
                'question' => $f->question,
                'answer' => $f->answer,
                'category' => $f->category,
                'is_active' => $f->is_active,
            ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Faq::class);

        return Inertia::render('admin/konten/faq/form', ['faq' => null]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        Faq::create([
            ...$request->validated(),
            'sort_order' => (int) Faq::query()->max('sort_order') + 1,
        ]);

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'Pertanyaan berhasil ditambahkan.');
    }

    public function edit(Faq $faq): Response
    {
        Gate::authorize('update', $faq);

        return Inertia::render('admin/konten/faq/form', [
            'faq' => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'category' => $faq->category,
                'is_active' => $faq->is_active,
            ],
        ]);
    }

    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'Pertanyaan berhasil diperbarui.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        Gate::authorize('delete', $faq);

        $faq->delete();

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'Pertanyaan berhasil dihapus.');
    }

    public function reorder(ReorderContentRequest $request, ContentOrderService $order): RedirectResponse
    {
        $order->apply(Faq::class, $request->ids());

        return back()->with('success', 'Urutan pertanyaan berhasil disimpan.');
    }
}
