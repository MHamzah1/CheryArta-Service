<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkInvoicePaidRequest;
use App\Http\Requests\Admin\VoidInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Tiga aksi status invoice: terbitkan, tandai lunas, batalkan (docs/07 §A8).
 *
 * Dipisahkan dari InvoiceController dengan alasan yang sama seperti
 * BookingStatusController di F1.5: yang satu mengurus DATA invoice, yang ini
 * mengurus PERPINDAHAN status. Keduanya punya otorisasi yang berbeda — void
 * hanya Super Admin — dan mencampurnya membuat perbedaan itu tenggelam.
 *
 * Perpindahan mana yang sah dijawab App\Enums\InvoiceStatus lewat
 * InvoiceService, bukan oleh rangkaian `if` di sini.
 */
class InvoiceStatusController extends Controller
{
    public function issue(Request $request, Invoice $invoice, InvoiceService $invoices): RedirectResponse
    {
        Gate::authorize('issue', $invoice);

        $terbit = $invoices->issue($invoice, $request->user());

        return back()->with('success', "Invoice {$terbit->invoice_number} diterbitkan.");
    }

    public function markPaid(MarkInvoicePaidRequest $request, Invoice $invoice, InvoiceService $invoices): RedirectResponse
    {
        Gate::authorize('markPaid', $invoice);

        $invoices->markPaid($invoice, $request->paymentMethod());

        return back()->with('success', 'Invoice ditandai lunas.');
    }

    /**
     * Super Admin saja (docs/09 §9.3). Alasannya wajib dan ikut ke activity log
     * (docs/09 §9.7) — void tanpa keterangan meninggalkan tagihan hilang yang
     * tak seorang pun bisa jelaskan enam bulan kemudian.
     */
    public function void(VoidInvoiceRequest $request, Invoice $invoice, InvoiceService $invoices): RedirectResponse
    {
        Gate::authorize('void', $invoice);

        $invoices->void($invoice, $request->reason());

        return back()->with('success', 'Invoice dibatalkan. Buat invoice pengganti dari detail booking bila diperlukan.');
    }
}
