<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Facility;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Fasilitas beserta gambarnya (docs/07 §A10).
 *
 * Hanya fasilitas yang punya berkas di Cloudinary, sehingga hanya ia yang
 * butuh service tersendiri; FAQ dan testimoni cukup ditulis langsung dari
 * controller lewat Form Request.
 */
final readonly class FacilityService
{
    public function __construct(private ImageUploader $uploader) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image): Facility
    {
        // Unggahan dituntaskan sebelum transaksi: panggilan jaringan di dalam
        // transaksi menahan kunci baris jauh lebih lama dari yang perlu.
        $asset = $this->uploader->upload($image, $this->folder());

        return DB::transaction(function () use ($data, $asset): Facility {
            return Facility::create([
                ...$this->kolom($data),
                ...$asset->toColumns('image_'),
                // Baris baru selalu di belakang; urutannya diatur dengan
                // menyeret, bukan dengan mengetik angka.
                'sort_order' => (int) Facility::query()->max('sort_order') + 1,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Facility $facility, array $data, ?UploadedFile $image): Facility
    {
        $asset = $image === null ? null : $this->uploader->upload($image, $this->folder());
        $gambarLama = $facility->image_public_id;

        DB::transaction(function () use ($facility, $data, $asset): void {
            $facility->update([
                ...$this->kolom($data),
                ...($asset?->toColumns('image_') ?? []),
            ]);
        });

        // Berkas lama dibuang setelah barisnya benar-benar tersimpan. Urutan
        // sebaliknya berisiko: transaksi yang gagal akan meninggalkan baris
        // yang menunjuk berkas yang sudah dihapus.
        if ($asset !== null && $gambarLama !== null) {
            $this->uploader->delete($gambarLama);
        }

        return $facility->refresh();
    }

    public function delete(Facility $facility): void
    {
        $gambar = $facility->image_public_id;

        DB::transaction(fn () => $facility->delete());

        // Tanpa ini berkasnya menjadi yatim dan tetap membebani kuota
        // Cloudinary selamanya.
        if ($gambar !== null) {
            $this->uploader->delete($gambar);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function kolom(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'is_active' => $data['is_active'],
        ];
    }

    private function folder(): string
    {
        return Config::string('cloudinary.folders.facilities');
    }
}
