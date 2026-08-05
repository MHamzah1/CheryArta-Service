<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\SlotService;
use App\Support\ReportPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Saringan halaman laporan (docs/07 §A9, PRD F2.1 §F).
 *
 * Rentang kustom divalidasi batas ATASNYA, bukan hanya urutannya. Tanpa itu,
 * satu tautan berisi `dari=1970-01-01` memaksa pemindaian seluruh tabel
 * bookings — dan tautan seperti itu tidak perlu niat jahat untuk lahir, cukup
 * salah ketik.
 */
class ReportFilterRequest extends FormRequest
{
    /** Otorisasinya ada di controller (`viewReports`) — lihat Admin\ReportController. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'periode' => ['nullable', Rule::in(ReportPeriod::PRESETS)],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:dari',
                'before_or_equal:'.$this->batasAkhir(),
            ],
            'tab' => ['nullable', Rule::in(self::TABS)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $maks = Config::integer('booking.reports.max_range_days');

        return [
            'periode.in' => 'Pilihan periode tidak dikenali.',
            'dari.date_format' => 'Tanggal mulai tidak dikenali.',
            'sampai.date_format' => 'Tanggal akhir tidak dikenali.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal daripada tanggal mulai.',
            'sampai.before_or_equal' => "Rentang laporan paling panjang {$maks} hari. Persempit tanggalnya.",
            'tab.in' => 'Tab laporan tidak dikenali.',
        ];
    }

    /**
     * `pendapatan` ADA di daftar ini meski tab-nya khusus Super Admin.
     *
     * Yang ditolak untuk advisor adalah DATANYA, di ReportController lewat
     * `viewRevenueReport` — bukan bentuk query stringnya. Membuang nilainya di
     * sini akan mengubah percobaan advisor menjadi "tab tidak dikenali", yaitu
     * pesan yang salah untuk penolakan yang sebenarnya soal wewenang.
     *
     * @var list<string>
     */
    public const TABS = ['rekap', 'okupansi', 'customer', 'pendapatan'];

    public function period(SlotService $slots): ReportPeriod
    {
        return ReportPeriod::fromValidated($this->validated(), $slots->today());
    }

    public function tab(): string
    {
        $tab = $this->validated('tab');

        return is_string($tab) && in_array($tab, self::TABS, true) ? $tab : self::TABS[0];
    }

    /**
     * Batas akhir rentang kustom, dihitung dari tanggal mulai yang dikirim.
     *
     * Ditulis sebagai aturan `before_or_equal` alih-alih pemeriksaan manual
     * supaya pesannya muncul menempel pada field-nya, bukan sebagai galat
     * umum di atas form.
     */
    private function batasAkhir(): string
    {
        $dari = $this->input('dari');
        $maks = Config::integer('booking.reports.max_range_days');

        if (! is_string($dari) || $dari === '') {
            // Tanpa tanggal mulai, batas atasnya tidak bisa dihitung — aturan
            // urutan tanggal yang akan menolaknya lebih dulu.
            return '9999-12-31';
        }

        try {
            return \Carbon\CarbonImmutable::parse($dari)->addDays($maks - 1)->toDateString();
        } catch (Throwable) {
            return '9999-12-31';
        }
    }
}
