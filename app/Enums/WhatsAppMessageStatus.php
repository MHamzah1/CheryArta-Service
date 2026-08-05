<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Perjalanan satu draft pesan WhatsApp (docs/04 §4.2).
 *
 * Barisnya baru lahir ketika advisor menekan "Buka WhatsApp" — bukan ketika
 * status booking berubah (keputusan grill Q1). Karena itu `generated` di sini
 * berarti "WhatsApp sudah dibuka, belum ditandai", bukan "server sempat
 * merender sesuatu".
 *
 * `skipped` dipakai untuk keputusan sadar advisor: pelanggan ini memang tidak
 * perlu dikabari. Keduanya sama-sama mengeluarkan booking dari hitungan
 * tunggakan di dashboard.
 */
enum WhatsAppMessageStatus: string
{
    case Generated = 'generated';
    case Sent = 'sent';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Generated => 'Sudah Dibuka',
            self::Sent => 'Terkirim',
            self::Skipped => 'Dilewati',
        };
    }

    /**
     * Kunci token warna; peta warnanya di resources/js/lib/status.ts — satu
     * sumber kebenaran untuk tampilan, sama seperti BookingStatus::tone().
     */
    public function tone(): string
    {
        return match ($this) {
            self::Generated => 'pending',
            self::Sent => 'done',
            self::Skipped => 'noshow',
        };
    }

    /**
     * Status akhir: pesan sudah selesai diurus, apa pun hasilnya.
     *
     * Inilah yang mengeluarkan sebuah booking dari kartu "Belum dikabari".
     */
    public function isSettled(): bool
    {
        return $this !== self::Generated;
    }

    /**
     * Perpindahan yang sah. Sekali selesai diurus, pesan tidak berubah lagi —
     * "Terkirim" yang bisa dijadikan "Dilewati" membuat jejaknya tidak berarti.
     */
    public function canTransitionTo(self $target): bool
    {
        return $this === self::Generated && $target->isSettled();
    }

    /** @return list<string> */
    public static function settledValues(): array
    {
        return [self::Sent->value, self::Skipped->value];
    }
}
