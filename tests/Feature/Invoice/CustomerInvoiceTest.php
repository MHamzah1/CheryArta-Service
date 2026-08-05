<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceItem;

/*
|--------------------------------------------------------------------------
| Invoice dari sisi pelanggan (roadmap 2.4.4, keputusan grill Q7)
|--------------------------------------------------------------------------
|
| Patokan yang diuji di sini adalah `issued_at`, BUKAN status. Bedanya baru
| terasa pada invoice yang pernah terbit lalu di-void: dengan patokan status ia
| akan hilang menjadi 404 dari mata pelanggan yang sudah memegang PDF-nya.
|
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

/** @return array{0: App\Models\User, 1: Invoice} */
function invoicePelanggan(string $state = 'issued'): array
{
    $pelanggan = pelangganSiapBooking();

    $booking = Booking::factory()->create([
        'user_id' => $pelanggan->id,
        'vehicle_id' => $pelanggan->vehicles()->value('id'),
        'status' => BookingStatus::Completed,
    ]);

    $factory = Invoice::factory();
    $invoice = match ($state) {
        'draft' => $factory,
        'paid' => $factory->paid(),
        'voided' => $factory->voided(),
        default => $factory->issued(),
    };

    $invoice = $invoice->create(['booking_id' => $booking->id]);

    InvoiceItem::factory()->part('Oli mesin', 4, 75_000)->create(['invoice_id' => $invoice->id]);

    return [$pelanggan, $invoice];
}

it('menampilkan invoice yang sudah diterbitkan kepada pemiliknya', function () {
    [$pelanggan, $invoice] = invoicePelanggan();

    $this->actingAs($pelanggan)
        ->get(route('customer.invoice.show', $invoice))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('invoice/show')
            ->where('invoice.invoice_number', $invoice->invoice_number));
});

it('menjawab 404 untuk invoice yang masih draft', function () {
    [$pelanggan, $invoice] = invoicePelanggan('draft');

    // Sengaja 404 dan bukan 403: 403 mengakui bahwa ada sesuatu di sana, dan
    // draft yang belum diterbitkan bukan urusan pelanggan.
    $this->actingAs($pelanggan)
        ->get(route('customer.invoice.show', $invoice))
        ->assertNotFound();
});

it('tetap menampilkan invoice yang pernah terbit lalu dibatalkan', function () {
    [$pelanggan, $invoice] = invoicePelanggan('voided');

    $this->actingAs($pelanggan)
        ->get(route('customer.invoice.show', $invoice))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('invoice.status', 'void'));
});

it('menjawab 404 untuk invoice milik pelanggan lain', function () {
    [, $invoice] = invoicePelanggan();

    $this->actingAs(customer())
        ->get(route('customer.invoice.show', $invoice))
        ->assertNotFound();
});

it('tidak membocorkan catatan internal maupun pembuat invoice', function () {
    [$pelanggan, $invoice] = invoicePelanggan();

    $invoice->forceFill(['notes' => 'Catatan untuk pelanggan'])->save();

    $this->actingAs($pelanggan)
        ->get(route('customer.invoice.show', $invoice))
        ->assertInertia(fn ($page) => $page
            ->missing('invoice.created_by_name')
            ->missing('invoice.customer_name')
            ->missing('invoice.booking.customer_phone'));
});

it('menautkan invoice dari detail booking hanya setelah terbit', function () {
    [$pelanggan, $invoice] = invoicePelanggan('draft');
    $booking = $invoice->booking;

    $this->actingAs($pelanggan)
        ->get(route('customer.booking.show', $booking))
        ->assertInertia(fn ($page) => $page->where('invoice', null));

    $invoice->forceFill(['status' => 'issued', 'invoice_number' => 'INV/2026/08/0001', 'issued_at' => now()])->save();

    $this->actingAs($pelanggan)
        ->get(route('customer.booking.show', $booking))
        ->assertInertia(fn ($page) => $page->where('invoice.invoice_number', 'INV/2026/08/0001'));
});

it('menolak tamu di rute invoice', function () {
    [, $invoice] = invoicePelanggan();

    $this->get(route('customer.invoice.show', $invoice))->assertRedirect(route('login'));
});

it('mengunduh PDF invoice milik sendiri', function () {
    [$pelanggan, $invoice] = invoicePelanggan();

    $respons = $this->actingAs($pelanggan)->get(route('customer.invoice.pdf', $invoice));

    $respons->assertOk();

    expect($respons->headers->get('content-type'))->toContain('application/pdf');
});

it('menolak unduhan PDF invoice milik orang lain', function () {
    [, $invoice] = invoicePelanggan();

    $this->actingAs(customer())
        ->get(route('customer.invoice.pdf', $invoice))
        ->assertNotFound();
});
