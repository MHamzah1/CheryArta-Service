<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsAppMessageStatus;
use App\Enums\WhatsAppTemplateKey;
use App\Exceptions\InvalidWhatsAppTransitionException;
use App\Exceptions\WhatsAppDraftUnavailableException;
use App\Models\Booking;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Jejak pengiriman klik-to-chat (docs/08 §8.2, §8.8).
 *
 * Terpisah dari WhatsAppNotifier dengan sengaja: menyusun draft adalah operasi
 * baca murni yang boleh terjadi berkali-kali saat halaman dimuat, sedangkan
 * pencatatan hanya terjadi sekali — ketika advisor benar-benar menekan tombol
 * (keputusan grill Q1). Pemisahan ini juga berarti implementasi gateway kelak
 * cukup memanggil service yang sama, bukan menyalin ulang pencatatannya.
 *
 * Service tidak menyentuh auth(): pelakunya diterima sebagai parameter
 * (.claude/rules/10-backend-laravel.md).
 */
final class WhatsAppMessageService
{
    public function __construct(private readonly WhatsAppNotifier $notifier) {}

    /**
     * Catat bahwa advisor membuka WhatsApp untuk booking ini.
     *
     * Isi pesannya disusun ULANG di sini dari template yang berlaku saat ini —
     * tidak ada teks yang datang dari request. Menerimanya dari klien akan
     * menjadikan kolom audit ini isian pengguna (.claude/rules/50), dan pada
     * saat itu ia berhenti membuktikan apa pun.
     */
    public function record(Booking $booking, WhatsAppTemplateKey $templateKey, User $actor): WhatsAppMessage
    {
        $draft = $this->notifier->draft($booking, $templateKey);

        if ($draft === null) {
            throw $booking->user->phone_wa === null || $booking->user->phone_wa === ''
                ? WhatsAppDraftUnavailableException::noPhone($booking->user->name)
                : WhatsAppDraftUnavailableException::templateUnavailable($templateKey);
        }

        return DB::transaction(fn (): WhatsAppMessage => WhatsAppMessage::create([
            'booking_id' => $booking->getKey(),
            'template_key' => $draft->templateKey,
            'recipient_phone' => $draft->phone,
            'rendered_message' => $draft->message,
            'generated_by' => $actor->getKey(),
            'status' => WhatsAppMessageStatus::Generated,
        ]));
    }

    public function markSent(WhatsAppMessage $message): WhatsAppMessage
    {
        return $this->settle($message, WhatsAppMessageStatus::Sent);
    }

    public function markSkipped(WhatsAppMessage $message): WhatsAppMessage
    {
        return $this->settle($message, WhatsAppMessageStatus::Skipped);
    }

    /**
     * Perpindahan status pesan, dengan barisnya dikunci.
     *
     * Dibaca ULANG di dalam transaksi, bukan dipercaya dari objek yang dibawa
     * controller: dua advisor bisa membuka booking yang sama, dan yang kedua
     * harus melihat hasil penekanan yang pertama — pola yang sama dengan
     * BookingService::changeStatus().
     */
    private function settle(WhatsAppMessage $message, WhatsAppMessageStatus $target): WhatsAppMessage
    {
        return DB::transaction(function () use ($message, $target): WhatsAppMessage {
            /** @var WhatsAppMessage $terkini */
            $terkini = WhatsAppMessage::query()->lockForUpdate()->findOrFail($message->getKey());

            if (! $terkini->status->canTransitionTo($target)) {
                throw InvalidWhatsAppTransitionException::alreadySettled($terkini->status);
            }

            $terkini->forceFill([
                'status' => $target,
                'sent_at' => $target === WhatsAppMessageStatus::Sent
                    ? now(Config::string('booking.timezone'))
                    : null,
            ])->save();

            return $terkini;
        });
    }
}
