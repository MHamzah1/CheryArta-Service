<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssetType;
use App\Support\UploadedAsset;
use Illuminate\Http\UploadedFile;

/**
 * Satu-satunya pintu menuju penyedia penyimpanan berkas.
 *
 * Controller dan model TIDAK boleh memanggil SDK Cloudinary langsung —
 * alasannya sama dengan WhatsAppNotifier: berganti penyedia kelak cukup
 * menyentuh CloudinaryImageUploader, bukan seluruh aplikasi.
 *
 * Uji memakai FakeImageUploader sehingga tidak ada yang menembak jaringan.
 */
interface ImageUploader
{
    /**
     * Mengunggah satu berkas ke folder yang diberikan.
     *
     * Implementasi wajib membuang nama berkas asli dan menggantinya dengan
     * nama yang di-generate ulang (.claude/rules/50-keamanan.md).
     *
     * @param  string  $folder  Ambil dari config('cloudinary.folders.*')
     */
    public function upload(UploadedFile $file, string $folder, AssetType $type = AssetType::Image): UploadedAsset;

    /**
     * Menghapus berkas dari penyedia. Aman dipanggil untuk berkas yang sudah
     * tidak ada — tidak melempar galat.
     */
    public function delete(string $publicId, AssetType $type = AssetType::Image): void;

    /**
     * URL yang sudah dilengkapi transformasi lebar tetap.
     *
     * Menerima URL tersimpan (kolom `*_url`), bukan public id, agar versi dan
     * ekstensi berkas ikut terbawa.
     */
    public function transformedUrl(string $url, int $width): string;
}
