<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssetType;
use App\Exceptions\MasterDataInUseException;
use App\Models\CarModel;
use App\Models\CarModelVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Aturan seputar katalog mobil dan varian-nya (docs/07-modul-admin.md §A4).
 *
 * Galeri gambar punya service tersendiri (CarModelGalleryService) karena
 * aturannya berbeda jenis: urutan, gambar utama, dan berkas di penyedia.
 */
class CarModelService
{
    public function __construct(private readonly ImageUploader $uploader) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data): CarModel
    {
        // Unggahan dikerjakan SEBELUM transaksi: memanggil jaringan di dalam
        // transaksi menahan kunci baris selama lalu lintas ke Cloudinary.
        $brochure = $this->unggahBrosur($data['brochure'] ?? null);

        return DB::transaction(function () use ($data, $brochure): CarModel {
            $attributes = $this->atribut($data);
            $attributes['slug'] = $this->slugUnik($data['slug'] ?? null, $data['name']);

            if ($brochure !== null) {
                $attributes = [...$attributes, ...$brochure];
            }

            return CarModel::create($attributes);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(CarModel $carModel, array $data): CarModel
    {
        $brochure = $this->unggahBrosur($data['brochure'] ?? null);
        $brosurLama = $carModel->brochure_public_id;

        DB::transaction(function () use ($carModel, $data, $brochure): void {
            $attributes = $this->atribut($data);
            $attributes['slug'] = $this->slugUnik($data['slug'] ?? null, $data['name'], $carModel);

            if ($brochure !== null) {
                $attributes = [...$attributes, ...$brochure];
            }

            $carModel->update($attributes);
        });

        // Berkas lama dibuang hanya setelah baris tersimpan — kalau urutannya
        // dibalik dan penyimpanan gagal, brosurnya hilang tanpa pengganti.
        if ($brochure !== null && $brosurLama !== null) {
            $this->uploader->delete($brosurLama, AssetType::Document);
        }

        return $carModel;
    }

    /** Brosur diunggah lewat form model; di sini hanya jalur membuangnya. */
    public function deleteBrochure(CarModel $carModel): void
    {
        $publicId = $carModel->brochure_public_id;

        if ($publicId === null) {
            return;
        }

        $carModel->update(['brochure_public_id' => null, 'brochure_url' => null]);

        $this->uploader->delete($publicId, AssetType::Document);
    }

    /**
     * Model yang sudah dipakai kendaraan pelanggan hanya boleh dinonaktifkan:
     * menghapusnya mengosongkan `vehicles.car_model_id` (FK nullOnDelete),
     * sehingga unit pelanggan kehilangan identitas modelnya
     * (.claude/rules/30-database.md).
     */
    public function delete(CarModel $carModel): void
    {
        if ($carModel->isReferenced()) {
            throw new MasterDataInUseException(
                "Model \"{$carModel->name}\" sudah dipakai kendaraan pelanggan, jadi tidak bisa dihapus. "
                .'Nonaktifkan saja agar tidak lagi tampil di katalog publik.',
            );
        }

        $berkas = $carModel->images()->pluck('public_id')->all();
        $brosur = $carModel->brochure_public_id;

        DB::transaction(fn () => $carModel->delete());

        // Varian dan baris gambar ikut terhapus lewat cascade FK; berkasnya di
        // Cloudinary tidak, jadi dibuang di sini.
        foreach ($berkas as $publicId) {
            $this->uploader->delete($publicId);
        }

        if ($brosur !== null) {
            $this->uploader->delete($brosur, AssetType::Document);
        }
    }

    /** @param  array<string, mixed>  $data */
    public function addVariant(CarModel $carModel, array $data): CarModelVariant
    {
        $data['specs'] = $this->specs($data['specs'] ?? null);
        $data['sort_order'] = $data['sort_order'] ?? ((int) $carModel->variants()->max('sort_order') + 1);

        return $carModel->variants()->create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function updateVariant(CarModelVariant $variant, array $data): CarModelVariant
    {
        $data['specs'] = $this->specs($data['specs'] ?? null);
        $data['sort_order'] = (int) ($data['sort_order'] ?? $variant->sort_order);

        $variant->update($data);

        return $variant;
    }

    public function deleteVariant(CarModelVariant $variant): void
    {
        // Varian belum dirujuk tabel mana pun — `vehicles` menyimpan model,
        // bukan varian — sehingga penghapusannya tidak meninggalkan yatim.
        $variant->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function atribut(array $data): array
    {
        unset($data['brochure'], $data['slug']);

        $data['specs'] = $this->specs($data['specs'] ?? null);

        // Kolomnya NOT NULL berdefault 0, sedangkan input kosong sampai ke sini
        // sebagai null (ConvertEmptyStringsToNull).
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    /**
     * Spesifikasi datang dari antarmuka sebagai daftar pasangan agar urutannya
     * terjaga dan kunci kembar bisa ditolak validasi; skema menyimpannya
     * sebagai objek `{"mesin":"1.6 TGDI"}` (docs/04 §4.2).
     *
     * @param  mixed  $specs
     * @return array<string, string>|null
     */
    private function specs($specs): ?array
    {
        if (! is_array($specs)) {
            return null;
        }

        $pairs = [];

        foreach ($specs as $spec) {
            if (! is_array($spec) || ! isset($spec['key'], $spec['value'])) {
                continue;
            }

            $pairs[trim((string) $spec['key'])] = trim((string) $spec['value']);
        }

        return $pairs === [] ? null : $pairs;
    }

    /**
     * Slug boleh disunting admin; bila dikosongkan, diturunkan dari nama.
     * Akhiran angka hanya ditambahkan untuk slug turunan — slug yang diketik
     * sendiri sudah dijamin unik oleh Form Request, jadi mengubahnya diam-diam
     * justru menyembunyikan kekeliruan dari admin.
     */
    private function slugUnik(?string $slug, string $nama, ?CarModel $kecuali = null): string
    {
        if (is_string($slug) && trim($slug) !== '') {
            return Str::slug($slug);
        }

        $dasar = Str::slug($nama);
        $kandidat = $dasar;
        $urutan = 1;

        while ($this->slugTerpakai($kandidat, $kecuali)) {
            $kandidat = $dasar.'-'.(++$urutan);
        }

        return $kandidat;
    }

    private function slugTerpakai(string $slug, ?CarModel $kecuali): bool
    {
        return CarModel::query()
            ->where('slug', $slug)
            ->when($kecuali !== null, fn ($query) => $query->whereKeyNot($kecuali->getKey()))
            ->exists();
    }

    /**
     * @param  mixed  $file
     * @return array<string, string>|null
     */
    private function unggahBrosur($file): ?array
    {
        if (! $file instanceof UploadedFile) {
            return null;
        }

        return $this->uploader
            ->upload($file, Config::string('cloudinary.folders.brochures'), AssetType::Document)
            ->toColumns('brochure_');
    }
}
