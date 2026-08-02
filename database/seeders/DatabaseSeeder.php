<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder dijalankan berurutan.
     *
     * Seeder data master (paket layanan, katalog mobil, fasilitas, FAQ,
     * template WA) ditambahkan pada Fase 1 dan 5 — lihat
     * docs/04-skema-database.md §4.4.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
