<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\WhatsAppPlaceholders;

/*
|--------------------------------------------------------------------------
| Placeholder {{ringkasan_biaya}} (roadmap 2.4.8, keputusan grill Q8)
|--------------------------------------------------------------------------
|
| Uji feature, bukan unit: nilainya dibaca dari tabel `invoices`, dan yang
| dijaga di sini adalah KAPAN ia berisi angka — bukan bentuk kalimatnya.
|
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function bookingSelesai(): Booking
{
    $pelanggan = pelangganSiapBooking();

    return Booking::factory()->create([
        'user_id' => $pelanggan->id,
        'vehicle_id' => $pelanggan->vehicles()->value('id'),
        'status' => BookingStatus::Completed,
    ]);
}

it('merender total sebagai satu baris setelah invoice diterbitkan', function () {
    $booking = bookingSelesai();
    $invoice = Invoice::factory()->issued()->amounts(750_000)->create(['booking_id' => $booking->id]);

    InvoiceItem::factory()->jasa('Jasa servis', 1, 450_000)->create(['invoice_id' => $invoice->id]);
    InvoiceItem::factory()->part('Oli mesin', 4, 75_000)->create(['invoice_id' => $invoice->id]);

    $nilai = WhatsAppPlaceholders::forBooking($booking->fresh(['user', 'vehicle', 'servicePackage']));

    expect($nilai['{{ringkasan_biaya}}'])->toBe('Total biaya: Rp 750.000 (2 item)');
});

it('tidak mengirim angka draft yang masih bisa berubah', function () {
    $booking = bookingSelesai();
    Invoice::factory()->amounts(750_000)->create(['booking_id' => $booking->id]);

    $nilai = WhatsAppPlaceholders::forBooking($booking->fresh(['user', 'vehicle', 'servicePackage']));

    expect($nilai['{{ringkasan_biaya}}'])->toBe('Rincian biaya menyusul');
});

it('tidak memakai invoice yang sudah dibatalkan', function () {
    $booking = bookingSelesai();
    Invoice::factory()->voided()->amounts(750_000)->create(['booking_id' => $booking->id]);

    $nilai = WhatsAppPlaceholders::forBooking($booking->fresh(['user', 'vehicle', 'servicePackage']));

    expect($nilai['{{ringkasan_biaya}}'])->toBe('Rincian biaya menyusul');
});

it('menerima ringkasan_biaya di penyunting template WA', function () {
    $template = App\Models\WhatsAppTemplate::factory()
        ->key(App\Enums\WhatsAppTemplateKey::BookingCompleted)
        ->create();

    $this->actingAs(superAdmin())
        ->put(route('admin.whatsapp-templates.update', $template), [
            'name' => $template->name,
            'body' => 'Servis {{kode_booking}} selesai. {{ringkasan_biaya}}. - Chery Arta',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($template->refresh()->body)->toContain('{{ringkasan_biaya}}');
});
