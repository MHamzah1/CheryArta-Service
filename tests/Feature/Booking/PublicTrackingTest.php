<?php

declare(strict_types=1);

use App\Models\Booking;

/*
| Pelacakan publik & endpoint slot (roadmap 1.4.4 & 1.4.9).
|
| Keduanya terbuka tanpa login, jadi yang paling perlu dijaga adalah apa yang
| TIDAK ikut terkirim.
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

it('membuka halaman cek servis tanpa login', function () {
    $this->get(route('public.tracking'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('cek-service')
            ->where('booking', null)
            ->where('sudahDicari', false));
});

it('menampilkan jadwal dan status untuk kode booking yang sah', function () {
    $user = pelangganSiapBooking();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'vehicle_id' => $user->vehicles()->value('id'),
        'booking_code' => 'CA-20260804-0001',
        'booking_date' => UJI_BESOK,
    ]);

    $this->get(route('public.tracking', ['kode' => 'CA-20260804-0001']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('booking.booking_code', $booking->booking_code)
            ->where('booking.booking_date', UJI_BESOK)
            ->where('booking.status', 'pending')
            ->where('sudahDicari', true));
});

it('tidak membocorkan data pribadi lewat pelacakan publik', function () {
    // Fitur lama membiarkan siapa pun mengambil seluruh basis data lalu
    // menyaringnya di browser (temuan S8). Yang boleh keluar hanya kode,
    // jadwal, status, dan perkiraan selesai.
    $user = pelangganSiapBooking();
    Booking::factory()->create([
        'user_id' => $user->id,
        'vehicle_id' => $user->vehicles()->value('id'),
        'booking_code' => 'CA-20260804-0001',
        'booking_date' => UJI_BESOK,
        'complaint' => 'Rem depan berbunyi.',
    ]);

    $this->get(route('public.tracking', ['kode' => 'CA-20260804-0001']))
        ->assertInertia(fn ($page) => $page
            ->missing('booking.complaint')
            ->missing('booking.vehicle_plate')
            ->missing('booking.vehicle_model')
            ->missing('booking.user')
            ->missing('booking.odometer'));
});

it('menjawab kode booking yang tidak ada tanpa membocorkan apa pun', function () {
    $this->get(route('public.tracking', ['kode' => 'CA-20260804-9999']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('booking', null)
            ->where('sudahDicari', true));
});

it('mengembalikan ketersediaan slot dalam bentuk JSON', function () {
    Booking::factory()->count(2)->onSlot(UJI_BESOK, '09:00')->create();
    Booking::factory()->onSlot(UJI_BESOK, '10:00')->create();

    $response = $this->getJson(route('booking.slots', ['date' => UJI_BESOK]))->assertOk();

    $slots = collect($response->json('slots'));

    expect($response->json('is_bookable'))->toBeTrue()
        ->and($slots)->toHaveCount(11)
        ->and($slots->firstWhere('time', '09:00'))->toBe(['time' => '09:00', 'remaining' => 0, 'is_full' => true])
        ->and($slots->firstWhere('time', '10:00'))->toBe(['time' => '10:00', 'remaining' => 1, 'is_full' => false])
        ->and($slots->firstWhere('time', '11:00'))->toBe(['time' => '11:00', 'remaining' => 2, 'is_full' => false]);
});

it('mengembalikan sembilan slot untuk hari Sabtu', function () {
    $response = $this->getJson(route('booking.slots', ['date' => UJI_SABTU]))->assertOk();

    expect($response->json('slots'))->toHaveCount(9)
        ->and(collect($response->json('slots'))->pluck('time')->last())->toBe('13:00');
});

it('menjelaskan mengapa hari Minggu tidak bisa dipesan', function () {
    $response = $this->getJson(route('booking.slots', ['date' => UJI_MINGGU]))->assertOk();

    expect($response->json('is_bookable'))->toBeFalse()
        ->and($response->json('reason'))->toContain('tutup')
        ->and($response->json('slots'))->toBe([]);
});

it('menjelaskan mengapa hari ini tidak bisa dipesan', function () {
    $response = $this->getJson(route('booking.slots', ['date' => UJI_HARI_INI]))->assertOk();

    expect($response->json('is_bookable'))->toBeFalse()
        ->and($response->json('reason'))->toContain('H-1');
});

it('menolak permintaan slot tanpa tanggal', function () {
    $this->getJson(route('booking.slots'))->assertStatus(422);
});

it('menolak format tanggal yang tidak dikenali', function () {
    $this->getJson(route('booking.slots', ['date' => '04-08-2026']))->assertStatus(422);
});

it('tidak membocorkan identitas pemesan lewat endpoint slot', function () {
    $user = pelangganSiapBooking();
    Booking::factory()->onSlot(UJI_BESOK, '09:00')->create([
        'user_id' => $user->id,
        'vehicle_id' => $user->vehicles()->value('id'),
    ]);

    $isi = $this->getJson(route('booking.slots', ['date' => UJI_BESOK]))->getContent();

    expect($isi)->not->toContain($user->name)
        ->not->toContain($user->email);
});
