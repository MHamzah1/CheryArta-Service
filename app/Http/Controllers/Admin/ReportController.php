<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\Booking;
use App\Services\ReportService;
use App\Services\SlotService;
use App\Support\ReportPeriod;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A9 — Laporan (docs/07 §A9, roadmap 2.1.4).
 *
 * Satu rute dengan tab di dalamnya, bukan empat rute terpisah (keputusan
 * grill #7): pemilih periode dipakai bersama seluruh tab, dan empat rute
 * berarti menyalinnya empat kali.
 *
 * Laporan **Pendapatan** ditambahkan di F2.4 sebagai tab keempat. Ia
 * satu-satunya yang dibatasi Super Admin (docs/09 §9.3), dan pembatasannya
 * bekerja dengan **tidak mengirim datanya sama sekali** kepada advisor —
 * bukan mengirim angka lalu menyembunyikan tabnya di React. Prop `revenue`
 * bernilai `null` bagi advisor, dan halaman yang tidak menerimanya tidak
 * merender tabnya.
 */
class ReportController extends Controller
{
    public function __invoke(ReportFilterRequest $request, ReportService $reports, SlotService $slots): Response
    {
        Gate::authorize('viewReports', Booking::class);

        $period = $request->period($slots);
        $bolehPendapatan = Gate::allows('viewRevenueReport', Booking::class);

        return Inertia::render('admin/laporan/index', [
            'period' => $period->toArray(),
            'periodOptions' => ReportPeriod::options(),
            // Advisor yang mengetik `?tab=pendapatan` dikembalikan ke tab
            // pertama — bukan dijawab 403. Ia memang berhak membuka halaman
            // laporan; yang tidak ada baginya hanyalah tab itu.
            'tab' => $bolehPendapatan ? $request->tab() : $this->tabTanpaPendapatan($request->tab()),
            'recap' => $reports->bookingRecap($period),
            'occupancy' => $reports->occupancy($period),
            'newCustomers' => $reports->newCustomers($period),
            'revenue' => $bolehPendapatan ? $reports->revenue($period) : null,
        ]);
    }

    private function tabTanpaPendapatan(string $tab): string
    {
        return $tab === 'pendapatan' ? ReportFilterRequest::TABS[0] : $tab;
    }
}
