<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;

/*
| A6 — Customer & Kendaraan (roadmap 1.5.6, docs/07 §A6).
|
| Yang dijaga: daftarnya hanya berisi pelanggan (bukan akun staf), pencariannya
| berjalan di server, dan dua aksi akun — nonaktifkan serta reset password —
| benar-benar terbatas pada Super Admin (matriks §A14).
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function pelangganDenganServis(array $userTimpa = [], ?string $tanggalSelesai = null): User
{
    $user = pelangganSiapBooking($userTimpa);

    if ($tanggalSelesai !== null) {
        Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $user->vehicles()->value('id'),
            'booking_date' => $tanggalSelesai,
            'status' => BookingStatus::Completed,
        ]);
    }

    return $user;
}

it('menampilkan pelanggan beserta jumlah kendaraan dan servis terakhirnya', function () {
    $pelanggan = pelangganDenganServis(['name' => 'Budi Santoso'], '2026-07-20');

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/customers/index')
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Budi Santoso')
            ->where('customers.data.0.vehicles_count', 1)
            ->where('customers.data.0.bookings_count', 1)
            ->where('customers.data.0.last_service_date', '2026-07-20'));

    expect($pelanggan->isCustomer())->toBeTrue();
});

it('tidak memasukkan akun staf ke daftar customer', function () {
    pelangganSiapBooking();
    serviceAdvisor();
    superAdmin();

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.customers.index'))
        ->assertInertia(fn ($page) => $page->has('customers.data', 1));
});

it('tidak menghitung booking yang dibatalkan sebagai servis terakhir', function () {
    $pelanggan = pelangganSiapBooking();
    Booking::factory()->create([
        'user_id' => $pelanggan->id,
        'vehicle_id' => $pelanggan->vehicles()->value('id'),
        'booking_date' => '2026-07-20',
        'status' => BookingStatus::Cancelled,
    ]);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.customers.index'))
        ->assertInertia(fn ($page) => $page->where('customers.data.0.last_service_date', null));
});

it('mencari pelanggan lewat nama, email, maupun nomor WhatsApp', function (string $kata) {
    pelangganSiapBooking(['name' => 'Rani Safitri', 'email' => 'rani@contoh.test', 'phone_wa' => '6281377665544']);
    pelangganSiapBooking(['name' => 'Budi Santoso']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.customers.index', ['cari' => $kata]))
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Rani Safitri'));
})->with(['rani', 'rani@contoh.test', '0813-7766-5544']);

it('menampilkan detail pelanggan beserta riwayat bookingnya', function () {
    $pelanggan = pelangganDenganServis(['name' => 'Budi Santoso'], '2026-07-20');

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.customers.show', $pelanggan))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/customers/show')
            ->where('customer.name', 'Budi Santoso')
            ->has('vehicles', 1)
            ->has('bookings.data', 1)
            ->where('stats.completed_bookings', 1)
            ->where('stats.last_service_date', '2026-07-20'));
});

it('tidak menampilkan tombol khusus super admin kepada advisor', function () {
    $pelanggan = pelangganSiapBooking();

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.customers.show', $pelanggan))
        ->assertInertia(fn ($page) => $page
            ->where('canToggleActive', false)
            ->where('canResetPassword', false));

    $this->actingAs(superAdmin())
        ->get(route('admin.customers.show', $pelanggan))
        ->assertInertia(fn ($page) => $page
            ->where('canToggleActive', true)
            ->where('canResetPassword', true));
});

it('menonaktifkan lalu mengaktifkan kembali akun pelanggan sebagai super admin', function () {
    $pelanggan = pelangganSiapBooking();

    $this->actingAs(superAdmin())->put(route('admin.customers.toggle-active', $pelanggan));
    expect($pelanggan->refresh()->is_active)->toBeFalse();

    $this->actingAs(superAdmin())->put(route('admin.customers.toggle-active', $pelanggan));
    expect($pelanggan->refresh()->is_active)->toBeTrue();
});

it('menonaktifkan akun tanpa menghapus riwayat servisnya', function () {
    // Tidak ada aksi hapus permanen di modul ini — riwayat dibutuhkan untuk
    // garansi kendaraan (docs/07 §A6).
    $pelanggan = pelangganDenganServis([], '2026-07-20');

    $this->actingAs(superAdmin())->put(route('admin.customers.toggle-active', $pelanggan));

    expect($pelanggan->refresh()->is_active)->toBeFalse()
        ->and($pelanggan->bookings()->count())->toBe(1)
        ->and($pelanggan->vehicles()->count())->toBe(1);
});

it('mereset password dan menandai akun harus mengatur ulang', function () {
    $pelanggan = pelangganSiapBooking();
    $passwordLama = $pelanggan->password;

    $this->actingAs(superAdmin())
        ->put(route('admin.customers.reset-password', $pelanggan))
        ->assertSessionHas('info');

    expect($pelanggan->refresh()->password)->not->toBe($passwordLama)
        ->and($pelanggan->must_reset_password)->toBeTrue();
});

it('menampilkan seluruh kendaraan terdaftar beserta pemiliknya', function () {
    $pelanggan = pelangganSiapBooking(['name' => 'Budi Santoso']);
    $kendaraan = $pelanggan->vehicles()->firstOrFail();

    Booking::factory()->create([
        'user_id' => $pelanggan->id,
        'vehicle_id' => $kendaraan->id,
        'booking_date' => '2026-07-20',
        'status' => BookingStatus::Completed,
    ]);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.vehicles.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/vehicles/index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.plate_full', $kendaraan->plate_full)
            ->where('vehicles.data.0.owner.name', 'Budi Santoso')
            ->where('vehicles.data.0.services_count', 1)
            ->where('vehicles.data.0.last_service_date', '2026-07-20'));
});

it('mencari kendaraan meski plat diketik dengan spasi', function () {
    $pemilik = User::factory()->create();
    Vehicle::factory()->create([
        'user_id' => $pemilik->id,
        'plate_prefix' => 'B',
        'plate_number' => '1234',
        'plate_suffix' => 'ABC',
    ]);
    Vehicle::factory()->create([
        'user_id' => $pemilik->id,
        'plate_prefix' => 'D',
        'plate_number' => '9999',
        'plate_suffix' => 'XYZ',
    ]);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.vehicles.index', ['cari' => 'b 1234 abc']))
        ->assertInertia(fn ($page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.plate_full', 'B-1234-ABC'));
});

it('mencari kendaraan lewat nama pemiliknya', function () {
    pelangganSiapBooking(['name' => 'Rani Safitri']);
    pelangganSiapBooking(['name' => 'Budi Santoso']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.vehicles.index', ['cari' => 'Rani']))
        ->assertInertia(fn ($page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.owner.name', 'Rani Safitri'));
});
