<?php

declare(strict_types=1);

use App\Models\Invoice;

/*
|--------------------------------------------------------------------------
| Perhitungan & penyuntingan (roadmap 2.4.5, docs/05 §5.6)
|--------------------------------------------------------------------------
|
| Berkas ini menjaga satu janji yang paling sering dilanggar sistem lama:
| TOTAL SELALU DIHITUNG SERVER. Uji pertama menembak rutenya dengan `total`
| palsu di payload — bukan memanggil service langsung — supaya jalur yang
| benar-benar dipakai peramban yang teruji.
|
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

/** @return array<string, mixed> */
function payloadInvoice(array $timpa = []): array
{
    return array_merge([
        'items' => [
            ['type' => 'jasa', 'description' => 'Jasa servis berkala', 'qty' => 1, 'unit_price' => 450000],
            ['type' => 'part', 'description' => 'Oli mesin 5W-30', 'qty' => 4, 'unit_price' => 75000],
        ],
        'discount' => 0,
        'tax' => 0,
        'notes' => null,
    ], $timpa);
}

it('menghitung subtotal dan total di server', function () {
    $invoice = Invoice::factory()->create();

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.invoices.update', $invoice), payloadInvoice())
        ->assertRedirect();

    $invoice->refresh();

    // 450.000 + (4 × 75.000) = 750.000
    expect($invoice->subtotal)->toBe('750000.00')
        ->and($invoice->total)->toBe('750000.00')
        ->and($invoice->items()->count())->toBe(2);
});

it('mengabaikan subtotal dan total yang dikirim browser', function () {
    $invoice = Invoice::factory()->create();

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.invoices.update', $invoice), payloadInvoice([
            'subtotal' => 1,
            'total' => 1,
            'status' => 'paid',
            'invoice_number' => 'INV/2026/08/9999',
        ]))
        ->assertRedirect();

    $invoice->refresh();

    expect($invoice->total)->toBe('750000.00')
        ->and($invoice->status->value)->toBe('draft')
        ->and($invoice->invoice_number)->toBeNull();
});

it('mengurangi diskon dan menambah pajak sesuai rumus', function () {
    $invoice = Invoice::factory()->create();

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.invoices.update', $invoice), payloadInvoice([
            'discount' => 50000,
            'tax' => 10000,
        ]));

    // 750.000 − 50.000 + 10.000
    expect($invoice->refresh()->total)->toBe('710000.00');
});

it('menolak diskon yang melebihi subtotal', function () {
    $invoice = Invoice::factory()->create();

    $this->actingAs(serviceAdvisor())
        ->from(route('admin.invoices.show', $invoice))
        ->put(route('admin.invoices.update', $invoice), payloadInvoice(['discount' => 800000]))
        ->assertSessionHasErrors('discount');

    expect($invoice->refresh()->total)->toBe('0.00');
});

it('menerima jumlah setengah untuk jasa per jam', function () {
    $invoice = Invoice::factory()->create();

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.invoices.update', $invoice), payloadInvoice([
            'items' => [['type' => 'jasa', 'description' => 'Jasa balancing', 'qty' => 0.5, 'unit_price' => 100000]],
        ]))
        ->assertRedirect();

    expect($invoice->refresh()->total)->toBe('50000.00');
});

it('menolak jumlah nol', function () {
    $invoice = Invoice::factory()->create();

    $this->actingAs(serviceAdvisor())
        ->from(route('admin.invoices.show', $invoice))
        ->put(route('admin.invoices.update', $invoice), payloadInvoice([
            'items' => [['type' => 'jasa', 'description' => 'Jasa', 'qty' => 0, 'unit_price' => 100000]],
        ]))
        ->assertSessionHasErrors('items.0.qty');
});

it('menolak penyuntingan invoice yang sudah diterbitkan', function () {
    $invoice = Invoice::factory()->issued()->create();

    $this->actingAs(serviceAdvisor())
        ->from(route('admin.invoices.show', $invoice))
        ->put(route('admin.invoices.update', $invoice), payloadInvoice())
        ->assertForbidden();

    expect($invoice->refresh()->items()->count())->toBe(0);
});

it('mengganti seluruh baris, bukan menambahkannya', function () {
    $invoice = Invoice::factory()->create();
    $advisor = serviceAdvisor();

    $this->actingAs($advisor)->put(route('admin.invoices.update', $invoice), payloadInvoice());
    $this->actingAs($advisor)->put(route('admin.invoices.update', $invoice), payloadInvoice([
        'items' => [['type' => 'part', 'description' => 'Filter oli', 'qty' => 1, 'unit_price' => 90000]],
    ]));

    expect($invoice->refresh()->items()->count())->toBe(1)
        ->and($invoice->total)->toBe('90000.00');
});

it('menolak customer dan tamu di rute penyuntingan', function () {
    $invoice = Invoice::factory()->create();

    $this->put(route('admin.invoices.update', $invoice), payloadInvoice())->assertRedirect(route('login'));

    $this->actingAs(customer())
        ->put(route('admin.invoices.update', $invoice), payloadInvoice())
        ->assertForbidden();
});
