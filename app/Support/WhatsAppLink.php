<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Config;

/**
 * Satu-satunya tempat tautan `wa.me` disusun (docs/08 §8.5).
 *
 * Dipakai draft booking MAUPUN balasan pesan kontak. Alasannya keamanan, bukan
 * kerapian: sistem lama menempelkan nomor apa adanya — lengkap dengan tanda
 * hubung hasil format otomatis — ke dalam URL, dan format itu tidak bisa
 * dipakai `wa.me` sama sekali (docs/08 §8.3). Dengan satu pintu, tidak ada
 * jalur kedua yang bisa melewatkan normalisasinya.
 */
final class WhatsAppLink
{
    /**
     * @param  string  $normalizedPhone  Bentuk `62…` dari App\Support\PhoneNumber
     */
    public static function to(string $normalizedPhone, string $message): string
    {
        return Config::string('whatsapp.chat_base_url')
            .$normalizedPhone
            .'?text='
            // rawurlencode, bukan urlencode: spasi harus menjadi %20, bukan '+'
            // yang akan terbaca sebagai tanda plus di WhatsApp (docs/08 §8.5).
            .rawurlencode(self::truncate($message));
    }

    /** Batas panjang agar tautan tidak terpotong peramban (docs/08 §8.5). */
    public static function truncate(string $message): string
    {
        return mb_substr($message, 0, Config::integer('whatsapp.max_message_length'));
    }
}
