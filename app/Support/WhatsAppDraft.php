<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\WhatsAppTemplateKey;

/**
 * Satu pesan siap kirim (docs/08 §8.5).
 *
 * Belum menyentuh database: barisnya baru ditulis ketika advisor menekan
 * "Buka WhatsApp" (keputusan grill Q1).
 *
 * `phone` sengaja tidak ikut ke props — panel hanya perlu bentuk yang enak
 * dibaca, dan nomor yang dipakai menyusun tautan tetap urusan server.
 */
final readonly class WhatsAppDraft
{
    public function __construct(
        public WhatsAppTemplateKey $templateKey,
        public string $url,
        public string $message,
        public string $phone,
        public string $phoneDisplay,
    ) {}

    /** @return array{template_key: string, template_label: string, url: string, message: string, phone_display: string} */
    public function toArray(): array
    {
        return [
            'template_key' => $this->templateKey->value,
            'template_label' => $this->templateKey->label(),
            'url' => $this->url,
            'message' => $this->message,
            'phone_display' => $this->phoneDisplay,
        ];
    }
}
