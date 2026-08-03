<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis berkas yang boleh diunggah pengguna internal.
 *
 * Sengaja tidak menyebut istilah penyedia (Cloudinary "resource_type"):
 * pemetaan ke istilah penyedia dilakukan di CloudinaryImageUploader supaya
 * berganti penyedia tidak menyentuh enum ini.
 */
enum AssetType: string
{
    case Image = 'image';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Gambar',
            self::Document => 'Dokumen',
        };
    }
}
