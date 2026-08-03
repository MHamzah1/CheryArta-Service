<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keputusan R4: tabelnya dibuat sejak Big Fase 1 supaya landing page
        // membaca database sejak awal; layar CRUD-nya (A10) menyusul di
        // Big Fase 2 tanpa kerja ulang di landing page.
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('description', 255);

            // Cloudinary (keputusan R3) — lihat App\Support\UploadedAsset.
            $table->string('image_public_id', 255)->nullable();
            $table->string('image_url', 500)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
