<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceFilterRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Models\Booking;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Support\InvoicePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A8 — Invoice (docs/07 §A8).
 *
 * Middleware `role` menjaga pintu masuk /admin; `Gate::authorize` di tiap method
 * adalah lapis keduanya, dan yang membedakan Super Admin dari advisor
 * (docs/09 §9.3).
 *
 * **Tidak ada `create` yang berdiri sendiri.** Invoice selalu lahir dari sebuah
 * booking yang sudah selesai — lewat transisi `completed` atau lewat `store()`
 * di bawah (keputusan grill Q3). Form "invoice kosong" akan menghasilkan
 * tagihan tanpa pekerjaan yang bisa ditelusuri.
 */
class InvoiceController extends Controller
{
    /** Daftar invoice dengan saringan yang dijalankan di server. */
    public function index(InvoiceFilterRequest $request): Response
    {
        Gate::authorize('viewAny', Invoice::class);

        $filters = $request->filters();

        $invoices = Invoice::query()
            // Tanpa ini satu halaman 25 baris menembakkan ratusan kueri —
            // cara N+1 masuk lewat pintu belakang (.claude/rules/10).
            ->with(['booking:id,booking_code,booking_date,user_id,vehicle_id', 'booking.user:id,name', 'booking.vehicle:id,plate_prefix,plate_number,plate_suffix'])
            ->tap(fn (Builder $query) => $this->saring($query, $filters))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(InvoicePresenter::adminRow(...));

        return Inertia::render('admin/invoices/index', [
            'invoices' => $invoices,
            'filters' => $filters,
            'statusOptions' => InvoiceStatus::options(),
        ]);
    }

    /**
     * Penyunting invoice — sekaligus halaman baca-saja setelah diterbitkan.
     *
     * Satu halaman, bukan dua: yang berubah setelah `issued` hanyalah apakah
     * kolomnya bisa disunting, dan dua rute berarti advisor harus tahu lebih
     * dulu status invoicenya sebelum bisa membukanya.
     */
    public function show(Invoice $invoice): Response
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['items', 'booking.user', 'booking.vehicle.carModel', 'booking.servicePackage', 'createdBy:id,name']);

        return Inertia::render('admin/invoices/show', [
            'invoice' => InvoicePresenter::adminDetail($invoice),
            // Otorisasi dihitung di SERVER lalu dikirim sebagai boolean.
            // Menyembunyikan tombol di React bukan pengaman (.claude/rules/20).
            'canEdit' => Gate::allows('update', $invoice),
            'canIssue' => Gate::allows('issue', $invoice) && $invoice->status->canTransitionTo(InvoiceStatus::Issued),
            'canMarkPaid' => Gate::allows('markPaid', $invoice) && $invoice->status->canTransitionTo(InvoiceStatus::Paid),
            'canVoid' => Gate::allows('void', $invoice) && $invoice->status->canTransitionTo(InvoiceStatus::Void),
            'itemTypeOptions' => InvoiceItemType::options(),
            'paymentMethodOptions' => PaymentMethod::options(),
        ]);
    }

    /**
     * Tombol "Buat Invoice" di detail booking (keputusan grill Q3).
     *
     * Satu jalur untuk tiga keadaan: booking yang selesai sebelum Tahap 11 ada,
     * invoice yang baru saja di-void dan perlu penggantinya, dan pemulihan bila
     * pembuatan otomatis pernah gagal. Apakah booking-nya memang layak dijawab
     * InvoiceService dengan barisnya terkunci, bukan di sini.
     */
    public function store(Booking $booking, InvoiceService $invoices): RedirectResponse
    {
        Gate::authorize('create', [Invoice::class, $booking]);

        $invoice = $invoices->createDraftFor($booking, request()->user());

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice draft dibuat. Lengkapi rinciannya lalu terbitkan.');
    }

    /** Menyimpan suntingan draft. Total dihitung ulang server (docs/05 §5.6). */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceService $invoices): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $invoices->updateDraft($invoice, $request->payload());

        return back()->with('success', 'Rincian invoice disimpan.');
    }

    /**
     * @param  Builder<Invoice>  $query
     * @param  array{cari: string|null, status: string|null, dari: string|null, sampai: string|null}  $filters
     */
    private function saring(Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'], fn (Builder $q, string $status) => $q->where('status', $status))
            // Rentangnya terhadap `issued_at`, sejalan dengan laporan Pendapatan
            // (keputusan grill Q9) — bukan `created_at`, yang bagi advisor
            // berarti "kapan drafnya lahir" dan bukan "kapan ditagihkan".
            ->when($filters['dari'], fn (Builder $q, string $dari) => $q->whereDate('issued_at', '>=', $dari))
            ->when($filters['sampai'], fn (Builder $q, string $sampai) => $q->whereDate('issued_at', '<=', $sampai))
            ->when($filters['cari'], function (Builder $q, string $kata): void {
                $q->where(function (Builder $inner) use ($kata): void {
                    $inner
                        ->where('invoice_number', 'like', '%'.$kata.'%')
                        ->orWhereHas('booking', fn (Builder $b) => $b->where('booking_code', 'like', '%'.$kata.'%'))
                        ->orWhereHas('booking.user', fn (Builder $u) => $u->where('name', 'like', '%'.$kata.'%'));
                });
            });
    }
}
