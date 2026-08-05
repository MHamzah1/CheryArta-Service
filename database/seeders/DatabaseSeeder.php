<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder dijalankan berurutan.
     *
     * Seluruhnya idempoten, sehingga `db:seed` boleh dijalankan berkali-kali
     * terhadap database yang sudah terisi tanpa menggandakan baris —
     * .claude/rules/30-database.md.
     *
     * Sebagian besar memakai `updateOrCreate`. WhatsAppTemplateSeeder adalah
     * pengecualian yang disengaja: isinya justru dimaksudkan untuk disunting
     * Super Admin, jadi ia hanya membuat baris yang belum ada dan tidak pernah
     * menimpa yang sudah ada.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ServicePackageSeeder::class,
            CarModelSeeder::class,
            FacilitySeeder::class,
            FaqSeeder::class,
            TestimonialSeeder::class,
            WhatsAppTemplateSeeder::class,

            // Paling akhir: bergantung pada baris yang dibuat CarModelSeeder
            // dan FacilitySeeder. Menyerah dengan tenang bila Cloudinary
            // belum disetel, sehingga urutan ini tetap aman di CI.
            AssetImageSeeder::class,
        ]);
    }
}
