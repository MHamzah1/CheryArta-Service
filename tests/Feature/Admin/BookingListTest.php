<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\Vehicle;

/*
| A2 — daftar booking dengan saringan DI SERVER (roadmap 1.5.2, docs/07 §A2).
|
| Sistem lama mengirim seluruh isi tabel ke browser lalu menyaringnya di sana
| (temuan S8). Yang dijaga di sini: penyaringannya benar-benar terjadi di
| kueri, dan yang sampai ke props hanya baris yang cocok.
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

/** Booking lengkap dengan pemilik & kendaraannya sendiri. */
function bookingDaftar(array $timpa = [], array $userTimpa = [], array $vehicleTimpa = []): Booking
{
    $user = User::factory()->create($userTimpa);
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, ...$vehicleTimpa]);

    return Booking::factory()->create([
        'user_id' => $user->id,
        'vehicle_id' => $vehicle->id,
        'booking_date' => UJI_BESOK,
        ...$timpa,
    ]);
}

it('menampilkan seluruh booking kepada staf, bukan hanya miliknya', function () {
    bookingDaftar(['booking_time' => '09:00']);
    bookingDaftar(['booking_time' => '10:00']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/bookings/index')
            ->has('bookings.data', 2)
            ->has('statusOptions', 6)
            ->where('filters.urutan', 'terbaru'));
});

it('menyaring booking berdasarkan nama pelanggan', function () {
    $dicari = bookingDaftar(['booking_time' => '09:00'], ['name' => 'Budi Santoso']);
    bookingDaftar(['booking_time' => '10:00'], ['name' => 'Rani Safitri']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index', ['cari' => 'budi']))
        ->assertInertia(fn ($page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_code', $dicari->booking_code));
});

it('menyaring booking berdasarkan kode booking', function () {
    $dicari = bookingDaftar(['booking_time' => '09:00']);
    bookingDaftar(['booking_time' => '10:00']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index', ['cari' => $dicari->booking_code]))
        ->assertInertia(fn ($page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_code', $dicari->booking_code));
});

it('menemukan booking meski plat diketik dengan spasi', function () {
    // Plat tersimpan "B-1234-ABC" tetapi diketik orang "B 1234 ABC" —
    // tanpa penyeragaman, cara pencarian yang paling wajar tidak menemukan
    // apa pun.
    $dicari = bookingDaftar(
        ['booking_time' => '09:00'],
        [],
        ['plate_prefix' => 'B', 'plate_number' => '1234', 'plate_suffix' => 'ABC'],
    );
    bookingDaftar(['booking_time' => '10:00'], [], ['plate_prefix' => 'D', 'plate_number' => '9999', 'plate_suffix' => 'XYZ']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index', ['cari' => 'b 1234 abc']))
        ->assertInertia(fn ($page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_code', $dicari->booking_code));
});

it('menyaring booking berdasarkan status', function () {
    $selesai = bookingDaftar(['booking_time' => '09:00', 'status' => BookingStatus::Completed]);
    bookingDaftar(['booking_time' => '10:00', 'status' => BookingStatus::Pending]);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index', ['status' => 'completed']))
        ->assertInertia(fn ($page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_code', $selesai->booking_code));
});

it('menyaring booking berdasarkan rentang tanggal', function () {
    $besok = bookingDaftar(['booking_date' => UJI_BESOK, 'booking_time' => '09:00']);
    bookingDaftar(['booking_date' => UJI_SABTU, 'booking_time' => '09:00']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index', ['dari' => UJI_BESOK, 'sampai' => UJI_BESOK]))
        ->assertInertia(fn ($page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_code', $besok->booking_code));
});

it('menyaring booking berdasarkan paket layanan', function () {
    $paket = ServicePackage::factory()->create(['code' => 'paket_disaring']);
    $cocok = bookingDaftar(['booking_time' => '09:00', 'service_package_id' => $paket->id]);
    bookingDaftar(['booking_time' => '10:00']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index', ['paket' => $paket->id]))
        ->assertInertia(fn ($page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_code', $cocok->booking_code));
});

it('menyaring booking berdasarkan advisor penanggung jawab', function () {
    $advisor = serviceAdvisor(['name' => 'Andre']);
    $ditangani = bookingDaftar(['booking_time' => '09:00', 'handled_by' => $advisor->id]);
    bookingDaftar(['booking_time' => '10:00']);

    $this->actingAs($advisor)
        ->get(route('admin.bookings.index', ['advisor' => $advisor->id]))
        ->assertInertia(fn ($page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_code', $ditangani->booking_code)
            ->where('bookings.data.0.handled_by_name', 'Andre'));
});

it('menolak rentang tanggal yang terbalik', function () {
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index', ['dari' => UJI_SABTU, 'sampai' => UJI_BESOK]))
        ->assertSessionHasErrors('sampai');
});

it('menolak nilai status yang tidak dikenal', function () {
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index', ['status' => 'entah_apa']))
        ->assertSessionHasErrors('status');
});

it('mengurutkan jadwal terdekat lebih dulu bila diminta', function () {
    $jauh = bookingDaftar(['booking_date' => UJI_SABTU, 'booking_time' => '09:00']);
    $dekat = bookingDaftar(['booking_date' => UJI_BESOK, 'booking_time' => '09:00']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index', ['urutan' => 'terdekat']))
        ->assertInertia(fn ($page) => $page
            ->where('bookings.data.0.booking_code', $dekat->booking_code)
            ->where('bookings.data.1.booking_code', $jauh->booking_code));

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index'))
        ->assertInertia(fn ($page) => $page
            ->where('bookings.data.0.booking_code', $jauh->booking_code));
});

it('memaginasi daftar 25 baris per halaman', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

    // Kuota slot tidak berlaku untuk data factory; yang diuji di sini
    // paginasinya, bukan aturan slotnya.
    foreach (range(1, 26) as $urutan) {
        Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'booking_date' => UJI_BESOK,
            'booking_time' => '09:00',
        ]);
    }

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index'))
        ->assertInertia(fn ($page) => $page
            ->has('bookings.data', 25)
            ->where('bookings.total', 26)
            ->where('bookings.last_page', 2));
});

it('tidak membocorkan catatan internal ke daftar', function () {
    bookingDaftar(['booking_time' => '09:00', 'admin_note' => 'Pelanggan sering telat.']);

    // Catatan internal hanya muncul di detail, bukan di setiap baris daftar —
    // props yang tidak dibutuhkan tetap props yang terkirim.
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.index'))
        ->assertInertia(fn ($page) => $page->missing('bookings.data.0.admin_note'));
});
