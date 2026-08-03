<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;

/*
| Otorisasi seluruh rute /admin (roadmap 1.5.9, docs/07 §A14, docs/09 §9.3).
|
| Sistem lama menentukan hak akses dari localStorage di browser, sehingga siapa
| pun yang tahu URL-nya bisa masuk panel admin (temuan S3). Uji di sini menjaga
| bahwa penolakannya terjadi di server untuk SETIAP rute — bukan dengan
| menyembunyikan menunya.
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

/**
 * Seluruh rute /admin yang lahir di F1.5, beserta metodenya.
 *
 * @return list<array{0: string, 1: string}>
 */
function ruteAdminOperasional(Booking $booking, User $customer): array
{
    return [
        ['get', route('admin.dashboard')],
        ['get', route('admin.schedule.index')],
        ['get', route('admin.bookings.index')],
        ['get', route('admin.bookings.create')],
        ['post', route('admin.bookings.store')],
        ['get', route('admin.bookings.show', $booking)],
        ['put', route('admin.bookings.status', $booking)],
        ['delete', route('admin.bookings.destroy', $booking)],
        ['get', route('admin.customers.index')],
        ['get', route('admin.customers.show', $customer)],
        ['put', route('admin.customers.toggle-active', $customer)],
        ['put', route('admin.customers.reset-password', $customer)],
        ['get', route('admin.vehicles.index')],
    ];
}

/** Booking milik pelanggan yang datanya lengkap — bahan dasar seluruh uji admin. */
function bookingAdmin(array $timpa = []): Booking
{
    $customer = pelangganSiapBooking();

    return Booking::factory()->create([
        'user_id' => $customer->id,
        'vehicle_id' => $customer->vehicles()->value('id'),
        'booking_date' => UJI_BESOK,
        ...$timpa,
    ]);
}

it('mengalihkan tamu ke halaman masuk di seluruh rute admin', function () {
    $booking = bookingAdmin();

    foreach (ruteAdminOperasional($booking, $booking->user) as [$method, $url]) {
        $this->$method($url)->assertRedirect(route('login'));
    }
});

it('menolak customer di seluruh rute admin', function () {
    $booking = bookingAdmin();

    foreach (ruteAdminOperasional($booking, $booking->user) as [$method, $url]) {
        $this->actingAs(customer())->$method($url)->assertForbidden();
    }
});

it('menolak akun staf yang dinonaktifkan', function () {
    $booking = bookingAdmin();
    $advisor = serviceAdvisor(['is_active' => false]);

    $this->actingAs($advisor)->get(route('admin.bookings.index'))->assertForbidden();
});

it('mengizinkan service advisor membuka layar operasional A2, A3, dan A6', function () {
    $booking = bookingAdmin();
    $advisor = serviceAdvisor();

    $bolehDilihat = [
        route('admin.schedule.index'),
        route('admin.bookings.index'),
        route('admin.bookings.create'),
        route('admin.bookings.show', $booking),
        route('admin.customers.index'),
        route('admin.customers.show', $booking->user),
        route('admin.vehicles.index'),
    ];

    foreach ($bolehDilihat as $url) {
        $this->actingAs($advisor)->get($url)->assertOk();
    }
});

it('menolak service advisor menghapus booking', function () {
    $booking = bookingAdmin();

    $this->actingAs(serviceAdvisor())
        ->delete(route('admin.bookings.destroy', $booking))
        ->assertForbidden();

    expect(Booking::query()->whereKey($booking->getKey())->exists())->toBeTrue();
});

it('mengizinkan super admin menghapus booking', function () {
    $booking = bookingAdmin();

    $this->actingAs(superAdmin())
        ->delete(route('admin.bookings.destroy', $booking))
        ->assertRedirect(route('admin.bookings.index'));

    // Soft delete: barisnya tetap ada untuk audit, hanya hilang dari daftar.
    expect(Booking::query()->whereKey($booking->getKey())->exists())->toBeFalse()
        ->and(Booking::withTrashed()->whereKey($booking->getKey())->exists())->toBeTrue();
});

it('menolak service advisor menonaktifkan akun customer', function () {
    $customer = customer();

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.customers.toggle-active', $customer))
        ->assertForbidden();

    expect($customer->refresh()->is_active)->toBeTrue();
});

it('menolak service advisor mereset password customer', function () {
    $customer = customer();
    $passwordLama = $customer->password;

    $this->actingAs(serviceAdvisor())
        ->put(route('admin.customers.reset-password', $customer))
        ->assertForbidden();

    expect($customer->refresh()->password)->toBe($passwordLama);
});

it('menolak staf membuka akun sesama staf lewat layar customer', function () {
    // Layar A6 hanya untuk pelanggan; akun staf dikelola A11 di Big Fase 2
    // dengan pengaman yang berbeda (docs/07 §A11).
    $advisorLain = serviceAdvisor();

    $this->actingAs(superAdmin())
        ->get(route('admin.customers.show', $advisorLain))
        ->assertForbidden();
});

it('tidak menampilkan kendaraan pelanggan lain sebagai milik pelanggan terpilih di form walk-in', function () {
    $pelanggan = pelangganSiapBooking();
    $orangLain = pelangganSiapBooking();

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.create', ['pelanggan' => $pelanggan->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/bookings/create')
            ->has('selectedCustomer.vehicles', 1)
            ->where('selectedCustomer.vehicles.0.id', $pelanggan->vehicles()->value('id')));

    expect($orangLain->vehicles()->count())->toBe(1);
});

it('mengalihkan /admin ke daftar booking selama dashboard belum ada', function () {
    // Keputusan R8: A1 ditunda utuh ke Big Fase 2, dan `/admin` tidak
    // menampilkan dashboard setengah jadi.
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.bookings.index'));
});

it('menolak customer membuka kendaraan lewat daftar admin', function () {
    Vehicle::factory()->create(['user_id' => customer()->id]);

    $this->actingAs(customer())
        ->get(route('admin.vehicles.index'))
        ->assertForbidden();
});
