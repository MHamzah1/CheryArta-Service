<?php

declare(strict_types=1);

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\Vehicle;

/*
| A2 — booking walk-in (roadmap 1.5.4, docs/07 §A2).
|
| Yang dijaga: aturan H-1 DILEWATI untuk walk-in, kuota slot TETAP berlaku, dan
| pembuatan akun + kendaraan + booking benar-benar satu transaksi — slot yang
| penuh tidak boleh meninggalkan akun setengah jadi di daftar pelanggan.
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

/**
 * Muatan form walk-in.
 *
 * Menyertakan `user_id` membuang blok `customer`, dan `vehicle_id` membuang
 * blok `vehicle` — persis seperti yang dilakukan form React lewat
 * `useForm().transform()`. Mengirim keduanya sekaligus akan memicu aturan
 * `required_with` pada blok yang justru tidak dipakai.
 *
 * @return array<string, mixed>
 */
function dataWalkIn(array $timpa = []): array
{
    $data = array_merge([
        'customer' => [
            'name' => 'Budi Santoso',
            'email' => 'budi@contoh.test',
            'phone_wa' => '0812-3456-7890',
        ],
        'vehicle' => [
            'car_model_id' => null,
            'model_name_manual' => 'Tiggo 8 Pro',
            'plate_prefix' => 'B',
            'plate_number' => '1234',
            'plate_suffix' => 'ABC',
            'year' => 2024,
        ],
        'service_package_id' => ServicePackage::factory()->create()->id,
        'booking_date' => UJI_HARI_INI,
        'booking_time' => '10:00',
        'odometer' => 12000,
        'complaint' => 'Servis berkala.',
    ], $timpa);

    if (isset($data['user_id'])) {
        unset($data['customer']);
    }

    if (isset($data['vehicle_id'])) {
        unset($data['vehicle']);
    }

    return $data;
}

it('membuat booking hari ini meski aturan H-1 berlaku untuk customer', function () {
    // Aturan H-1 ditegakkan untuk booking web (diuji di F1.4). Walk-in
    // melewatinya karena pelanggannya sudah berdiri di depan meja.
    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.store'), dataWalkIn())
        ->assertSessionHasNoErrors();

    $booking = Booking::query()->latest('id')->firstOrFail();

    expect($booking->booking_date->toDateString())->toBe(UJI_HARI_INI)
        ->and($booking->source)->toBe(BookingSource::WalkIn)
        ->and($booking->status)->toBe(BookingStatus::Confirmed);
});

it('menandai advisor pengisi sebagai penanggung jawab dan mengisi confirmed_at', function () {
    $advisor = serviceAdvisor();

    $this->actingAs($advisor)->post(route('admin.bookings.store'), dataWalkIn());

    $booking = Booking::query()->latest('id')->firstOrFail();

    expect($booking->handled_by)->toBe($advisor->id)
        ->and($booking->confirmed_at)->not->toBeNull();
});

it('mencatat satu baris riwayat berstatus confirmed, bukan dua', function () {
    $this->actingAs(serviceAdvisor())->post(route('admin.bookings.store'), dataWalkIn());

    $booking = Booking::query()->latest('id')->firstOrFail();
    $riwayat = $booking->statusHistories;

    expect($riwayat)->toHaveCount(1)
        ->and($riwayat->first()->from_status)->toBeNull()
        ->and($riwayat->first()->to_status)->toBe(BookingStatus::Confirmed);
});

it('membuatkan akun pelanggan baru dengan penanda harus atur ulang password', function () {
    $this->actingAs(serviceAdvisor())->post(route('admin.bookings.store'), dataWalkIn());

    $customer = User::query()->where('email', 'budi@contoh.test')->firstOrFail();

    expect($customer->must_reset_password)->toBeTrue()
        ->and($customer->isCustomer())->toBeTrue()
        // Nomor selalu tersimpan ternormalisasi, apa pun bentuk yang diketik.
        ->and($customer->phone_wa)->toBe('6281234567890');
});

it('mendaftarkan kendaraan baru sebagai kendaraan utama pelanggan', function () {
    $this->actingAs(serviceAdvisor())->post(route('admin.bookings.store'), dataWalkIn());

    $vehicle = Vehicle::query()->where('plate_full', 'B-1234-ABC')->firstOrFail();

    expect($vehicle->is_primary)->toBeTrue()
        ->and($vehicle->display_model)->toBe('Tiggo 8 Pro');
});

it('memakai kembali pelanggan dan kendaraan yang sudah terdaftar', function () {
    $pelanggan = pelangganSiapBooking();
    $kendaraan = $pelanggan->vehicles()->firstOrFail();

    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.store'), dataWalkIn([
            'user_id' => $pelanggan->id,
            'vehicle_id' => $kendaraan->id,
        ]))
        ->assertSessionHasNoErrors();

    $booking = Booking::query()->latest('id')->firstOrFail();

    expect($booking->user_id)->toBe($pelanggan->id)
        ->and($booking->vehicle_id)->toBe($kendaraan->id)
        ->and(User::query()->count())->toBe(2); // pelanggan + advisor, tanpa akun baru
});

it('menolak memesankan servis atas nama kendaraan pelanggan lain', function () {
    $pelanggan = pelangganSiapBooking();
    $orangLain = pelangganSiapBooking();

    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.store'), dataWalkIn([
            'user_id' => $pelanggan->id,
            'vehicle_id' => $orangLain->vehicles()->value('id'),
        ]))
        ->assertSessionHasErrors('vehicle_id');

    expect(Booking::query()->count())->toBe(0);
});

it('tetap menegakkan kuota slot untuk walk-in', function () {
    $paket = ServicePackage::factory()->create();

    // Kuota 2 per slot; slot 10:00 hari ini diisi penuh lebih dulu.
    foreach (range(1, 2) as $urutan) {
        $pemilik = pelangganSiapBooking();
        Booking::factory()->create([
            'user_id' => $pemilik->id,
            'vehicle_id' => $pemilik->vehicles()->value('id'),
            'booking_date' => UJI_HARI_INI,
            'booking_time' => '10:00',
            'status' => BookingStatus::Confirmed,
        ]);
    }

    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.store'), dataWalkIn(['service_package_id' => $paket->id]))
        ->assertSessionHasErrors('booking_time');

    // Yang paling penting: akun pelanggan baru TIDAK ikut tercipta.
    expect(User::query()->where('email', 'budi@contoh.test')->exists())->toBeFalse()
        ->and(Vehicle::query()->where('plate_full', 'B-1234-ABC')->exists())->toBeFalse();
});

it('tetap menolak hari Minggu untuk walk-in', function () {
    // Yang dilewati walk-in hanya aturan H-1. Bengkel tetap tutup hari Minggu.
    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.store'), dataWalkIn(['booking_date' => UJI_MINGGU]))
        ->assertSessionHasErrors('booking_date');

    expect(Booking::query()->count())->toBe(0);
});

it('tetap menolak jam Sabtu yang sudah ditutup keputusan R7', function () {
    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.store'), dataWalkIn(['booking_date' => UJI_SABTU, 'booking_time' => '13:30']))
        ->assertSessionHasErrors('booking_time');
});

it('menolak email yang sudah terdaftar sebagai pelanggan baru', function () {
    User::factory()->create(['email' => 'budi@contoh.test']);

    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.store'), dataWalkIn())
        ->assertSessionHasErrors('customer.email');
});

it('menolak plat kembar dalam satu akun pelanggan', function () {
    $pelanggan = pelangganSiapBooking();
    $pelanggan->vehicles()->firstOrFail()->forceFill([
        'plate_prefix' => 'B',
        'plate_number' => '1234',
        'plate_suffix' => 'ABC',
    ])->save();

    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.store'), dataWalkIn([
            'user_id' => $pelanggan->id,
        ]))
        ->assertSessionHasErrors('vehicle.plate_number');
});

it('menerima plat berprefiks satu huruf maupun dua huruf', function (string $prefix) {
    // Temuan B1: sistem lama memaksa dua huruf dan karena itu menolak plat
    // Jakarta, yang justru paling banyak.
    $data = dataWalkIn();
    $data['vehicle']['plate_prefix'] = $prefix;
    $data['customer']['email'] = strtolower($prefix).'@contoh.test';
    $data['customer']['phone_wa'] = '0812345678'.strlen($prefix).'0';

    $this->actingAs(serviceAdvisor())
        ->post(route('admin.bookings.store'), $data)
        ->assertSessionHasNoErrors();
})->with(['B', 'AB']);

it('menolak customer membuat booking walk-in', function () {
    $this->actingAs(customer())
        ->post(route('admin.bookings.store'), dataWalkIn())
        ->assertForbidden();

    expect(Booking::query()->count())->toBe(0);
});

it('menampilkan kendaraan pelanggan terpilih di form walk-in', function () {
    $pelanggan = pelangganSiapBooking(['name' => 'Rani Safitri']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.create', ['pelanggan' => $pelanggan->id, 'cari' => 'Rani']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/bookings/create')
            ->where('selectedCustomer.name', 'Rani Safitri')
            ->has('selectedCustomer.vehicles', 1)
            ->has('customerResults', 1)
            // Walk-in boleh hari ini — batasnya dihitung server, bukan React.
            ->where('slotRules.earliest_date', UJI_HARI_INI)
            ->where('slotRules.source', 'walk_in'));
});

it('mencari pelanggan lewat nomor WhatsApp dalam bentuk yang diketik', function () {
    User::factory()->create(['name' => 'Sinta', 'phone_wa' => '6281299887766']);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.create', ['cari' => '0812-9988-7766']))
        ->assertInertia(fn ($page) => $page
            ->has('customerResults', 1)
            ->where('customerResults.0.name', 'Sinta'));
});
