<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

/**
 * 8 fasilitas, judul dan deskripsi dibawa APA ADANYA dari prototipe lama
 * (`index (1).html`, `facilityData` baris 2025–2067).
 *
 * Gambarnya tidak diunggah di sini. Berkasnya ada di
 * `database/seeders/assets/fasilitas-*`, tetapi mengunggahnya menuntut
 * `CLOUDINARY_URL` terisi — dan seeder yang gagal tanpa kredensial membuat
 * `migrate:fresh --seed` tidak bisa dijalankan di CI maupun di komputer yang
 * belum disetel. Pekerjaan itu dipisahkan ke AssetImageSeeder, yang menyerah
 * dengan tenang bila kredensialnya belum ada.
 *
 * Seeder produksi — idempoten, aman dijalankan ulang.
 */
class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->facilities() as $sortOrder => $facility) {
            Facility::updateOrCreate(
                ['title' => $facility['title']],
                [...$facility, 'sort_order' => $sortOrder, 'is_active' => true],
            );
        }
    }

    /** @return list<array<string, string>> */
    private function facilities(): array
    {
        return [
            [
                'title' => 'Customer Service Center',
                'description' => 'Meja pelayanan customer service yang profesional',
            ],
            [
                'title' => 'Showroom Chery',
                'description' => 'Showroom eksklusif menampilkan koleksi lengkap kendaraan Chery',
            ],
            [
                'title' => 'Ruang Tunggu Premium',
                'description' => 'Ruang tunggu eksklusif dengan sofa nyaman dan mural pemandangan pegunungan',
            ],
            [
                'title' => 'Kids Play Area',
                'description' => 'Area bermain anak yang aman dan menyenangkan',
            ],
            [
                'title' => 'Workshop Modern',
                'description' => 'Bengkel dengan teknologi terdepan dan peralatan canggih',
            ],
            [
                'title' => 'Area Diskusi Santai',
                'description' => 'Area santai dengan meja bulat yang nyaman untuk berdiskusi dengan keluarga',
            ],
            [
                'title' => 'Ruang Konsultasi',
                'description' => 'Ruang meeting modern untuk diskusi dan konsultasi mengenai perawatan kendaraan',
            ],
            [
                'title' => 'Lounge Entertainment',
                'description' => 'Ruang tunggu VIP dengan sofa premium dan TV layar lebar',
            ],
        ];
    }
}
