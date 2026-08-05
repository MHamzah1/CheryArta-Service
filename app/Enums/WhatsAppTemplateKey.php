<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tujuh kunci template pesan WhatsApp (docs/08 §8.4).
 *
 * Himpunan ini ditentukan KODE, bukan data: setiap kunci punya pemicunya
 * sendiri di dalam aplikasi. Baris `whatsapp_templates` yang isinya bisa
 * disunting Super Admin hanyalah teksnya — kuncinya tidak bisa ditambah
 * maupun dihapus dari layar (keputusan grill Q5).
 *
 * Karena itu daftar kunci TIDAK lagi hidup di `config/whatsapp.php`: satu
 * himpunan, satu tempat. Menaruhnya di dua tempat persis pola cacat B2 sistem
 * lama (docs/01 §1.3).
 *
 * `booking_reminder`, bukan `reminder_h1` (keputusan grill Q4a): "H-1" adalah
 * aturan bisnis yang hidup di `config/booking.php`, dan menyalinnya ke dalam
 * nama kunci membuat namanya berbohong begitu aturannya bergeser.
 */
enum WhatsAppTemplateKey: string
{
    case BookingCreated = 'booking_created';
    case BookingConfirmed = 'booking_confirmed';
    case BookingInProgress = 'booking_in_progress';
    case BookingCompleted = 'booking_completed';
    case BookingCancelled = 'booking_cancelled';
    case BookingNoShow = 'booking_no_show';
    case BookingReminder = 'booking_reminder';

    /** Label di panel admin — juga nilai awal kolom `name` di seeder. */
    public function label(): string
    {
        return match ($this) {
            self::BookingCreated => 'Booking Diterima',
            self::BookingConfirmed => 'Booking Dikonfirmasi',
            self::BookingInProgress => 'Sedang Dikerjakan',
            self::BookingCompleted => 'Servis Selesai',
            self::BookingCancelled => 'Booking Dibatalkan',
            self::BookingNoShow => 'Tidak Hadir',
            self::BookingReminder => 'Pengingat Servis',
        };
    }

    /** Kapan pesan ini dipakai — keterangan di layar penyunting. */
    public function trigger(): string
    {
        return match ($this) {
            self::BookingCreated => 'Booking baru masuk dan belum dikonfirmasi',
            self::BookingConfirmed => 'Status berubah menjadi Dikonfirmasi',
            self::BookingInProgress => 'Status berubah menjadi Sedang Dikerjakan',
            self::BookingCompleted => 'Status berubah menjadi Selesai',
            self::BookingCancelled => 'Status berubah menjadi Dibatalkan',
            self::BookingNoShow => 'Status berubah menjadi Tidak Hadir',
            self::BookingReminder => 'Dikirim manual sehari sebelum jadwal servis',
        };
    }

    /** Template yang dipakai untuk sebuah status booking. */
    public static function forStatus(BookingStatus $status): self
    {
        return match ($status) {
            BookingStatus::Pending => self::BookingCreated,
            BookingStatus::Confirmed => self::BookingConfirmed,
            BookingStatus::InProgress => self::BookingInProgress,
            BookingStatus::Completed => self::BookingCompleted,
            BookingStatus::Cancelled => self::BookingCancelled,
            BookingStatus::NoShow => self::BookingNoShow,
        };
    }

    /**
     * Apakah tidak adanya pesan untuk kunci ini dihitung sebagai tunggakan di
     * kartu dashboard (keputusan grill Q6).
     *
     * `booking_created` bersifat sukarela — booking yang belum dikonfirmasi
     * belum menjanjikan apa pun kepada pelanggan, jadi tidak mengabarinya bukan
     * kelalaian. `booking_reminder` juga sukarela dan tidak terikat perubahan
     * status mana pun.
     */
    public function isTracked(): bool
    {
        return ! in_array($this, [self::BookingCreated, self::BookingReminder], true);
    }

    /**
     * Status booking yang ketiadaan kabarnya ditagih dashboard.
     *
     * @return list<string>
     */
    public static function trackedStatusValues(): array
    {
        return array_values(array_map(
            fn (BookingStatus $status): string => $status->value,
            array_filter(
                BookingStatus::cases(),
                fn (BookingStatus $status): bool => self::forStatus($status)->isTracked(),
            ),
        ));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
