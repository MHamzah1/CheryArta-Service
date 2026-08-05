<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WhatsAppTemplateRequest;
use App\Models\WhatsAppTemplate;
use App\Support\WhatsAppPlaceholders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A7 — Teks pesan WhatsApp (docs/07 §A7). Super Admin saja.
 *
 * **Sengaja bukan CRUD.** Tidak ada `create` maupun `destroy`: himpunan kunci
 * template milik App\Enums\WhatsAppTemplateKey karena setiap kunci punya
 * pemicunya sendiri di dalam kode. Template buatan admin tidak akan pernah
 * terpanggil — baris mati yang tampak seperti fitur — dan template yang dihapus
 * akan mematahkan jalur kirim di tengah advisor mengubah status
 * (keputusan grill Q5).
 *
 * Tidak memakai Service: menyimpan template hanyalah menulis kolom hasil
 * validasi. Aturan yang sesungguhnya — placeholder mana yang sah — hidup di
 * App\Rules\KnownPlaceholders, tempat ia bisa dipakai ulang.
 */
class WhatsAppTemplateController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', WhatsAppTemplate::class);

        return Inertia::render('admin/template-wa/index', [
            'templates' => WhatsAppTemplate::ordered()
                ->map(fn (WhatsAppTemplate $t): array => [
                    'id' => $t->id,
                    'key' => $t->key->value,
                    'name' => $t->name,
                    'trigger' => $t->key->trigger(),
                    'is_active' => $t->is_active,
                    'updated_at' => $t->updated_at?->toIso8601String(),
                ])
                ->all(),
        ]);
    }

    public function edit(WhatsAppTemplate $whatsappTemplate): Response
    {
        Gate::authorize('update', $whatsappTemplate);

        return Inertia::render('admin/template-wa/edit', [
            'template' => [
                'id' => $whatsappTemplate->id,
                'key' => $whatsappTemplate->key->value,
                'name' => $whatsappTemplate->name,
                'trigger' => $whatsappTemplate->key->trigger(),
                'body' => $whatsappTemplate->body,
                'is_active' => $whatsappTemplate->is_active,
            ],
            'placeholders' => WhatsAppPlaceholders::names(),
            // Nilai contoh untuk pratinjau. Sengaja karangan: penyunting
            // template tidak punya alasan menampilkan nama, plat, dan jadwal
            // pelanggan sungguhan (.claude/rules/50), dan pratinjau tidak boleh
            // kosong hanya karena database belum berisi booking.
            'sampleValues' => WhatsAppPlaceholders::sample(),
            'maxLength' => Config::integer('whatsapp.max_template_body_length'),
        ]);
    }

    public function update(WhatsAppTemplateRequest $request, WhatsAppTemplate $whatsappTemplate): RedirectResponse
    {
        $whatsappTemplate->update($request->validated());

        return redirect()
            ->route('admin.whatsapp-templates.index')
            ->with('success', "Template \"{$whatsappTemplate->name}\" berhasil diperbarui.");
    }
}
