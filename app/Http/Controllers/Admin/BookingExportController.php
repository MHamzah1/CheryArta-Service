<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingFilterRequest;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\Booking;
use App\Services\ReportService;
use App\Services\SlotService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export daftar booking (docs/07 §A9), dipakai DUA layar.
 *
 * Sistem lama punya dua tombol: unduh Excel dan "Salin untuk Spreadsheet".
 * Keduanya dipertahankan, dengan kolom yang sama persis, supaya berkas
 * hasilnya tetap bisa ditempel ke spreadsheet yang sudah dipakai bengkel.
 *
 * **Kenapa CSV, bukan .xlsx.** `maatwebsite/excel` menuntut PhpSpreadsheet dan
 * ekstensi PHP `zip`; ketersediaannya di runtime Railway belum terbukti, dan
 * kegagalan `composer install` menjatuhkan SELURUH deploy, bukan hanya fitur
 * export. CSV ber-BOM UTF-8 dibuka Excel secara langsung — termasuk nama
 * pelanggan beraksen — tanpa satu pun dependensi baru. Bila kelak `.xlsx`
 * sungguhan dibutuhkan, yang berubah hanya berkas ini.
 *
 * **Kenapa `tsv` mengembalikan teks biasa.** Tombol "Salin untuk Spreadsheet"
 * menyalin SELURUH hasil saringan, bukan 25 baris halaman berjalan, sehingga
 * datanya memang harus diambil dari server. Ini aksi yang dipicu pengguna,
 * bukan data halaman — larangan `fetch` di .claude/rules/20 menyasar yang
 * kedua.
 */
class BookingExportController extends Controller
{
    /** Export dari daftar booking (A2) — mengikuti saringan di layar itu. */
    public function bookings(BookingFilterRequest $request, ReportService $reports): StreamedResponse|HttpResponse
    {
        Gate::authorize('viewReports', Booking::class);

        $query = Booking::query()->tap($request->filters()->apply(...));

        return $this->keluarkan($query, $reports, $request->string('format')->toString(), 'booking');
    }

    /** Export dari halaman laporan (A9) — mengikuti periode yang sedang dipilih. */
    public function reports(ReportFilterRequest $request, ReportService $reports, SlotService $slots): StreamedResponse|HttpResponse
    {
        Gate::authorize('viewReports', Booking::class);

        $period = $request->period($slots);

        $query = Booking::query()
            ->whereBetween('booking_date', [$period->startDate(), $period->endDate()])
            ->orderBy('booking_date')
            ->orderBy('booking_time')
            ->orderBy('id');

        return $this->keluarkan(
            $query,
            $reports,
            $request->string('format')->toString(),
            "laporan-{$period->startDate()}-sd-{$period->endDate()}",
        );
    }

    /**
     * @param  Builder<Booking>  $query
     */
    private function keluarkan(Builder $query, ReportService $reports, string $format, string $namaDasar): StreamedResponse|HttpResponse
    {
        return $format === 'tsv'
            ? $this->tsv($query, $reports)
            : $this->csv($query, $reports, $namaDasar);
    }

    /**
     * Unduhan CSV yang dialirkan baris demi baris.
     *
     * BOM UTF-8 di awal berkas adalah yang membuat Excel di Windows membaca
     * huruf beraksen dengan benar. Tanpa itu "Müller" menjadi "MÃ¼ller", dan
     * yang disalahkan biasanya datanya, bukan berkasnya.
     *
     * @param  Builder<Booking>  $query
     */
    private function csv(Builder $query, ReportService $reports, string $namaDasar): StreamedResponse
    {
        $nama = "{$namaDasar}-".now(config('booking.timezone'))->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query, $reports): void {
            $keluaran = fopen('php://output', 'wb');

            if ($keluaran === false) {
                return;
            }

            fwrite($keluaran, "\xEF\xBB\xBF");

            foreach ($reports->exportRows($query) as $baris) {
                fputcsv($keluaran, $baris, ',', '"', '\\');
            }

            fclose($keluaran);
        }, $nama, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            // Berkas ini memuat nama, telepon, dan plat pelanggan — jangan
            // sampai tersimpan di cache perantara mana pun (docs/09 §9.7).
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Muatan TSV untuk clipboard — teks biasa, bukan unduhan.
     *
     * @param  Builder<Booking>  $query
     */
    private function tsv(Builder $query, ReportService $reports): HttpResponse
    {
        $baris = [];

        foreach ($reports->exportRows($query) as $isi) {
            // Tab dan baris baru di dalam sel akan merusak struktur TSV —
            // keluhan pelanggan adalah kolom yang paling mungkin memuatnya.
            $baris[] = implode("\t", array_map(
                static fn (string $sel): string => str_replace(["\t", "\r\n", "\r", "\n"], ' ', $sel),
                $isi,
            ));
        }

        return response(implode("\n", $baris), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
