<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder dijalankan berurutan.
     *
     * Seluruhnya idempoten (`updateOrCreate`), sehingga `db:seed` boleh
     * dijalankan berkali-kali terhadap database yang sudah terisi tanpa
     * menggandakan baris — .claude/rules/30-database.md.
     *
     * WhatsAppTemplateSeeder menyusul di F2.2, DemoBookingSeeder di F2.1 —
     * lihat docs/04-skema-database.md §4.4.
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
        ]);
    }
}
