<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Penyusun URL transformasi Cloudinary.
 *
 * Dipisahkan dari CloudinaryImageUploader karena murni operasi teks — tidak
 * menyentuh jaringan — sehingga FakeImageUploader dapat memakai logika yang
 * persis sama dan hasilnya bisa diuji tanpa akun Cloudinary.
 */
final class CloudinaryUrl
{
    private const MARKER = '/upload/';

    /**
     * Menyisipkan `f_auto,q_auto,w_{width}` ke dalam URL Cloudinary.
     *
     * URL yang bukan milik Cloudinary dikembalikan apa adanya, dan transformasi
     * yang sudah tertulis sebelumnya diganti — bukan ditumpuk.
     */
    public static function withWidth(string $url, int $width): string
    {
        $position = strpos($url, self::MARKER);

        if ($position === false) {
            return $url;
        }

        $cut = $position + strlen(self::MARKER);
        $head = substr($url, 0, $cut);
        $tail = substr($url, $cut);

        // Segmen pertama adalah transformasi bila memuat salah satu penanda
        // ini; versi (`v1234…`) dan nama folder tidak akan cocok.
        $tail = preg_replace('#^[^/]*\b(f_auto|q_auto|w_\d+)\b[^/]*/#', '', $tail) ?? $tail;

        return $head."f_auto,q_auto,w_{$width}/".$tail;
    }
}
