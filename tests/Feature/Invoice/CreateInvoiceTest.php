<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\ServicePackage;

/*
|--------------------------------------------------------------------------
| Kelahiran invoice (roadmap 2.4.1 & 2.4.5, keputusan grill Q3 & Q4)
|--------------------------------------------------------------------------
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function bookingSiapSelesai(array $timpaPaket = []): Booking
{
    $pelanggan = pelangganSiapBooking();

    return Booking::factory()->create([
        'user_id' => $pelanggan->id,
        'vehicle_id' => $pelanggan->vehicles()->value('id'),
        'service_package_id' => ServicePackage::factory()->create($timpaPaket)->id,
        'status' => BookingStatus::InProgress,
        'odometer' => 20000,
    ]);
}

it('membuat invoice draft saat booking ditandai selesai', function () {
    $booking = bookingSiapSelesai(['name' => 'Servis Berkala 10.000 KM', 'price' => 450_000, 'is_free' => false]);

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.bookings.status', $booking), ['status' => BookingStatus::Completed->value])
        ->assertRedirect();

    $invoice = $booking->refresh()->invoice;

    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->invoice_number)->toBeNull()
        ->and($invoice->total)->toBe('450000.00');

    $item = $invoice->items()->sole();

    expect($item->type)->toBe(InvoiceItemType::Jasa)
        ->and($item->description)->toBe('Servis Berkala 10.000 KM')
        ->and($item->qty)->toBe('1.00')
        ->and($item->subtotal)->toBe('450000.00');
});

it('tetap membuat invoice Rp 0 untuk paket gratis', function () {
    $booking = bookingSiapSelesai(['name' => 'Servis Gratis Pertama', 'price' => 0, 'is_free' => true]);

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.bookings.status', $booking), ['status' => BookingStatus::Completed->value]);

    $invoice = $booking->refresh()->invoice;

    // Aturan tanpa pengecualian (keputusan grill Q4): servis garansi tetap
    // butuh bukti bahwa ia dikerjakan, dan sparepart di luar cakupan garansi
    // tetap ditagihkan lewat invoice yang sama.
    expect($invoice)->not->toBeNull()
        ->and($invoice->total)->toBe('0.00')
        ->and($invoice->items()->count())->toBe(1);
});

it('membatalkan perubahan status bila invoice gagal dibuat', function () {
    $booking = bookingSiapSelesai();

    // Invoice aktif yang sudah ada membuat pembuatan kedua ditolak. Yang diuji
    // di sini bukan kasus itu sendiri, melainkan keatomikannya: transisi status
    // ikut batal, bukan tersimpan setengah.
    Invoice::factory()->create(['booking_id' => $booking->id]);

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.bookings.status', $booking), ['status' => BookingStatus::Completed->value]);

    expect($booking->refresh()->status)->toBe(BookingStatus::InProgress);
});

it('menampilkan tombol buat invoice untuk booking selesai tanpa invoice', function () {
    // Booking yang selesai sebelum Tahap 11 ada — tidak ada pengisian mundur,
    // jalannya lewat tombol (keputusan grill Q3).
    $booking = Booking::factory()->create(['status' => BookingStatus::Completed]);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(fn ($page) => $page
            ->where('canCreateInvoice', true)
            ->where('invoice', null));
});

it('membuat invoice lewat tombol untuk booking yang sudah selesai', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Completed]);

    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.invoice.store', $booking))
        ->assertRedirect();

    expect($booking->refresh()->invoice)->not->toBeNull();
});

it('menolak pembuatan invoice untuk booking yang belum selesai', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Confirmed]);

    $this->actingAs(serviceAdvisor())
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.invoice.store', $booking))
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHas('error');

    expect($booking->refresh()->invoices()->count())->toBe(0);
});

it('menolak invoice kedua selama yang pertama masih berlaku', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Completed]);
    Invoice::factory()->issued()->create(['booking_id' => $booking->id]);

    $this->actingAs(serviceAdvisor())
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.invoice.store', $booking))
        ->assertSessionHas('error');

    expect($booking->refresh()->invoices()->count())->toBe(1);
});

it('mengizinkan invoice pengganti setelah yang lama dibatalkan', function () {
    // Inti keputusan grill Q1: kolom unik pada booking_id akan membuat baris
    // ini mustahil, sehingga janji "void lalu buat ulang" di docs/05 §5.6
    // tidak pernah bisa ditepati.
    $booking = Booking::factory()->create(['status' => BookingStatus::Completed]);
    Invoice::factory()->voided()->create(['booking_id' => $booking->id]);

    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.invoice.store', $booking))
        ->assertRedirect();

    expect($booking->refresh()->invoices()->count())->toBe(2)
        ->and($booking->invoice->status)->toBe(InvoiceStatus::Draft);
});

it('tidak menembus aturan satu invoice aktif meski diminta beruntun', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Completed]);
    $advisor = serviceAdvisor();

    foreach (range(1, 3) as $percobaan) {
        $this->actingAs($advisor)
            ->from(route('admin.bookings.show', $booking))
            ->post(route('admin.bookings.invoice.store', $booking));
    }

    expect($booking->refresh()->invoices()->active()->count())->toBe(1);
});
