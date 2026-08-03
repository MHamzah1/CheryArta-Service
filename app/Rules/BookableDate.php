<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\BookingSource;
use App\Services\SlotService;
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Tanggal booking: bukan hari tutup, tidak melanggar H-1, tidak melewati batas
 * 60 hari (docs/05 §5.8).
 *
 * Ketiganya digabung dalam satu Rule karena semuanya menyoroti kolom yang sama
 * dan hanya satu pesan yang perlu tampil — pengguna yang memilih hari Minggu
 * tidak terbantu oleh tambahan "dan juga terlalu jauh ke depan".
 *
 * Aturannya sendiri tidak ada di sini: kelas ini hanya menanyakannya kepada
 * SlotService, yang membacanya dari `config/booking.php`.
 */
class BookableDate implements ValidationRule
{
    public function __construct(
        private readonly SlotService $slots,
        private readonly BookingSource $source = BookingSource::Web,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Pilih tanggal servis terlebih dahulu.');

            return;
        }

        try {
            $date = $this->slots->parseDate($value);
        } catch (Exception) {
            $fail('Tanggal yang dipilih tidak dikenali.');

            return;
        }

        $alasan = $this->slots->dateRejectionReason($date, $this->source);

        if ($alasan !== null) {
            $fail($alasan);
        }
    }
}
