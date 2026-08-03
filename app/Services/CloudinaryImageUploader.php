<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssetType;
use App\Support\CloudinaryUrl;
use App\Support\UploadedAsset;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Implementasi ImageUploader di atas SDK resmi cloudinary/cloudinary_php.
 *
 * Ini satu-satunya berkas yang mengenal istilah Cloudinary — di luar
 * App\Support\CloudinaryUrl yang hanya menyusun teks URL.
 */
final class CloudinaryImageUploader implements ImageUploader
{
    private readonly Cloudinary $cloudinary;

    public function __construct(string $credentialsUrl)
    {
        $this->cloudinary = new Cloudinary($credentialsUrl);
    }

    public function upload(UploadedFile $file, string $folder, AssetType $type = AssetType::Image): UploadedAsset
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw new RuntimeException('Berkas unggahan tidak dapat dibaca dari penyimpanan sementara.');
        }

        $response = $this->cloudinary->uploadApi()->upload($path, [
            'folder' => $folder,
            // Nama berkas asli dibuang seluruhnya dan diganti UUID
            // (.claude/rules/50-keamanan.md — Unggahan Berkas).
            'public_id' => (string) Str::uuid(),
            'use_filename' => false,
            'unique_filename' => false,
            'overwrite' => false,
            'resource_type' => self::resourceType($type),
        ]);

        return new UploadedAsset(
            publicId: self::stringField($response, 'public_id'),
            url: self::stringField($response, 'secure_url'),
        );
    }

    public function delete(string $publicId, AssetType $type = AssetType::Image): void
    {
        try {
            $this->cloudinary->uploadApi()->destroy($publicId, [
                'resource_type' => self::resourceType($type),
                'invalidate' => true,
            ]);
        } catch (NotFound) {
            // Berkas memang sudah tidak ada — keadaan akhir yang diinginkan
            // sudah tercapai, jadi tidak ada yang perlu dilaporkan.
        }
    }

    public function transformedUrl(string $url, int $width): string
    {
        return CloudinaryUrl::withWidth($url, $width);
    }

    /**
     * Pemetaan istilah aplikasi ke istilah Cloudinary. Brosur PDF diunggah
     * sebagai `raw` agar tidak tersandung setelan "PDF delivery" yang
     * bawaannya nonaktif di akun gratis.
     */
    private static function resourceType(AssetType $type): string
    {
        return match ($type) {
            AssetType::Image => 'image',
            AssetType::Document => 'raw',
        };
    }

    private static function stringField(ApiResponse $response, string $key): string
    {
        $value = $response[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new RuntimeException("Respons Cloudinary tidak memuat '{$key}'.");
        }

        return $value;
    }
}
