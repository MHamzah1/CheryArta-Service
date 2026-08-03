<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unit milik customer. Lihat docs/04-skema-database.md §4.2.
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Null bila kendaraannya bukan Chery (pertanyaan terbuka Q3 —
            // rancangan saat ini membolehkan). nullOnDelete, bukan cascade:
            // menonaktifkan model katalog tidak boleh melenyapkan kendaraan
            // milik pelanggan.
            $table->foreignId('car_model_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model_name_manual', 120)->nullable();

            // Plat disimpan bersegmen, bukan satu string. Prefix 1–2 huruf —
            // sistem lama memaksanya 2 dan menolak plat sah seperti "B"
            // (temuan B1). Rules 30-database.md melarang menyimpannya utuh saja.
            $table->string('plate_prefix', 2);
            $table->string('plate_number', 4);
            $table->string('plate_suffix', 3);

            // Nilai turunan, di-generate model. Disimpan karena dipakai
            // memfilter dan menegakkan keunikan per pemilik.
            $table->string('plate_full', 12);

            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color', 40)->nullable();
            $table->string('vin', 25)->nullable();
            $table->unsignedInteger('last_odometer')->nullable();
            $table->boolean('is_primary')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Satu pemilik tidak boleh mendaftarkan plat yang sama dua kali.
            // Tidak unik global: mobil bekas bisa berpindah tangan.
            $table->unique(['user_id', 'plate_full']);
            $table->index(['user_id', 'is_primary']);
            $table->index('plate_full');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
