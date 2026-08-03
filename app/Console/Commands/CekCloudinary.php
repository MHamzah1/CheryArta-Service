<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AssetType;
use App\Services\FakeImageUploader;
use App\Services\ImageUploader;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Throwable;

/**
 * Membuktikan kredensial Cloudinary benar-benar bekerja.
 *
 * Dibutuhkan karena layar unggah gambar baru dibangun di F1.3 — tanpa
 * perintah ini, kriteria "unggah gambar percobaan muncul di Cloudinary"
 * (F1.1) hanya bisa diperiksa dengan menulis kode sekali pakai.
 */
final class CekCloudinary extends Command
{
    protected $signature = 'cloudinary:cek {--simpan : Biarkan berkas uji tetap ada di Cloudinary}';

    protected $description = 'Mengunggah satu gambar uji ke Cloudinary lalu menghapusnya kembali';

    /** PNG 1×1 — sengaja disemat agar perintah ini tidak bergantung pada berkas lain. */
    private const PIXEL_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function handle(): int
    {
        // Sengaja di-resolve di sini, bukan lewat injeksi method: bila
        // CLOUDINARY_URL kosong, container melempar galat — dan perintah yang
        // gunanya justru mendiagnosis konfigurasi tidak sepantasnya
        // memuntahkan stack trace untuk hal yang sudah ia ketahui.
        try {
            $uploader = app(ImageUploader::class);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($uploader instanceof FakeImageUploader) {
            $this->components->error(
                'ImageUploader sedang memakai FakeImageUploader, jadi tidak ada yang benar-benar '
                .'diunggah. Isi CLOUDINARY_URL dan pastikan CLOUDINARY_FAKE tidak bernilai true.',
            );

            return self::FAILURE;
        }

        $folder = Config::string('cloudinary.folders.car_models');
        $path = tempnam(sys_get_temp_dir(), 'cek-cloudinary-').'.png';
        $contents = base64_decode(self::PIXEL_PNG_BASE64, true);

        if ($contents === false || file_put_contents($path, $contents) === false) {
            $this->components->error('Gagal menyiapkan berkas uji di penyimpanan sementara.');

            return self::FAILURE;
        }

        try {
            $asset = $uploader->upload(
                new UploadedFile($path, 'uji-cloudinary.png', 'image/png', test: true),
                $folder,
                AssetType::Image,
            );
        } catch (Throwable $e) {
            $this->components->error('Unggah gagal: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($path);
        }

        $this->components->info('Unggahan berhasil.');
        $this->components->twoColumnDetail('public_id', $asset->publicId);
        $this->components->twoColumnDetail('url', $asset->url);
        $this->components->twoColumnDetail(
            'thumbnail',
            $uploader->transformedUrl($asset->url, Config::integer('cloudinary.widths.thumbnail')),
        );

        if ($this->option('simpan')) {
            $this->components->warn('Berkas uji dibiarkan ada. Hapus manual lewat Media Library bila tidak diperlukan.');

            return self::SUCCESS;
        }

        $uploader->delete($asset->publicId, AssetType::Image);
        $this->components->info('Berkas uji sudah dihapus kembali dari Cloudinary.');

        return self::SUCCESS;
    }
}
