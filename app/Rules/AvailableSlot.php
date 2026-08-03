<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\SlotService;
use Closure;
use Exception;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Jam booking: harus salah satu slot yang berlaku pada tanggal itu, dan
 * kuotanya masih tersisa (docs/05 §5.8).
 *
 * Ini lapis PERTAMA — pesannya ramah dan menyebut jam pengganti, tetapi tidak
 * mengunci apa pun. Penentu akhirnya ada di BookingService, di dalam transaksi
 * dengan baris slot terkunci; slot bisa saja direbut orang lain di antara
 * keduanya (docs/04 §4.2).
 */
class AvailableSlot implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param  int|null  $ignoreBookingId  Booking yang sedang dijadwal ulang; slotnya sendiri tidak dihitung menghalangi.
     */
    public function __construct(
        private readonly SlotService $slots,
        private readonly ?int $ignoreBookingId = null,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tanggal = $this->data['booking_date'] ?? null;

        // Tanggalnya sendiri sudah ditolak BookableDate; menambah pesan kedua
        // di sini hanya membuat form berteriak dua kali untuk satu kesalahan.
        if (! is_string($tanggal) || $tanggal === '' || ! is_string($value)) {
            return;
        }

        try {
            $date = $this->slots->parseDate($tanggal);
        } catch (Exception) {
            return;
        }

        if (! $this->slots->isValidTimeOn($date, $value)) {
            $tersedia = $this->slots->slotsOn($date);

            $fail($tersedia === []
                ? 'Tidak ada jam servis pada tanggal tersebut.'
                : 'Jam tersebut tidak tersedia pada hari itu. Pilihan yang ada: '.implode(', ', $tersedia).'.');

            return;
        }

        if ($this->slots->remaining($date, $value, $this->ignoreBookingId) > 0) {
            return;
        }

        $sisa = $this->slots->openTimesOn($date, $this->ignoreBookingId);

        $fail($sisa === []
            ? 'Jam tersebut sudah penuh, dan tidak ada jam lain yang tersisa pada hari itu.'
            : 'Jam tersebut sudah penuh. Jam yang masih tersedia: '.implode(', ', $sisa).'.');
    }
}
