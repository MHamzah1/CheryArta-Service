<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsAppTemplateKey;
use App\Models\Booking;
use App\Models\WhatsAppTemplate;
use App\Support\PhoneNumber;
use App\Support\WhatsAppDraft;
use App\Support\WhatsAppLink;
use App\Support\WhatsAppPlaceholders;

/**
 * Klik-to-chat: menyusun teks dan tautan `wa.me`, tanpa mengirim apa pun
 * (keputusan final #4, docs/08 §8.5).
 *
 * **Tidak menyentuh database.** Menyusun draft adalah operasi baca murni;
 * pencatatannya milik WhatsAppMessageService dan baru terjadi ketika advisor
 * benar-benar menekan tombolnya (keputusan grill Q1). Memisahkan keduanya juga
 * berarti implementasi gateway kelak tidak perlu menyalin ulang pencatatannya.
 *
 * Service tidak menyentuh request(), session(), atau auth()
 * (.claude/rules/10-backend-laravel.md).
 */
final class ClickToChatNotifier implements WhatsAppNotifier
{
    /**
     * Template yang sudah dibaca dalam permintaan ini.
     *
     * Layar jadwal menyusun draft pengingat untuk setiap booking pada satu
     * hari; tanpa simpanan ini setiap baris memicu kueri yang sama berulang
     * kali (.claude/rules/10 — dilarang N+1). Objeknya sengaja TIDAK singleton
     * agar simpanan ini tidak bertahan antar-permintaan — lihat AppServiceProvider.
     *
     * @var array<string, WhatsAppTemplate|null>
     */
    private array $templates = [];

    public function draft(Booking $booking, WhatsAppTemplateKey $templateKey): ?WhatsAppDraft
    {
        $phone = $booking->user->phone_wa;

        if ($phone === null || $phone === '') {
            return null;
        }

        $template = $this->template($templateKey);

        // Templatenya dinonaktifkan Super Admin, atau belum di-seed. TIDAK ada
        // jalur cadangan diam-diam ke teks bawaan: cadangan semacam itu membuat
        // tombol "nonaktifkan" tidak melakukan apa-apa, dan itu lebih
        // membingungkan daripada panel yang menjelaskan keadaannya
        // (keputusan grill Q5).
        if ($template === null) {
            return null;
        }

        $message = WhatsAppLink::truncate(
            WhatsAppPlaceholders::render($template->body, WhatsAppPlaceholders::forBooking($booking)),
        );

        return new WhatsAppDraft(
            templateKey: $templateKey,
            url: WhatsAppLink::to($phone, $message),
            message: $message,
            phone: $phone,
            phoneDisplay: PhoneNumber::forDisplay($phone),
        );
    }

    private function template(WhatsAppTemplateKey $key): ?WhatsAppTemplate
    {
        return $this->templates[$key->value] ??= WhatsAppTemplate::query()
            ->active()
            ->where('key', $key->value)
            ->first();
    }
}
