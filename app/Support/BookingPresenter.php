<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BookingSource;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\ServicePackage;
use App\Models\Vehicle;
use App\Services\SlotService;

/**
 * Bentuk props booking untuk Inertia.
 *
 * Dipusatkan supaya satu booking tampil dengan bentuk yang sama di halaman
 * sukses, detail, dan riwayat — dan supaya sulit tanpa sengaja mengirim kolom
 * yang tidak boleh dilihat customer (`admin_note`) atau tidak boleh dilihat
 * publik sama sekali (nama, telepon, keluhan — temuan S8).
 *
 * Label status TIDAK ada di sini: peta status→label & warna hanya hidup di
 * `resources/js/lib/status.ts` (.claude/rules/40-ui-design-system.md).
 */
final class BookingPresenter
{
    /** @return array<string, mixed> */
    public static function vehicleOption(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'plate_full' => $vehicle->plate_full,
            'display_model' => $vehicle->display_model,
            'year' => $vehicle->year,
            'last_odometer' => $vehicle->last_odometer,
            'is_primary' => $vehicle->is_primary,
            // Dipakai antarmuka untuk menyorot paket yang cocok; server tetap
            // tidak menjadikannya syarat (docs/05 §5.8).
            'series_code' => $vehicle->carModel?->series_code,
        ];
    }

    /** @return array<string, mixed> */
    public static function packageOption(ServicePackage $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'description' => $package->description,
            'applicable_series' => $package->applicable_series ?? [],
            'estimated_duration_minutes' => $package->estimated_duration_minutes,
            'price' => $package->price,
            'is_free' => $package->is_free,
        ];
    }

    /**
     * Batas-batas yang dipakai kalender di browser.
     *
     * Nilainya berasal dari SlotService, bukan ditulis ulang di React — angka
     * aturan booking hanya boleh hidup di `config/booking.php` (temuan B2).
     *
     * @return array<string, mixed>
     */
    public static function slotRules(SlotService $slots, BookingSource $source = BookingSource::Web): array
    {
        return [
            // Walk-in boleh hari ini; web paling cepat besok. Bedanya dihitung
            // SlotService dari config, bukan dengan `if` di sini (temuan B2).
            'earliest_date' => $slots->earliestDate($source)->toDateString(),
            'source' => $source->value,
            'latest_date' => $slots->latestDate()->toDateString(),
            'closed_weekdays' => array_values(config('booking.closed_weekdays')),
            'quota_per_slot' => (int) config('booking.quota_per_slot'),
        ];
    }

    /** Booking lengkap untuk pemiliknya. @return array<string, mixed> */
    public static function detail(Booking $booking): array
    {
        return [
            ...self::summary($booking),
            'complaint' => $booking->complaint,
            'odometer' => $booking->odometer,
            'cancel_reason' => $booking->cancel_reason,
            'source' => $booking->source->value,
            'package_description' => $booking->servicePackage->description,
            'package_duration_minutes' => $booking->servicePackage->estimated_duration_minutes,
            'rescheduled_from' => $booking->rescheduledFrom === null ? null : [
                'booking_code' => $booking->rescheduledFrom->booking_code,
                'booking_date' => $booking->rescheduledFrom->booking_date->toDateString(),
                'booking_time' => SlotTime::short($booking->rescheduledFrom->booking_time),
            ],
        ];
    }

    /** Bentuk ringkas untuk daftar riwayat. @return array<string, mixed> */
    public static function summary(Booking $booking): array
    {
        return [
            'booking_code' => $booking->booking_code,
            'booking_date' => $booking->booking_date->toDateString(),
            'booking_time' => SlotTime::short($booking->booking_time),
            'status' => $booking->status->value,
            'estimated_finish_at' => $booking->estimated_finish_at?->toIso8601String(),
            'vehicle_plate' => $booking->vehicle->plate_full,
            'vehicle_model' => $booking->vehicle->display_model,
            'package_name' => $booking->servicePackage->name,
            'package_price' => $booking->servicePackage->price,
            'package_is_free' => $booking->servicePackage->is_free,
        ];
    }

    /**
     * Baris daftar booking di panel admin (docs/07 §A2).
     *
     * Memuat nama pelanggan dan plat — keduanya WAJIB ada di sini karena itulah
     * yang dicari advisor, dan sama-sama TERLARANG di `publicTracking()`.
     * Kedua bentuk sengaja berdiri berdampingan di berkas ini supaya bedanya
     * terlihat saat dibaca, bukan tersembunyi di dua controller berbeda.
     *
     * @return array<string, mixed>
     */
    public static function adminRow(Booking $booking): array
    {
        return [
            'booking_code' => $booking->booking_code,
            'booking_date' => $booking->booking_date->toDateString(),
            'booking_time' => SlotTime::short($booking->booking_time),
            'status' => $booking->status->value,
            'source' => $booking->source->value,
            'source_label' => $booking->source->label(),
            'customer_name' => $booking->user->name,
            'vehicle_plate' => $booking->vehicle->plate_full,
            'vehicle_model' => $booking->vehicle->display_model,
            'package_name' => $booking->servicePackage->name,
            'handled_by_name' => $booking->handledBy?->name,
        ];
    }

    /**
     * Detail booking untuk advisor.
     *
     * Bedanya dari `detail()` milik customer: memuat `admin_note`, identitas
     * dan nomor WhatsApp pelanggan, serta penanda waktu tiap tahap. Nomor
     * WhatsApp hanya boleh terlihat oleh staf yang login (docs/09 §9.7).
     *
     * @return array<string, mixed>
     */
    public static function adminDetail(Booking $booking): array
    {
        return [
            ...self::adminRow($booking),
            'complaint' => $booking->complaint,
            'odometer' => $booking->odometer,
            'admin_note' => $booking->admin_note,
            'cancel_reason' => $booking->cancel_reason,
            'estimated_finish_at' => $booking->estimated_finish_at?->toIso8601String(),
            'customer' => [
                'id' => $booking->user->getKey(),
                'name' => $booking->user->name,
                'email' => $booking->user->email,
                'phone_wa' => $booking->user->phone_wa,
                'is_active' => $booking->user->is_active,
            ],
            'vehicle' => [
                'id' => $booking->vehicle->getKey(),
                'plate_full' => $booking->vehicle->plate_full,
                'display_model' => $booking->vehicle->display_model,
                'year' => $booking->vehicle->year,
                'last_odometer' => $booking->vehicle->last_odometer,
            ],
            'package_price' => $booking->servicePackage->price,
            'package_is_free' => $booking->servicePackage->is_free,
            'package_duration_minutes' => $booking->servicePackage->estimated_duration_minutes,
            'confirmed_at' => $booking->confirmed_at?->toIso8601String(),
            'started_at' => $booking->started_at?->toIso8601String(),
            'completed_at' => $booking->completed_at?->toIso8601String(),
            'cancelled_at' => $booking->cancelled_at?->toIso8601String(),
            'created_at' => $booking->created_at?->toIso8601String(),
            'rescheduled_from' => $booking->rescheduledFrom === null ? null : [
                'booking_code' => $booking->rescheduledFrom->booking_code,
                'booking_date' => $booking->rescheduledFrom->booking_date->toDateString(),
                'booking_time' => SlotTime::short($booking->rescheduledFrom->booking_time),
            ],
        ];
    }

    /**
     * Kartu booking di dalam sel jadwal harian (docs/07 §A3).
     *
     * Sengaja pendek: satu sel selebar setengah kolom hanya sanggup memuat
     * kode, pemilik, unit, dan status.
     *
     * @return array<string, mixed>
     */
    public static function scheduleCard(Booking $booking): array
    {
        return [
            'booking_code' => $booking->booking_code,
            'status' => $booking->status->value,
            'customer_name' => $booking->user->name,
            'vehicle_plate' => $booking->vehicle->plate_full,
            'vehicle_model' => $booking->vehicle->display_model,
            'package_name' => $booking->servicePackage->name,
        ];
    }

    /** @return array<string, mixed> */
    public static function timelineEntry(BookingStatusHistory $history): array
    {
        return [
            'id' => $history->id,
            'from_status' => $history->from_status?->value,
            'to_status' => $history->to_status->value,
            'note' => $history->note,
            'created_at' => $history->created_at?->toIso8601String(),
        ];
    }

    /**
     * Timeline versi advisor: sama persis, ditambah siapa pelakunya.
     *
     * Nama pelaku sengaja TIDAK ikut di `timelineEntry()`. Pelanggan tidak
     * perlu tahu advisor mana yang menekan tombol, dan menambahkannya di sana
     * berarti nama staf ikut terkirim ke setiap halaman detail customer tanpa
     * ada yang menyadarinya.
     *
     * @return array<string, mixed>
     */
    public static function adminTimelineEntry(BookingStatusHistory $history): array
    {
        return [
            ...self::timelineEntry($history),
            'actor_name' => $history->changedBy?->name,
        ];
    }

    /**
     * Pelacakan publik tanpa login (docs/05 §5.7).
     *
     * HANYA kode, jadwal, status, dan perkiraan selesai. Tidak ada nama,
     * nomor telepon, plat, atau keluhan — fitur "Cek Service" lama membiarkan
     * siapa pun mengambil seluruh basis data lalu menyaringnya di browser
     * (temuan S8), dan bentuk inilah penggantinya.
     *
     * @return array<string, mixed>
     */
    public static function publicTracking(Booking $booking): array
    {
        return [
            'booking_code' => $booking->booking_code,
            'booking_date' => $booking->booking_date->toDateString(),
            'booking_time' => SlotTime::short($booking->booking_time),
            'status' => $booking->status->value,
            'estimated_finish_at' => $booking->estimated_finish_at?->toIso8601String(),
        ];
    }
}
