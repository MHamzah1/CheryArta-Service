<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Varian tidak punya makna tanpa model induknya — karena itu cascade.
        // Lihat docs/04-skema-database.md §4.2.
        Schema::create('car_model_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();

            $table->string('name', 80);
            $table->decimal('price', 12, 2)->nullable();

            // Hanya selisih spesifikasi terhadap model dasar, bukan salinan penuh.
            $table->json('specs')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['car_model_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_model_variants');
    }
};
