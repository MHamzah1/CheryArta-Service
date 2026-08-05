<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactMessageFilterRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A10 — Pesan dari form kontak publik (docs/07 §A10). Super Admin saja.
 *
 * Sampai layar ini ada, pesan masuk ke `contact_messages` dan berhenti di
 * sana — halaman kontak karena itu menonjolkan tombol WhatsApp sebagai jalur
 * utama, dan formnya hanya jalur cadangan. Layar ini yang menutup lubang itu.
 *
 * **Hapus di sini menghapus permanen** (keputusan grill #4). Tabelnya tidak
 * memakai soft delete. Yang mengawal: `ConfirmDialog` di layar yang
 * menampilkan nama pengirim beserta cuplikan isinya sebelum tombol ditekan,
 * dan pencatatan ke activity log — isinya hilang, tetapi fakta siapa
 * menghapus pesan dari siapa tetap punya jejak.
 */
class ContactMessageController extends Controller
{
    public function index(ContactMessageFilterRequest $request): Response
    {
        Gate::authorize('viewAny', ContactMessage::class);

        $status = $request->status();

        $messages = ContactMessage::query()
            ->when($status === 'belum', fn ($q) => $q->where('is_read', false))
            ->when($status === 'sudah', fn ($q) => $q->where('is_read', true))
            ->with('readBy:id,name')
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (ContactMessage $m): array => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'phone' => $m->phone,
                'subject' => $m->subject,
                'message' => $m->message,
                'is_read' => $m->is_read,
                'read_at' => $m->read_at?->toIso8601String(),
                'read_by_name' => $m->readBy?->name,
                'created_at' => $m->created_at?->toIso8601String(),
                // Tautan disusun server lewat App\Support\WhatsAppLink: nomor
                // selalu bentuk ternormalisasi `62…`, tidak pernah input mentah
                // yang ditempel ke URL (.claude/rules/50 #7). `null` bila
                // pengirim tidak mencantumkan nomor — tombolnya tidak dirender.
                'whatsapp_url' => $m->whatsapp_reply_url,
            ]);

        return Inertia::render('admin/konten/pesan-masuk/index', [
            'messages' => $messages,
            'filters' => ['status' => $status],
        ]);
    }

    public function markRead(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        Gate::authorize('update', $contactMessage);

        // Ketiga kolom ini di luar $fillable dengan sengaja — ditentukan
        // server, tidak pernah datang dari request.
        $contactMessage->forceFill([
            'is_read' => true,
            'read_by' => $request->user()?->id,
            'read_at' => now(config('booking.timezone')),
        ])->save();

        return back();
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        Gate::authorize('delete', $contactMessage);

        $pengirim = $contactMessage->name;

        $contactMessage->delete();

        return redirect()
            ->route('admin.contact-messages.index')
            ->with('success', "Pesan dari {$pengirim} berhasil dihapus.");
    }
}
