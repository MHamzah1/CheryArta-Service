<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Katalog produk Chery yang tampil di halaman publik.
        // Lihat docs/04-skema-database.md §4.2.
        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();

            // Disimpan sebagai string + PHP Enum, bukan tipe ENUM MySQL, agar
            // nilai baru tidak menuntut ALTER TABLE (.claude/rules/30-database.md).
            $table->string('category', 30);
            $table->string('fuel_type', 20);

            // Dipetakan ke service_packages.applicable_series untuk menentukan
            // paket maintenance mana yang relevan bagi model ini.
            $table->string('series_code', 40)->nullable();

            // Null berarti "hubungi kami", bukan gratis.
            $table->decimal('price_start', 12, 2)->nullable();

            $table->string('short_description', 255);
            $table->text('description')->nullable();

            // Hanya untuk spesifikasi yang ditampilkan apa adanya; tidak pernah
            // dipakai memfilter atau mengurutkan.
            $table->json('specs')->nullable();

            // Brosur PDF di Cloudinary (keputusan R3). public_id disimpan agar
            // berkas bisa diganti/dihapus; url agar render tidak memanggil API.
            $table->string('brochure_public_id', 255)->nullable();
            $table->string('brochure_url', 500)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // Katalog publik selalu difilter aktif lalu diurutkan.
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_models');
    }
};
