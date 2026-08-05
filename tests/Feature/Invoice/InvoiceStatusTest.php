<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Spatie\Activitylog\Models\Activity;

/*
|--------------------------------------------------------------------------
| Transisi status invoice (roadmap 2.4.5, docs/05 §5.6)
|--------------------------------------------------------------------------
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function invoiceDraftBerisi(): Invoice
{
    $invoice = Invoice::factory()->create();

    InvoiceItem::factory()->jasa('Jasa servis berkala', 1, 450_000)->create(['invoice_id' => $invoice->id]);

    return $invoice;
}

it('menerbitkan invoice dengan nomor berurut per bulan', function () {
    $advisor = serviceAdvisor();

    $pertama = invoiceDraftBerisi();
    $kedua = invoiceDraftBerisi();

    $this->actingAs($advisor)->put(route('admin.invoices.issue', $pertama))->assertRedirect();
    $this->actingAs($advisor)->put(route('admin.invoices.issue', $kedua))->assertRedirect();

    expect($pertama->refresh()->invoice_number)->toBe('INV/2026/08/0001')
        ->and($kedua->refresh()->invoice_number)->toBe('INV/2026/08/0002')
        ->and($pertama->status)->toBe(InvoiceStatus::Issued)
        ->and($pertama->issued_at)->not->toBeNull();
});

it('menghitung ulang total saat menerbitkan', function () {
    $invoice = invoiceDraftBerisi();

    // Total sengaja dikotori langsung di database — meniru baris yang sempat
    // disunting lewat jalur lain. Penerbitan harus mengembalikannya ke angka
    // yang benar, bukan mengabadikan yang salah.
    $invoice->forceFill(['total' => 1, 'subtotal' => 1])->save();

    $this->actingAs(serviceAdvisor())->put(route('admin.invoices.issue', $invoice));

    expect($invoice->refresh()->total)->toBe('450000.00');
});

it('menolak penerbitan invoice tanpa item', function () {
    $invoice = Invoice::factory()->create();

    $this->actingAs(serviceAdvisor())
        ->from(route('admin.invoices.show', $invoice))
        ->put(route('admin.invoices.issue', $invoice))
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->invoice_number)->toBeNull();
});

it('tidak menghabiskan nomor untuk draft yang tidak pernah terbit', function () {
    Invoice::factory()->count(3)->create();

    $invoice = invoiceDraftBerisi();
    $this->actingAs(serviceAdvisor())->put(route('admin.invoices.issue', $invoice));

    expect($invoice->refresh()->invoice_number)->toBe('INV/2026/08/0001');
});

it('menandai lunas beserta metode pembayarannya', function () {
    $invoice = Invoice::factory()->issued()->create();

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.invoices.paid', $invoice), ['payment_method' => 'transfer'])
        ->assertRedirect();

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->payment_method?->value)->toBe('transfer')
        ->and($invoice->paid_at)->not->toBeNull();
});

it('menolak metode pembayaran yang tidak dikenali', function () {
    $invoice = Invoice::factory()->issued()->create();

    $this->actingAs(serviceAdvisor())
        ->from(route('admin.invoices.show', $invoice))
        ->put(route('admin.invoices.paid', $invoice), ['payment_method' => 'kripto'])
        ->assertSessionHasErrors('payment_method');
});

it('menolak advisor yang mencoba membatalkan invoice', function () {
    $invoice = Invoice::factory()->issued()->create();

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.invoices.void', $invoice), ['reason' => 'Salah input harga'])
        ->assertForbidden();

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Issued);
});

it('mengizinkan super admin membatalkan invoice dengan alasan', function () {
    $invoice = Invoice::factory()->issued()->create();

    $this->actingAs(superAdmin())
        ->put(route('admin.invoices.void', $invoice), ['reason' => 'Salah input harga sparepart'])
        ->assertRedirect();

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Void)
        ->and($invoice->void_reason)->toBe('Salah input harga sparepart');
});

it('mewajibkan alasan saat membatalkan', function () {
    $invoice = Invoice::factory()->issued()->create();

    $this->actingAs(superAdmin())
        ->from(route('admin.invoices.show', $invoice))
        ->put(route('admin.invoices.void', $invoice), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Issued);
});

it('mengizinkan super admin membatalkan invoice yang sudah lunas', function () {
    // Keputusan grill Q2: tanpa jalur ini, satu klik "Tandai Lunas" yang keliru
    // hanya bisa diperbaiki lewat DBeaver.
    $invoice = Invoice::factory()->paid()->create();

    $this->actingAs(superAdmin())
        ->put(route('admin.invoices.void', $invoice), ['reason' => 'Pembayaran dikembalikan ke pelanggan'])
        ->assertRedirect();

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Void)
        // Jejak pembayaran TIDAK dihapus — log audit tidak boleh berbohong
        // tentang apa yang sempat terjadi.
        ->and($invoice->paid_at)->not->toBeNull()
        ->and($invoice->payment_method)->not->toBeNull();
});

it('menolak transisi yang tidak ada dengan galat, bukan 403', function () {
    $invoice = Invoice::factory()->voided()->create();

    // Advisor memang BERHAK menandai lunas; perpindahannya yang tidak ada.
    $this->actingAs(serviceAdvisor())
        ->from(route('admin.invoices.show', $invoice))
        ->put(route('admin.invoices.paid', $invoice), ['payment_method' => 'cash'])
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Void);
});

it('mencatat pembatalan invoice ke activity log', function () {
    $invoice = Invoice::factory()->issued()->create();

    $this->actingAs(superAdmin())
        ->put(route('admin.invoices.void', $invoice), ['reason' => 'Duplikat dengan invoice lain']);

    // docs/09 §9.7 mewajibkan void invoice tercatat.
    expect(Activity::query()->where('subject_type', Invoice::class)->where('subject_id', $invoice->id)->exists())
        ->toBeTrue();
});
