<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordWhatsAppMessageRequest;
use App\Models\Booking;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * A7 — Jejak pengiriman klik-to-chat (docs/08 §8.2).
 *
 * Otorisasinya `view` pada booking-nya, bukan Policy tersendiri: siapa pun yang
 * boleh membuka sebuah booking boleh mengabari pelanggannya (matriks
 * docs/09 §9.3). Rutenya memakai scoped binding, sehingga pesan milik booking
 * lain berujung 404 alih-alih tertandai diam-diam.
 */
class WhatsAppMessageController extends Controller
{
    /**
     * Advisor menekan "Buka WhatsApp".
     *
     * Tautannya sudah terbuka lewat `<a href>` di sisi peramban — permintaan
     * ini hanya mencatat bahwa itu terjadi. Isi pesannya disusun ulang di
     * server; request hanya membawa `template_key` (.claude/rules/50).
     */
    public function store(
        RecordWhatsAppMessageRequest $request,
        Booking $booking,
        WhatsAppMessageService $messages,
    ): RedirectResponse {
        Gate::authorize('view', $booking);

        $messages->record($booking, $request->templateKey(), $request->user());

        return back();
    }

    public function markSent(
        Request $request,
        Booking $booking,
        WhatsAppMessage $whatsappMessage,
        WhatsAppMessageService $messages,
    ): RedirectResponse {
        Gate::authorize('view', $booking);

        $messages->markSent($whatsappMessage);

        return back()->with('success', 'Pesan ditandai sudah terkirim.');
    }

    public function skip(
        Request $request,
        Booking $booking,
        WhatsAppMessage $whatsappMessage,
        WhatsAppMessageService $messages,
    ): RedirectResponse {
        Gate::authorize('view', $booking);

        $messages->markSkipped($whatsappMessage);

        return back()->with('success', 'Pesan ditandai dilewati.');
    }
}
