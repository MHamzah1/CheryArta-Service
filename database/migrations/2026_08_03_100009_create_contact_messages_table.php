<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pesan dari form kontak publik. Selama Big Fase 1 belum ada layar
        // untuk membacanya — A10 dibangun di F2.4, sampai saat itu isinya
        // dilihat lewat DBeaver (docs/10 F1.6).
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 150);

            // Ternormalisasi 62… lewat App\Support\PhoneNumber sebelum
            // disimpan, sama seperti users.phone_wa.
            $table->string('phone', 20)->nullable();

            $table->string('subject', 160);
            $table->text('message');

            $table->boolean('is_read')->default(false);

            // Staf yang menandai terbaca. nullOnDelete supaya menghapus akun
            // staf tidak ikut melenyapkan pesan pelanggan.
            $table->foreignId('read_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('read_at')->nullable();

            // Untuk penanganan spam pada form publik.
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->index(['is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
