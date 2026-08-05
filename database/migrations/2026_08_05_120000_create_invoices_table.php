<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Invoice satu booking (docs/04 §4.2, PRD F1).
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // SENGAJA BUKAN unique, berbeda dari docs/04 §4.2 versi awal
            // (keputusan grill Q1). Kolom unik membuat janji docs/05 §5.6 —
            // invoice salah "di-void lalu dibuat ulang" — mustahil dijalankan:
            // baris void-nya tidak boleh dihapus (docs/04 §4.5), sehingga
            // penggantinya selalu ditolak database dengan 500 "Duplicate entry"
            // di layar advisor.
            //
            // Aturan "maksimal satu invoice non-void per booking" karena itu
            // ditegakkan App\Services\InvoiceService di dalam transaksi dengan
            // baris BOOKING-nya terkunci — baris invoice yang belum ada tidak
            // bisa dikunci, pola yang sama dengan kuota slot di F1.4.
            //
            // restrictOnDelete: booking hanya di-soft delete, dan invoice tidak
            // boleh kehilangan induknya bila kelak ada penghapusan sungguhan.
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();

            // Nullable sampai diterbitkan: draft yang tidak pernah terbit tidak
            // boleh menghabiskan nomor urut (PRD F14).
            $table->string('invoice_number', 25)->nullable()->unique();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // App\Enums\InvoiceStatus: draft | issued | paid | void.
            // varchar + PHP Enum, bukan ENUM MySQL (.claude/rules/30).
            $table->string('status', 15)->default('draft');

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // cash | transfer | edc — pencatatan saja, tanpa payment gateway.
            $table->string('payment_method', 30)->nullable();

            // TIDAK ada di docs/04 §4.2, ditambahkan di sini (PRD F2): docs/07
            // §A8 mewajibkan alasan saat void, dan menaruhnya di `notes` akan
            // menimpa catatan advisor yang sudah ada.
            $table->string('void_reason', 255)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // TANPA softDeletes: invoice tidak pernah dihapus, dibatalkan lewat
            // status `void` (docs/04 §4.5).

            // Dipakai relasi "invoice aktif" di detail booking.
            $table->index('booking_id');
            // Dipakai laporan Pendapatan (2.4.7) yang mengelompokkan issued_at.
            $table->index(['status', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
