<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel inti sistem. Lihat docs/04-skema-database.md §4.2 dan
        // docs/05-alur-bisnis.md untuk aturan yang berlaku di atasnya.
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // CA-20260803-0001 — menutup temuan D2 sistem lama yang tidak
            // punya penanda booking sama sekali.
            $table->string('booking_code', 20)->unique();

            // restrictOnDelete: riwayat servis tidak boleh lenyap hanya karena
            // akun atau kendaraannya dihapus. Keduanya memakai soft delete.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_package_id')->constrained()->restrictOnDelete();

            // Tanggal dan jam dipisah supaya kuota per slot bisa dihitung
            // dengan satu index, tanpa fungsi tanggal di klausa WHERE
            // (.claude/rules/30-database.md).
            $table->date('booking_date');
            $table->time('booking_time');

            // String + PHP Enum, bukan tipe ENUM MySQL.
            $table->string('status', 20)->default('pending');
            $table->string('source', 15)->default('web');

            $table->unsignedInteger('odometer')->nullable();
            $table->text('complaint')->nullable();

            // Catatan internal — tidak pernah dikirim ke props customer.
            $table->text('admin_note')->nullable();

            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();

            // Jejak penjadwalan ulang: booking lama dibatalkan, booking baru
            // menunjuk ke sana (docs/05 §5.4).
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('bookings')->nullOnDelete();

            $table->dateTime('estimated_finish_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Perhitungan kuota slot — index terpenting di tabel ini.
            $table->index(['booking_date', 'booking_time']);
            // Daftar & filter admin.
            $table->index(['status', 'booking_date']);
            // Riwayat customer.
            $table->index(['user_id', 'booking_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
