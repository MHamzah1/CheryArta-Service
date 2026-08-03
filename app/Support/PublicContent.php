<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CarModel;
use App\Models\CarModelImage;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\ServicePackage;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Config;

/**
 * Bentuk data untuk halaman publik (docs/06 §6.5).
 *
 * Dikumpulkan di satu tempat karena beranda menampilkan versi ringkas dari
 * konten yang punya halaman sendiri: kalau bentuk kartu katalog ditulis dua
 * kali, cepat atau lambat keduanya berbeda.
 *
 * Aturan yang dipegang kelas ini:
 *
 * 1. **Hanya baris yang sudah disetujui admin** yang keluar — `active()` /
 *    `published()` selalu dipakai, tidak pernah `all()`.
 * 2. **Tidak ada data pribadi pelanggan.** Nama pada testimoni adalah nama
 *    yang diketik admin di tabel `testimonials`, bukan `users.name`; tidak
 *    ada telepon, email, maupun plat yang lewat sini (docs/09 §9.4).
 */
final class PublicContent
{
    /**
     * Paket layanan yang ditawarkan di halaman publik.
     *
     * `applicable_series` sengaja tidak ikut: pengunjung tidak mengenal kode
     * seri internal, dan daftarnya baru berguna di dalam form booking.
     *
     * @return list<array<string, mixed>>
     */
    public static function servicePackages(?int $limit = null): array
    {
        $query = ServicePackage::query()->active()->ordered();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (ServicePackage $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'category_label' => $package->category->label(),
                'description' => $package->description,
                'estimated_duration_minutes' => $package->estimated_duration_minutes,
                'price' => $package->price,
                'is_free' => $package->is_free,
            ])
            ->all();
    }

    /**
     * Kartu katalog. Memuat satu gambar per model lewat relasi `primaryImage`
     * supaya daftar tidak menarik seluruh galeri.
     *
     * @return list<array<string, mixed>>
     */
    public static function carModelCards(?int $limit = null): array
    {
        $query = CarModel::query()->active()->with('primaryImage')->ordered();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->map(self::carModelCard(...))->all();
    }

    /** @return array<string, mixed> */
    public static function carModelCard(CarModel $carModel): array
    {
        return [
            'name' => $carModel->name,
            'slug' => $carModel->slug,
            'category' => $carModel->category->value,
            'category_label' => $carModel->category->label(),
            'fuel_type' => $carModel->fuel_type->value,
            'fuel_type_label' => $carModel->fuel_type->label(),
            'price_start' => $carModel->price_start,
            'short_description' => $carModel->short_description,
            'thumbnail_url' => $carModel->primaryImage?->thumbnail_url,
            'thumbnail_alt' => $carModel->primaryImage?->alt,
        ];
    }

    /**
     * Galeri fasilitas. Fasilitas yang belum punya foto tetap ditampilkan
     * sebagai kartu teks — seeder memang belum mengunggah gambarnya, dan
     * menyembunyikannya membuat halaman terlihat kosong tanpa alasan.
     *
     * @return list<array<string, mixed>>
     */
    public static function facilities(?int $limit = null): array
    {
        $query = Facility::query()->active()->ordered();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (Facility $facility): array => [
                'id' => $facility->id,
                'title' => $facility->title,
                'description' => $facility->description,
                'thumbnail_url' => self::gambar($facility->image_url, 'thumbnail'),
                'image_url' => self::gambar($facility->image_url, 'full'),
            ])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function faqs(?int $limit = null): array
    {
        $query = Faq::query()->active()->ordered();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (Faq $faq): array => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'category' => $faq->category,
            ])
            ->all();
    }

    /**
     * Paket layanan dikelompokkan per kategori, untuk halaman `/layanan`.
     *
     * Urutan kelompok mengikuti urutan kemunculan pertama pada `ordered()`,
     * bukan urutan `case` di enum: `sort_order` adalah cara admin mengatur
     * apa yang tampil lebih dulu, dan itu berlaku sampai ke tingkat kelompok.
     *
     * @return list<array{label: string, packages: list<array<string, mixed>>}>
     */
    public static function servicePackageGroups(): array
    {
        $kelompok = [];

        foreach (self::servicePackages() as $package) {
            /** @var string $label */
            $label = $package['category_label'];
            $kelompok[$label][] = $package;
        }

        return array_map(
            static fn (string $label, array $packages): array => [
                'label' => $label,
                'packages' => $packages,
            ],
            array_keys($kelompok),
            $kelompok,
        );
    }

    /**
     * FAQ dikelompokkan per kategori. Kategorinya teks bebas di database
     * (tanpa enum), jadi labelnya dibentuk dari nilainya sendiri; baris tanpa
     * kategori masuk ke "Umum" alih-alih hilang dari halaman.
     *
     * @return list<array{label: string, faqs: list<array<string, mixed>>}>
     */
    public static function faqGroups(): array
    {
        $kelompok = [];

        foreach (self::faqs() as $faq) {
            $label = is_string($faq['category']) && $faq['category'] !== ''
                ? ucfirst($faq['category'])
                : 'Umum';

            $kelompok[$label][] = $faq;
        }

        return array_map(
            static fn (string $label, array $faqs): array => [
                'label' => $label,
                'faqs' => $faqs,
            ],
            array_keys($kelompok),
            $kelompok,
        );
    }

    /** @return list<array<string, mixed>> */
    public static function testimonials(?int $limit = null): array
    {
        $query = Testimonial::query()->published()->ordered();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (Testimonial $testimonial): array => [
                'id' => $testimonial->id,
                'customer_name' => $testimonial->customer_name,
                'car_model' => $testimonial->car_model,
                'rating' => $testimonial->rating,
                'content' => $testimonial->content,
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    public static function carModelImage(CarModelImage $image): array
    {
        return [
            'id' => $image->id,
            'thumbnail_url' => $image->thumbnail_url,
            'url' => CloudinaryUrl::withWidth($image->url, Config::integer('cloudinary.widths.full')),
            'alt' => $image->alt,
            'is_primary' => $image->is_primary,
        ];
    }

    private static function gambar(?string $url, string $ukuran): ?string
    {
        return $url === null
            ? null
            : CloudinaryUrl::withWidth($url, Config::integer("cloudinary.widths.{$ukuran}"));
    }
}
