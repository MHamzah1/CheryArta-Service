<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Log klik-to-chat (docs/04 §4.2). Satu baris lahir ketika advisor
        // menekan "Buka WhatsApp" — BUKAN setiap kali status booking berubah
        // (keputusan grill Q1). Tanpa batas itu, aksi cepat dashboard
        // meninggalkan tiga baris per booking dan kartu tunggakan tidak akan
        // pernah bisa kembali ke nol.
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            // Kuncinya disimpan sebagai nilai, bukan FK ke whatsapp_templates:
            // baris ini adalah arsip, dan harus tetap terbaca meski templatenya
            // kelak berubah.
            $table->string('template_key', 50);

            // Bentuk ternormalisasi 62… apa adanya saat pesan dibuat. Nomor
            // pelanggan bisa berubah; yang dicatat adalah ke mana pesan itu
            // benar-benar diarahkan (docs/08 §8.3).
            $table->string('recipient_phone', 20);

            // Isi final. TIDAK pernah dihitung ulang saat dibaca — menyunting
            // template tidak boleh mengubah pesan yang sudah keluar.
            $table->text('rendered_message');

            // nullOnDelete, sama seperti contact_messages.read_by: menghapus
            // akun staf tidak boleh ikut melenyapkan jejak pesannya.
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();

            // App\Enums\WhatsAppMessageStatus: generated | sent | skipped.
            $table->string('status', 15);
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            // Dipakai panel & riwayat di detail booking.
            $table->index(['booking_id', 'template_key']);
            // Dipakai kartu dashboard dan saringan `wa=belum` di daftar booking.
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
