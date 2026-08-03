<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;

/*
| Jadwal ulang & pembatalan mandiri (US-C6, docs/05 §5.4, roadmap 1.4.8).
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

/** Booking milik $user yang masih boleh diubah: besok, status pending. */
function bookingBisaDiubah(App\Models\User $user, array $timpa = []): Booking
{
    return Booking::factory()->create(array_merge([
        'user_id' => $user->id,
        'vehicle_id' => $user->vehicles()->value('id'),
        'booking_date' => UJI_BESOK,
        'booking_time' => '09:00',
    ], $timpa));
}

it('memindahkan jadwal dengan membuat booking baru dan membatalkan yang lama', function () {
    $user = pelangganSiapBooking();
    $lama = bookingBisaDiubah($user);

    $this->actingAs($user)
        ->put(route('customer.booking.reschedule', $lama), [
            'booking_date' => UJI_BESOK,
            'booking_time' => '10:30',
        ])
        ->assertSessionHasNoErrors();

    $baru = Booking::query()->where('rescheduled_from_id', $lama->id)->firstOrFail();

    expect($lama->refresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($lama->cancel_reason)->toContain('Dijadwalkan ulang')
        ->and($baru->status)->toBe(BookingStatus::Pending)
        ->and($baru->booking_time)->toBe('10:30:00')
        ->and($baru->booking_code)->not->toBe($lama->booking_code);
});

it('mempertahankan kendaraan dan paket layanan saat dijadwal ulang', function () {
    $user = pelangganSiapBooking();
    $lama = bookingBisaDiubah($user, ['complaint' => 'Rem berbunyi.', 'odometer' => 33000]);

    $this->actingAs($user)->put(route('customer.booking.reschedule', $lama), [
        'booking_date' => UJI_BESOK,
        'booking_time' => '11:00',
    ]);

    $baru = Booking::query()->where('rescheduled_from_id', $lama->id)->firstOrFail();

    expect($baru->vehicle_id)->toBe($lama->vehicle_id)
        ->and($baru->service_package_id)->toBe($lama->service_package_id)
        ->and($baru->complaint)->toBe('Rem berbunyi.')
        ->and($baru->odometer)->toBe(33000);
});

it('mencatat riwayat status pada booking lama dan baru', function () {
    $user = pelangganSiapBooking();
    $lama = bookingBisaDiubah($user);

    $this->actingAs($user)->put(route('customer.booking.reschedule', $lama), [
        'booking_date' => UJI_BESOK,
        'booking_time' => '11:30',
    ]);

    $baru = Booking::query()->where('rescheduled_from_id', $lama->id)->firstOrFail();

    expect($lama->statusHistories()->count())->toBe(1)
        ->and($lama->statusHistories()->first()->to_status)->toBe(BookingStatus::Cancelled)
        ->and($baru->statusHistories()->count())->toBe(1)
        ->and($baru->statusHistories()->value('note'))->toContain('Dijadwalkan ulang dari');
});

it('melepaskan kuota slot lama setelah dijadwal ulang', function () {
    $user = pelangganSiapBooking();
    $lama = bookingBisaDiubah($user);
    Booking::factory()->onSlot(UJI_BESOK, '09:00')->create();

    $this->actingAs($user)->put(route('customer.booking.reschedule', $lama), [
        'booking_date' => UJI_BESOK,
        'booking_time' => '10:00',
    ]);

    expect(Booking::query()->occupyingQuota()->onSlot(UJI_BESOK, '09:00')->count())->toBe(1);
});

it('mengizinkan pindah ke slot yang sudah terisi satu booking lain', function () {
    $user = pelangganSiapBooking();
    $lama = bookingBisaDiubah($user);
    Booking::factory()->onSlot(UJI_BESOK, '10:00')->create();

    $this->actingAs($user)
        ->put(route('customer.booking.reschedule', $lama), [
            'booking_date' => UJI_BESOK,
            'booking_time' => '10:00',
        ])
        ->assertSessionHasNoErrors();
});

it('menolak pindah ke slot yang sudah penuh', function () {
    $user = pelangganSiapBooking();
    $lama = bookingBisaDiubah($user);
    Booking::factory()->count(2)->onSlot(UJI_BESOK, '10:00')->create();

    $this->actingAs($user)
        ->put(route('customer.booking.reschedule', $lama), [
            'booking_date' => UJI_BESOK,
            'booking_time' => '10:00',
        ])
        ->assertSessionHasErrors('booking_time');

    expect($lama->refresh()->status)->toBe(BookingStatus::Pending);
});

it('menolak pindah ke hari Minggu', function () {
    $user = pelangganSiapBooking();
    $lama = bookingBisaDiubah($user);

    $this->actingAs($user)
        ->put(route('customer.booking.reschedule', $lama), [
            'booking_date' => UJI_MINGGU,
            'booking_time' => '09:00',
        ])
        ->assertSessionHasErrors('booking_date');
});

it('menolak jadwal ulang untuk booking hari ini karena sudah lewat batas H-1', function () {
    $user = pelangganSiapBooking();
    $bookingHariIni = bookingBisaDiubah($user, ['booking_date' => UJI_HARI_INI]);

    $this->actingAs($user)
        ->put(route('customer.booking.reschedule', $bookingHariIni), [
            'booking_date' => UJI_BESOK,
            'booking_time' => '10:00',
        ])
        ->assertForbidden();
});

it('menolak jadwal ulang untuk booking yang sudah selesai', function () {
    $user = pelangganSiapBooking();
    $selesai = bookingBisaDiubah($user, ['status' => BookingStatus::Completed]);

    $this->actingAs($user)
        ->put(route('customer.booking.reschedule', $selesai), [
            'booking_date' => UJI_BESOK,
            'booking_time' => '10:00',
        ])
        ->assertForbidden();
});

it('membatalkan booking beserta alasannya', function () {
    $user = pelangganSiapBooking();
    $booking = bookingBisaDiubah($user);

    $this->actingAs($user)
        ->put(route('customer.booking.cancel', $booking), ['reason_choice' => 'Berubah rencana'])
        ->assertSessionHasNoErrors();

    expect($booking->refresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->cancel_reason)->toBe('Berubah rencana')
        ->and($booking->cancelled_at)->not->toBeNull();
});

it('menggabungkan keterangan bebas ke dalam alasan pembatalan', function () {
    $user = pelangganSiapBooking();
    $booking = bookingBisaDiubah($user);

    $this->actingAs($user)->put(route('customer.booking.cancel', $booking), [
        'reason_choice' => 'Lainnya',
        'reason_note' => 'Mobil dipakai keluar kota.',
    ]);

    expect($booking->refresh()->cancel_reason)->toBe('Mobil dipakai keluar kota.');
});

it('mewajibkan alasan pembatalan', function () {
    $user = pelangganSiapBooking();
    $booking = bookingBisaDiubah($user);

    $this->actingAs($user)
        ->put(route('customer.booking.cancel', $booking), [])
        ->assertSessionHasErrors('reason_choice');

    expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
});

it('mewajibkan keterangan bila alasannya Lainnya', function () {
    $user = pelangganSiapBooking();
    $booking = bookingBisaDiubah($user);

    $this->actingAs($user)
        ->put(route('customer.booking.cancel', $booking), ['reason_choice' => 'Lainnya'])
        ->assertSessionHasErrors('reason_note');
});

it('melepaskan kuota slot setelah booking dibatalkan', function () {
    $user = pelangganSiapBooking();
    $booking = bookingBisaDiubah($user);
    Booking::factory()->onSlot(UJI_BESOK, '09:00')->create();

    $this->actingAs($user)->put(route('customer.booking.cancel', $booking), ['reason_choice' => 'Berubah rencana']);

    expect(Booking::query()->occupyingQuota()->onSlot(UJI_BESOK, '09:00')->count())->toBe(1);
});

it('mencatat riwayat status saat dibatalkan', function () {
    $user = pelangganSiapBooking();
    $booking = bookingBisaDiubah($user);

    $this->actingAs($user)->put(route('customer.booking.cancel', $booking), ['reason_choice' => 'Salah pilih jadwal']);

    $riwayat = $booking->statusHistories()->latest('id')->firstOrFail();

    expect($riwayat->from_status)->toBe(BookingStatus::Pending)
        ->and($riwayat->to_status)->toBe(BookingStatus::Cancelled)
        ->and($riwayat->changed_by)->toBe($user->id);
});

it('menyembunyikan tombol ubah jadwal saat batas H-1 sudah lewat', function () {
    $user = pelangganSiapBooking();
    $bookingHariIni = bookingBisaDiubah($user, ['booking_date' => UJI_HARI_INI]);

    $this->actingAs($user)
        ->get(route('customer.booking.show', $bookingHariIni))
        ->assertInertia(fn ($page) => $page
            ->where('canReschedule', false)
            ->where('canCancel', false));
});

it('menampilkan tombol ubah jadwal untuk booking yang masih jauh', function () {
    $user = pelangganSiapBooking();
    $booking = bookingBisaDiubah($user);

    $this->actingAs($user)
        ->get(route('customer.booking.show', $booking))
        ->assertInertia(fn ($page) => $page
            ->where('canReschedule', true)
            ->where('canCancel', true));
});
