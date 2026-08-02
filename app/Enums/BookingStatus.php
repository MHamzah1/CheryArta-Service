<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * State machine status booking.
 *
 * Menggantikan status sistem lama yang rancu (Successful/Pending/In Progress/
 * Completed/Cancelled) — 'Successful' di sana berarti "booking berhasil masuk",
 * bukan tahapan pekerjaan. Lihat docs/05-alur-bisnis.md §5.3.
 *
 * Pemetaan data lama: Successful → confirmed, Pending → pending,
 * In Progress → in_progress, Completed → completed, Cancelled → cancelled.
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Konfirmasi',
            self::Confirmed => 'Dikonfirmasi',
            self::InProgress => 'Sedang Dikerjakan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::NoShow => 'Tidak Hadir',
        };
    }

    /**
     * Kunci token warna. Peta warna sesungguhnya ada di
     * resources/js/lib/status.ts — satu sumber kebenaran untuk tampilan.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Confirmed => 'confirmed',
            self::InProgress => 'progress',
            self::Completed => 'done',
            self::Cancelled => 'cancel',
            self::NoShow => 'noshow',
        };
    }

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::InProgress, self::Cancelled, self::NoShow],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled, self::NoShow => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /** Status yang masih boleh dijadwal ulang / dibatalkan customer. */
    public function isReschedulable(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    /**
     * Booking dengan status ini melepaskan kuota slotnya kembali.
     * Sistem lama menghitung semua booking termasuk yang batal, sehingga
     * slot bisa "penuh palsu".
     */
    public function releasesQuota(): bool
    {
        return in_array($this, [self::Cancelled, self::NoShow], true);
    }

    /** @return array<int, string> untuk whereNotIn saat menghitung kuota. */
    public static function quotaReleasingValues(): array
    {
        return array_values(array_map(
            fn (self $status) => $status->value,
            array_filter(self::cases(), fn (self $status) => $status->releasesQuota()),
        ));
    }

    /** Status yang dianggap masih berjalan (untuk dashboard & daftar mendatang). */
    public static function activeValues(): array
    {
        return [self::Pending->value, self::Confirmed->value, self::InProgress->value];
    }

    public static function options(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'tone' => $status->tone(),
            ],
            self::cases(),
        );
    }
}
