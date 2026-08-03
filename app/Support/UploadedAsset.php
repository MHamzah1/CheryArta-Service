<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Hasil satu unggahan berkas.
 *
 * `publicId` disimpan agar berkas bisa dihapus atau diganti kelak; `url`
 * disimpan agar render halaman tidak perlu memanggil API penyedia sama sekali
 * (docs/12 §12.5).
 */
final readonly class UploadedAsset
{
    public function __construct(
        public string $publicId,
        public string $url,
    ) {}

    /**
     * Pasangan kolom siap simpan, mis. `toColumns('brochure_')` menghasilkan
     * `brochure_public_id` dan `brochure_url`.
     *
     * @return array<string, string>
     */
    public function toColumns(string $prefix = ''): array
    {
        return [
            $prefix.'public_id' => $this->publicId,
            $prefix.'url' => $this->url,
        ];
    }
}
