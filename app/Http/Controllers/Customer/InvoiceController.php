<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\InvoiceDocument;
use App\Support\InvoicePresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

/**
 * Invoice yang dilihat pemiliknya (US-C7, roadmap 2.4.4).
 *
 * Invoice diambil LEWAT RELASI `$user->invoices()`, bukan
 * `Invoice::findOrFail()` lalu diperiksa policy belakangan
 * (.claude/rules/50 #2). Bedanya bukan gaya: dengan bentuk ini, invoice milik
 * orang lain tidak pernah terambil sama sekali, sehingga tidak ada jalan bagi
 * satu baris kode yang lupa memanggil policy untuk membocorkannya.
 *
 * Yang terlihat dipatok `issued_at`, bukan status (keputusan grill Q7). Draft
 * dijawab **404**, bukan 403 — 403 mengakui bahwa ada sesuatu di sana, dan
 * draft yang belum diterbitkan bukan urusan pelanggan. Invoice yang pernah
 * terbit lalu di-void TETAP terbuka: pelanggan sudah memegang PDF-nya, dan
 * justru itu keadaan yang paling perlu ia ketahui.
 */
class InvoiceController extends Controller
{
    public function show(Request $request, Invoice $invoice): \Inertia\Response
    {
        $invoice = $this->milikPengguna($request, $invoice);

        $invoice->load(['items', 'booking.vehicle.carModel', 'booking.servicePackage']);

        return Inertia::render('invoice/show', [
            'invoice' => InvoicePresenter::ownerDetail($invoice),
        ]);
    }

    public function download(Request $request, Invoice $invoice): Response
    {
        return InvoiceDocument::download($this->milikPengguna($request, $invoice));
    }

    /**
     * Satu tempat untuk kedua jalur di atas.
     *
     * Route model binding sudah menyelesaikan `{invoice}` menjadi baris, tetapi
     * baris itu bisa milik siapa saja — jadi ia dicari ULANG lewat relasi
     * penggunanya. Yang tidak ketemu berujung 404 dari `findOrFail`, sama
     * seperti invoice yang belum pernah diterbitkan.
     */
    private function milikPengguna(Request $request, Invoice $invoice): Invoice
    {
        /** @var Invoice $milik */
        $milik = $request->user()
            ->invoices()
            ->whereNotNull('invoices.issued_at')
            ->findOrFail($invoice->getKey());

        return $milik;
    }
}
