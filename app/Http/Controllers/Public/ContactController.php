<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman kontak + form pesan (docs/10 F1.6.3).
 *
 * CATATAN JUJUR: pesan yang masuk tersimpan di `contact_messages` tetapi belum
 * ada layar untuk membacanya sampai A10 dibangun di F2.4 — selama Big Fase 1
 * isinya hanya terlihat lewat DBeaver. Karena itu halaman ini menempatkan
 * tombol WhatsApp sebagai jalur utama dan form sebagai jalur cadangan
 * (docs/10 §Risiko).
 */
class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('kontak');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $message = new ContactMessage($request->validated());

        // Ditentukan server, bukan diterima dari form — kolomnya sengaja tidak
        // fillable (.claude/rules/50-keamanan.md).
        $message->ip_address = $request->ip();
        $message->save();

        return redirect()
            ->route('public.contact.show')
            ->with('success', 'Pesan Anda sudah kami terima. Untuk keperluan mendesak, silakan hubungi kami lewat WhatsApp.');
    }
}
