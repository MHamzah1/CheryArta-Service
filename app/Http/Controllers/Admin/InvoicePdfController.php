<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\InvoiceDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Unduh PDF invoice dari panel (roadmap 2.4.3).
 *
 * Berkasnya identik dengan yang diunduh pelanggan — lihat
 * App\Support\InvoiceDocument. Yang berbeda hanya siapa yang boleh sampai ke
 * sini, dan itu dijawab InvoicePolicy.
 */
class InvoicePdfController extends Controller
{
    public function __invoke(Invoice $invoice): Response
    {
        Gate::authorize('download', $invoice);

        return InvoiceDocument::download($invoice);
    }
}
