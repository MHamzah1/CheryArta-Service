<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\WhatsAppTemplateKey;
use RuntimeException;

/**
 * Pesan diminta untuk keadaan yang memang tidak punya draft (docs/08 §8.5).
 *
 * Dua sebab wajarnya: pelanggan tidak punya nomor WhatsApp, atau Super Admin
 * menonaktifkan templatenya di antara halaman dimuat dan tombol ditekan.
 * Keduanya bukan kesalahan advisor, jadi pesannya menjelaskan keadaan dan
 * jalan keluarnya.
 *
 * Penanganannya terpusat di bootstrap/app.php — 422 untuk permintaan JSON,
 * toast penjelasan untuk Inertia.
 */
final class WhatsAppDraftUnavailableException extends RuntimeException
{
    public static function noPhone(string $customerName): self
    {
        return new self(
            "{$customerName} belum punya nomor WhatsApp tersimpan, jadi pesannya tidak bisa disiapkan. ".
            'Lengkapi nomornya lebih dulu dari halaman customer.',
        );
    }

    public static function templateUnavailable(WhatsAppTemplateKey $key): self
    {
        return new self(sprintf(
            'Template "%s" sedang dinonaktifkan, jadi tidak ada pesan yang bisa dibuka. '.
            'Aktifkan kembali dari halaman Template WA bila pesannya memang diperlukan.',
            $key->label(),
        ));
    }
}
