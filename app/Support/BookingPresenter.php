<?php

declare(strict_types=1);

namespace App\Support;

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
    public static function slotRules(SlotService $slots): array
    {
        return [
            'earliest_date' => $slots->earliestDate()->toDateString(),
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
