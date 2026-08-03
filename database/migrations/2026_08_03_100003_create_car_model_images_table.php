<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_model_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();

            // Gambar hidup di Cloudinary (keputusan R3), bukan di disk server
            // yang bersifat ephemeral. Sepasang kolom ini yang dihasilkan
            // App\Support\UploadedAsset::toColumns().
            $table->string('public_id', 255);
            $table->string('url', 500);

            // Wajib diisi — aturan aksesibilitas di .claude/rules/20.
            $table->string('alt', 160);

            // Satu per model; ditegakkan di Service, bukan lewat unique index,
            // karena "satu true per car_model_id" bukan kekangan yang bisa
            // dinyatakan MySQL tanpa kolom bantu.
            $table->boolean('is_primary')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['car_model_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_model_images');
    }
};
