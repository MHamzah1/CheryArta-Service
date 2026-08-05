<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Teks pesan WhatsApp yang bisa disunting Super Admin tanpa deploy
        // (docs/04 §4.2, docs/08 §8.4). Menggantikan `config('company.wa_messages')`
        // yang dihapus pada perubahan yang sama — dua sumber teks yang hidup
        // bersamaan adalah pola cacat B2 sistem lama.
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();

            // Nilainya dibatasi App\Enums\WhatsAppTemplateKey. varchar + enum
            // PHP, bukan tipe ENUM MySQL (.claude/rules/30): menambah kunci
            // kelak tidak boleh menuntut ALTER TABLE.
            $table->string('key', 50)->unique();

            $table->string('name', 100);
            $table->text('body');

            // false berarti "jangan tawarkan draft untuk pemicu ini"
            // (keputusan grill Q5) — bukan "pakai teks bawaan diam-diam".
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
