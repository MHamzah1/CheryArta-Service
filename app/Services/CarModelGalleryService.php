<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CarModel;
use App\Models\CarModelImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Galeri katalog: unggah, urutan, gambar utama, dan penghapusan berkas
 * (docs/07-modul-admin.md §A4).
 *
 * Dua kekangan yang tidak bisa dinyatakan MySQL dan karena itu ditegakkan di
 * sini: hanya ada satu gambar utama per model, dan model yang punya gambar
 * selalu punya satu yang utama.
 */
class CarModelGalleryService
{
    public function __construct(private readonly ImageUploader $uploader) {}

    /**
     * Menambahkan beberapa gambar sekaligus. `$alts` sejajar dengan `$files`
     * berdasarkan indeks — teks alt wajib, jadi keduanya selalu sama panjang
     * (dijamin Form Request).
     *
     * @param  list<UploadedFile>  $files
     * @param  list<string>  $alts
     * @return int Jumlah gambar yang tersimpan.
     */
    public function addImages(CarModel $carModel, array $files, array $alts): int
    {
        $folder = Config::string('cloudinary.folders.car_models');

        // Seluruh unggahan dituntaskan lebih dulu, di luar transaksi: panggilan
        // jaringan di dalam transaksi menahan kunci baris jauh lebih lama dari
        // yang diperlukan.
        $assets = [];

        foreach ($files as $index => $file) {
            $assets[] = [
                'asset' => $this->uploader->upload($file, $folder),
                'alt' => $alts[$index] ?? '',
            ];
        }

        return DB::transaction(function () use ($carModel, $assets): int {
            $urutan = (int) $carModel->images()->max('sort_order');
            $adaUtama = $carModel->images()->where('is_primary', true)->exists();

            foreach ($assets as $item) {
                $carModel->images()->create([
                    ...$item['asset']->toColumns(),
                    'alt' => $item['alt'],
                    // Gambar pertama otomatis menjadi utama — tanpa ini kartu
                    // katalog model baru tampil tanpa gambar sama sekali.
                    'is_primary' => ! $adaUtama,
                    'sort_order' => ++$urutan,
                ]);

                $adaUtama = true;
            }

            return count($assets);
        });
    }

    public function updateAlt(CarModelImage $image, string $alt): void
    {
        $image->update(['alt' => $alt]);
    }

    public function markPrimary(CarModelImage $image): void
    {
        DB::transaction(function () use ($image): void {
            CarModelImage::query()
                ->where('car_model_id', $image->car_model_id)
                ->whereKeyNot($image->getKey())
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $image->update(['is_primary' => true]);
        });
    }

    /**
     * Menyimpan urutan hasil seret. Id yang tidak dimiliki model ini sudah
     * ditolak Form Request, sehingga di sini cukup menuliskan posisinya.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(CarModel $carModel, array $orderedIds): void
    {
        DB::transaction(function () use ($carModel, $orderedIds): void {
            foreach ($orderedIds as $posisi => $id) {
                $carModel->images()->whereKey($id)->update(['sort_order' => $posisi + 1]);
            }
        });
    }

    public function remove(CarModelImage $image): void
    {
        $publicId = $image->public_id;
        $tadinyaUtama = $image->is_primary;
        $carModel = $image->carModel;

        DB::transaction(function () use ($image, $tadinyaUtama, $carModel): void {
            $image->delete();

            // Jangan tinggalkan model bergambar tanpa gambar utama.
            if ($tadinyaUtama) {
                $carModel->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
            }
        });

        $this->uploader->delete($publicId);
    }
}
