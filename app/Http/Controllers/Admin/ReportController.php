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
 * Laporan **Pendapatan tidak ada di sini** — tabel `invoices` baru lahir di
 * F2.4 (keputusan grill #1). Tab-nya tidak dirender sama sekali, bukan
 * ditampilkan lalu dinonaktifkan: menu yang mengantar ke halaman kosong
 * membuat orang menyangka aplikasinya rusak.
 */
class ReportController extends Controller
{
    public function __invoke(ReportFilterRequest $request, ReportService $reports, SlotService $slots): Response
    {
        Gate::authorize('viewReports', Booking::class);

        $period = $request->period($slots);

        return Inertia::render('admin/laporan/index', [
            'period' => $period->toArray(),
            'periodOptions' => ReportPeriod::options(),
            'tab' => $request->tab(),
            'recap' => $reports->bookingRecap($period),
            'occupancy' => $reports->occupancy($period),
            'newCustomers' => $reports->newCustomers($period),
        ]);
    }
}
