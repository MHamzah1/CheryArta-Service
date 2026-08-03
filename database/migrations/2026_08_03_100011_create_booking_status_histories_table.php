<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Timeline yang dilihat customer sekaligus jejak audit — kebutuhan
        // yang sama sekali tidak terpenuhi sistem lama (docs/04 §4.2).
        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            // Null saat booking pertama kali dibuat: tidak ada status asal.
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);

            // Null bila perubahannya dilakukan sistem, bukan manusia.
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();

            // Catatan yang TERLIHAT customer di timeline.
            $table->string('note', 255)->nullable();

            // Tanpa updated_at: baris audit tidak pernah diubah, hanya ditulis
            // sekali (.claude/rules/30-database.md melarang menghapusnya juga).
            $table->timestamp('created_at')->nullable();

            $table->index(['booking_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_histories');
    }
};
