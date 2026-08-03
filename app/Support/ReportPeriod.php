<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Throwable;

/**
 * Rentang tanggal laporan (docs/07 §A9, PRD F2.1 keputusan #8).
 *
 * SATU-SATUNYA tempat arti "30 hari" dan "bulan lalu" ditentukan. Preset yang
 * sama dipakai tiga kali: menyusun kueri, mengisi ulang pemilih periode di
 * React, dan menempel di query string agar tautannya bisa dibagikan. Ditulis
 * ulang di tiap tempat, ketiganya akan berbeda cepat atau lambat — persis cara
 * cacat B2 sistem lama lahir.
 *
 * Objek nilai, bukan service: ia tidak menyentuh database dan tidak butuh
 * apa pun selain tanggal "hari ini" yang diberikan pemanggilnya.
 */
final readonly class ReportPeriod
{
    public const PRESET_7_HARI = '7_hari';

    public const PRESET_30_HARI = '30_hari';

    public const PRESET_BULAN_INI = 'bulan_ini';

    public const PRESET_BULAN_LALU = 'bulan_lalu';

    public const PRESET_KUSTOM = 'kustom';

    /** @var list<string> */
    public const PRESETS = [
        self::PRESET_7_HARI,
        self::PRESET_30_HARI,
        self::PRESET_BULAN_INI,
        self::PRESET_BULAN_LALU,
        self::PRESET_KUSTOM,
    ];

    private function __construct(
        public string $preset,
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    /**
     * Bangun dari nilai yang SUDAH divalidasi ReportFilterRequest.
     *
     * Preset yang tidak dikenal jatuh ke bawaan alih-alih melempar: nilainya
     * datang dari query string yang bisa disunting siapa pun, dan halaman
     * laporan yang gagal total karena satu huruf salah lebih buruk daripada
     * halaman yang menampilkan periode bawaan.
     */
    public static function fromValidated(array $validated, CarbonImmutable $today): self
    {
        $preset = is_string($validated['periode'] ?? null) ? $validated['periode'] : '';

        if (! in_array($preset, self::PRESETS, true)) {
            $preset = Config::string('booking.reports.default_preset');
        }

        if ($preset === self::PRESET_KUSTOM) {
            $dari = self::tanggal($validated['dari'] ?? null, $today);
            $sampai = self::tanggal($validated['sampai'] ?? null, $today);

            // Rentang kustom tanpa tanggal bukan galat — ia hanya belum diisi.
            // Jatuhkan ke bawaan supaya halamannya tetap menampilkan sesuatu.
            if ($dari === null || $sampai === null) {
                return self::dariPreset(Config::string('booking.reports.default_preset'), $today);
            }

            return new self(self::PRESET_KUSTOM, $dari->startOfDay(), $sampai->startOfDay());
        }

        return self::dariPreset($preset, $today);
    }

    public static function dariPreset(string $preset, CarbonImmutable $today): self
    {
        [$start, $end] = match ($preset) {
            self::PRESET_7_HARI => [$today->subDays(6), $today],
            self::PRESET_BULAN_INI => [$today->startOfMonth(), $today],
            self::PRESET_BULAN_LALU => [
                $today->subMonthNoOverflow()->startOfMonth(),
                $today->subMonthNoOverflow()->endOfMonth(),
            ],
            // 30 hari dan preset apa pun yang tidak dikenal.
            default => [$today->subDays(29), $today],
        };

        return new self(
            in_array($preset, self::PRESETS, true) ? $preset : self::PRESET_30_HARI,
            $start->startOfDay(),
            $end->startOfDay(),
        );
    }

    /** Jumlah hari dalam rentang, termasuk hari pertama dan terakhir. */
    public function dayCount(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    public function startDate(): string
    {
        return $this->start->toDateString();
    }

    public function endDate(): string
    {
        return $this->end->toDateString();
    }

    /**
     * Bentuk yang dikirim balik ke React untuk mengisi ulang pemilih periode
     * dan menyusun ulang query string.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'periode' => $this->preset,
            'dari' => $this->startDate(),
            'sampai' => $this->endDate(),
            'jumlah_hari' => $this->dayCount(),
        ];
    }

    /**
     * Pilihan preset siap tampil. Labelnya hidup di sini, bukan di React —
     * label dan makna preset harus berubah bersamaan.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => self::PRESET_7_HARI, 'label' => '7 Hari Terakhir'],
            ['value' => self::PRESET_30_HARI, 'label' => '30 Hari Terakhir'],
            ['value' => self::PRESET_BULAN_INI, 'label' => 'Bulan Ini'],
            ['value' => self::PRESET_BULAN_LALU, 'label' => 'Bulan Lalu'],
            ['value' => self::PRESET_KUSTOM, 'label' => 'Rentang Kustom'],
        ];
    }

    private static function tanggal(mixed $value, CarbonImmutable $today): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            // Zona waktunya diambil dari `$today` supaya seluruh perhitungan
            // laporan hidup di Asia/Jakarta, bukan di UTC (temuan B7).
            return CarbonImmutable::parse($value, $today->getTimezone());
        } catch (Throwable) {
            return null;
        }
    }
}
