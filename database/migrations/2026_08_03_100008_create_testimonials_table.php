<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keputusan R4 — lihat migration facilities.
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();

            // Nama ditulis apa adanya oleh admin, bukan diambil dari users:
            // testimoni boleh dipasang untuk pelanggan yang tidak punya akun,
            // dan menampilkan nama pelanggan asli di halaman publik dilarang
            // (.claude/rules/50-keamanan.md — Data Pribadi).
            $table->string('customer_name', 120);
            $table->string('car_model', 120)->nullable();

            $table->unsignedTinyInteger('rating');
            $table->text('content');

            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
