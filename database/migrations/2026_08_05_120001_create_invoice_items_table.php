<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rincian invoice (docs/04 §4.2, PRD F3).
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            // cascadeOnDelete aman di sini justru karena invoice-nya sendiri
            // tidak pernah dihapus (docs/04 §4.5) — baris ini tidak punya
            // makna apa pun di luar invoicenya.
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            // App\Enums\InvoiceItemType: jasa | part.
            $table->string('type', 10);

            $table->string('description', 200);

            // decimal, bukan integer: 0,5 jam jasa harus bisa ditulis
            // (.claude/rules/30, docs/04 §4.2).
            $table->decimal('qty', 8, 2);
            $table->decimal('unit_price', 12, 2);

            // qty × unit_price, DIHITUNG SERVER. Disimpan karena harga paket
            // dan tarif jasa bisa berubah sesudah invoice terbit — nilai yang
            // dihitung ulang saat dibaca akan mengubah tagihan yang sudah
            // dipegang pelanggan.
            $table->decimal('subtotal', 12, 2);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['invoice_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
