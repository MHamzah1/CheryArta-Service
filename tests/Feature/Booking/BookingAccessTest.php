<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\User;

/*
| Kepemilikan booking (roadmap 1.4.10).
|
| Sistem lama menentukan hak akses dari localStorage di browser, sehingga siapa
| pun bisa melihat data siapa pun (temuan S3 & S8). Uji di sini menjaga bahwa
| penolakannya terjadi di server, bukan dengan menyembunyikan tautan.
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function bookingMilik(User $user): Booking
{
    return Booking::factory()->create([
        'user_id' => $user->id,
        'vehicle_id' => $user->vehicles()->value('id'),
        'booking_date' => UJI_BESOK,
    ]);
}

it('mengalihkan tamu ke halaman masuk saat membuka detail booking', function () {
    $booking = bookingMilik(pelangganSiapBooking());

    $this->get(route('customer.booking.show', $booking))->assertRedirect(route('login'));
});

it('menampilkan detail booking kepada pemiliknya', function () {
    $user = pelangganSiapBooking();
    $booking = bookingMilik($user);

    $this->actingAs($user)
        ->get(route('customer.booking.show', $booking))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('booking/show')
            ->where('booking.booking_code', $booking->booking_code)
            ->has('timeline'));
});

it('menolak pengguna membuka booking milik orang lain', function () {
    $booking = bookingMilik(pelangganSiapBooking());

    $this->actingAs(User::factory()->create())
        ->get(route('customer.booking.show', $booking))
        ->assertForbidden();
});

it('menolak pengguna membuka halaman sukses booking orang lain', function () {
    $booking = bookingMilik(pelangganSiapBooking());

    $this->actingAs(User::factory()->create())
        ->get(route('customer.booking.success', $booking))
        ->assertForbidden();
});

it('menolak pengguna menjadwal ulang booking orang lain', function () {
    $booking = bookingMilik(pelangganSiapBooking());

    $this->actingAs(User::factory()->create())
        ->put(route('customer.booking.reschedule', $booking), [
            'booking_date' => UJI_BESOK,
            'booking_time' => '10:00',
        ])
        ->assertForbidden();

    expect($booking->refresh()->booking_time)->toBe('09:00:00');
});

it('menolak pengguna membatalkan booking orang lain', function () {
    $booking = bookingMilik(pelangganSiapBooking());

    $this->actingAs(User::factory()->create())
        ->put(route('customer.booking.cancel', $booking), ['reason_choice' => 'Berubah rencana'])
        ->assertForbidden();

    expect($booking->refresh()->status->value)->toBe('pending');
});

it('hanya menampilkan booking milik pengguna yang masuk di halaman riwayat', function () {
    $saya = pelangganSiapBooking();
    $milikSaya = bookingMilik($saya);
    bookingMilik(pelangganSiapBooking());

    $this->actingAs($saya)
        ->get(route('customer.history.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('riwayat/index')
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_code', $milikSaya->booking_code));
});

it('menyaring riwayat berdasarkan kendaraan', function () {
    $user = pelangganSiapBooking();
    $bookingPertama = bookingMilik($user);

    $kendaraanKedua = App\Models\Vehicle::factory()->create([
        'user_id' => $user->id,
        'plate_number' => '5678',
    ]);
    Booking::factory()->create([
        'user_id' => $user->id,
        'vehicle_id' => $kendaraanKedua->id,
        'booking_date' => UJI_BESOK,
        'booking_time' => '10:00',
    ]);

    $this->actingAs($user)
        ->get(route('customer.history.index', ['kendaraan' => $bookingPertama->vehicle_id]))
        ->assertInertia(fn ($page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_code', $bookingPertama->booking_code));
});

it('tidak membocorkan catatan internal admin ke props customer', function () {
    $user = pelangganSiapBooking();
    $booking = bookingMilik($user);
    $booking->forceFill(['admin_note' => 'Pelanggan sering telat.'])->save();

    $this->actingAs($user)
        ->get(route('customer.booking.show', $booking))
        ->assertInertia(fn ($page) => $page->missing('booking.admin_note'));
});
