<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AssetType;
use App\Models\CarModel;
use App\Models\Facility;
use App\Services\ImageUploader;
use App\Support\UploadedAsset;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Memasang gambar katalog & fasilitas dari `database/seeders/assets`.
 *
 * Dijalankan PALING AKHIR karena bergantung pada baris yang dibuat
 * CarModelSeeder dan FacilitySeeder.
 *
 * Tiga sifat yang membuat seeder ini aman dijalankan di mana pun:
 *
 * 1. **Menyerah dengan tenang tanpa Cloudinary.** Selama `CLOUDINARY_URL`
 *    kosong, `AppServiceProvider` melempar galat saat ImageUploader
 *    di-resolve. Galat itu ditangkap di sini supaya `migrate:fresh --seed`
 *    tetap jalan di CI dan di komputer yang belum disetel — alasan yang sama
 *    dengan yang membuat FacilitySeeder tidak mengunggah gambarnya sendiri.
 * 2. **Idempoten lewat keadaan, bukan penanda.** Model yang sudah punya baris
 *    `car_model_images` dan fasilitas yang `image_public_id`-nya sudah terisi
 *    dilewati. Menjalankan ulang `db:seed` karena itu tidak menggandakan
 *    gambar dan tidak menghabiskan kuota unggah Cloudinary.
 * 3. **Berkas yang hilang dilewati, bukan menggagalkan seluruh seeder.** Satu
 *    gambar yang belum diunduh tidak sepantasnya membuat basis data gagal
 *    dibangun.
 *
 * Sumber gambar mobil: media resmi Chery Indonesia (chery.co.id) dan Jaecoo
 * Indonesia (jaecoo.id) — lihat docs/12 §12.9. Gambar fasilitas dibawa dari
 * prototipe lama sejak F1.0 tugas 0.7.
 */
class AssetImageSeeder extends Seeder
{
    /**
     * Slug model katalog → nama berkas di `database/seeders/assets`.
     *
     * Slugnya harus cocok dengan CarModelSeeder; model yang slugnya tidak ada
     * di sini tetap tampil tanpa gambar, bukan gagal.
     *
     * @var array<string, string>
     */
    private const CAR_MODEL_IMAGES = [
        'tiggo-5x' => 'mobil-tiggo-5x.png',
        'tiggo-cross' => 'mobil-tiggo-cross.png',
        'tiggo-7-pro' => 'mobil-tiggo-7-pro.png',
        'tiggo-8-pro' => 'mobil-tiggo-8-pro.png',
        'omoda-5' => 'mobil-omoda-5.png',
        'omoda-e5' => 'mobil-omoda-e5.png',
        'jaecoo-j7' => 'mobil-jaecoo-j7.webp',
    ];

    /**
     * Judul fasilitas → nama berkas. Dicocokkan lewat judul karena itulah
     * kunci yang dipakai FacilitySeeder pada `updateOrCreate`.
     *
     * @var array<string, string>
     */
    private const FACILITY_IMAGES = [
        'Customer Service Center' => 'fasilitas-1-customer-service.webp',
        'Showroom Chery' => 'fasilitas-2-showroom.jpg',
        'Ruang Tunggu Premium' => 'fasilitas-3-ruang-tunggu.jpg',
        'Kids Play Area' => 'fasilitas-4-kids-play-area.jpg',
        'Workshop Modern' => 'fasilitas-5-workshop.jpg',
        'Area Diskusi Santai' => 'fasilitas-6-area-diskusi.webp',
        'Ruang Konsultasi' => 'fasilitas-7-ruang-konsultasi.webp',
        'Lounge Entertainment' => 'fasilitas-8-lounge.webp',
    ];

    public function run(): void
    {
        $uploader = $this->uploader();

        if ($uploader === null) {
            $this->catat(
                'CLOUDINARY_URL belum diisi — gambar katalog & fasilitas dilewati. '
                .'Isi kredensialnya lalu jalankan `php artisan db:seed --class=AssetImageSeeder`.',
            );

            return;
        }

        $this->isiGambarKatalog($uploader);
        $this->isiGambarFasilitas($uploader);
    }

    private function isiGambarKatalog(ImageUploader $uploader): void
    {
        $folder = Config::string('cloudinary.folders.car_models');

        foreach (self::CAR_MODEL_IMAGES as $slug => $namaBerkas) {
            $carModel = CarModel::query()->where('slug', $slug)->first();

            // `exists()` dan bukan penanda tersendiri: begitu satu gambar
            // terpasang lewat seeder ini ATAU lewat layar admin F1.3, tidak
            // ada lagi yang perlu diunggah.
            if ($carModel === null || $carModel->images()->exists()) {
                continue;
            }

            $asset = $this->unggah($uploader, $namaBerkas, $folder);

            if ($asset === null) {
                continue;
            }

            $carModel->images()->create([
                ...$asset->toColumns(),
                'alt' => "Foto mobil {$carModel->name}",
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }
    }

    private function isiGambarFasilitas(ImageUploader $uploader): void
    {
        $folder = Config::string('cloudinary.folders.facilities');

        foreach (self::FACILITY_IMAGES as $judul => $namaBerkas) {
            $facility = Facility::query()->where('title', $judul)->first();

            if ($facility === null || $facility->image_public_id !== null) {
                continue;
            }

            $asset = $this->unggah($uploader, $namaBerkas, $folder);

            if ($asset === null) {
                continue;
            }

            $facility->update($asset->toColumns('image_'));
        }
    }

    /**
     * Mengunggah satu berkas aset. Mengembalikan null bila berkasnya tidak ada
     * atau penyedia menolak — pemanggilnya melanjutkan ke berkas berikutnya.
     */
    private function unggah(ImageUploader $uploader, string $namaBerkas, string $folder): ?UploadedAsset
    {
        $path = database_path('seeders/assets/'.$namaBerkas);

        if (! is_file($path)) {
            $this->catat("Berkas {$namaBerkas} tidak ditemukan di database/seeders/assets — dilewati.");

            return null;
        }

        try {
            // `test: true` memakai berkas apa adanya dari disk; tanpa itu
            // UploadedFile menuntut berkas hasil unggahan HTTP sungguhan.
            return $uploader->upload(
                new UploadedFile($path, $namaBerkas, test: true),
                $folder,
                AssetType::Image,
            );
        } catch (Throwable $e) {
            $this->catat("Unggah {$namaBerkas} gagal: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * ImageUploader sengaja di-resolve lewat container di dalam try/catch.
     *
     * Bila `CLOUDINARY_URL` kosong dan `CLOUDINARY_FAKE` tidak aktif,
     * AppServiceProvider memang melempar — dan itu keputusan yang benar untuk
     * jalur unggah sungguhan. Yang tidak benar adalah membiarkannya
     * menggagalkan `migrate:fresh --seed`.
     */
    private function uploader(): ?ImageUploader
    {
        try {
            return app(ImageUploader::class);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Dicatat ke log, bukan ke konsol.
     *
     * `$this->command` milik Seeder baru terisi bila seeder dipanggil lewat
     * `db:seed`, tetapi PHPDoc Laravel menyatakannya non-nullable — sehingga
     * `?->` maupun `isset()` sama-sama ditolak PHPStan, dan memanggilnya
     * langsung berisiko fatal error pada jalur pemanggilan lain. Log selalu
     * tersedia dan tidak menuntut apa pun tentang cara seeder dijalankan.
     */
    private function catat(string $pesan): void
    {
        Log::warning("AssetImageSeeder: {$pesan}");
    }
}
