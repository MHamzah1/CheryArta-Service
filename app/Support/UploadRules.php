<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AssetType;
use Illuminate\Support\Facades\Config;

/**
 * Aturan validasi unggahan, dibaca dari config('cloudinary.uploads').
 *
 * Dipusatkan di sini agar batas ukuran dan daftar MIME hanya hidup di satu
 * tempat — cacat B2 sistem lama justru lahir dari angka aturan yang ditulis
 * ulang di beberapa berkas.
 */
final class UploadRules
{
    /**
     * @return list<string>
     */
    public static function for(AssetType $type): array
    {
        $mimes = implode(',', Config::array("cloudinary.uploads.{$type->value}.mimes"));
        $maxKilobytes = Config::integer("cloudinary.uploads.{$type->value}.max_kb");

        $rules = ['file'];

        // `image` memeriksa bahwa isinya benar-benar gambar, bukan sekadar
        // ekstensinya; `mimes` memeriksa MIME sungguhan dari isi berkas.
        if ($type === AssetType::Image) {
            $rules[] = 'image';
        }

        $rules[] = "mimes:{$mimes}";
        $rules[] = "max:{$maxKilobytes}";

        return $rules;
    }
}
