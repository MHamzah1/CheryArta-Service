<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssetType;
use App\Support\CloudinaryUrl;
use App\Support\UploadedAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Pengganti ImageUploader untuk pengujian: mencatat apa yang diunggah dan
 * dihapus, tanpa satu pun panggilan jaringan.
 *
 * Bentuk `publicId` dan `url` sengaja menyerupai keluaran Cloudinary
 * (`folder/uuid` dan `.../<resource>/upload/v1/<public_id>.<ext>`) supaya uji
 * yang lulus di sini juga benar terhadap implementasi sungguhan.
 */
final class FakeImageUploader implements ImageUploader
{
    /** @var list<array{asset: UploadedAsset, folder: string, type: AssetType, original_name: string}> */
    private array $uploads = [];

    /** @var list<array{public_id: string, type: AssetType}> */
    private array $deletions = [];

    public function upload(UploadedFile $file, string $folder, AssetType $type = AssetType::Image): UploadedAsset
    {
        $publicId = $folder.'/'.Str::uuid();

        $asset = new UploadedAsset(
            publicId: $publicId,
            url: sprintf(
                'https://res.cloudinary.com/fake/%s/upload/v1/%s.%s',
                $type === AssetType::Image ? 'image' : 'raw',
                $publicId,
                $this->extensionOf($file),
            ),
        );

        $this->uploads[] = [
            'asset' => $asset,
            'folder' => $folder,
            'type' => $type,
            'original_name' => $file->getClientOriginalName(),
        ];

        return $asset;
    }

    public function delete(string $publicId, AssetType $type = AssetType::Image): void
    {
        $this->deletions[] = ['public_id' => $publicId, 'type' => $type];
    }

    public function transformedUrl(string $url, int $width): string
    {
        return CloudinaryUrl::withWidth($url, $width);
    }

    /** @return list<array{asset: UploadedAsset, folder: string, type: AssetType, original_name: string}> */
    public function uploads(): array
    {
        return $this->uploads;
    }

    /** @return list<string> */
    public function deletedPublicIds(): array
    {
        return array_map(static fn (array $deletion): string => $deletion['public_id'], $this->deletions);
    }

    private function extensionOf(UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();

        return $extension !== '' ? $extension : 'bin';
    }
}
