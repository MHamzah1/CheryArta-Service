<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menggantikan dropdown hardcode sistem lama.
        // Lihat docs/04-skema-database.md §4.2.
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();

            // Kode lama (`first_maintenance_1000`, `Tiggo_8_Free`, `Other`, …)
            // WAJIB dipertahankan persis supaya data servis lama tetap bisa
            // dipetakan — .claude/rules/30-database.md.
            $table->string('code', 60)->unique();

            $table->string('name', 200);
            $table->string('category', 40);
            $table->text('description')->nullable();

            // ["tiggo_8"] — null berarti berlaku untuk seluruh seri.
            $table->json('applicable_series')->nullable();

            // Dasar perhitungan estimasi jam selesai di SlotService (F1.4).
            $table->unsignedSmallInteger('estimated_duration_minutes')->default(60);

            $table->decimal('price', 12, 2)->default(0);

            // Dibedakan dari `price = 0` supaya antarmuka bisa menulis "Gratis"
            // alih-alih "Rp 0".
            $table->boolean('is_free')->default(false);

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
