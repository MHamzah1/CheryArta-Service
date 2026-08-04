<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ServicePackage;
use Spatie\Activitylog\Models\Activity;

/*
| A12 — Activity Log (docs/07 §A12, roadmap 2.2.6).
|
| Dua hal yang paling mudah salah dan paling mahal akibatnya: log yang
| membocorkan password, dan log yang menduplikasi riwayat status booking
| sehingga ada dua sumber kebenaran untuk satu fakta (cacat B2 sistem lama).
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

it('mencatat perubahan paket layanan beserta pelakunya', function () {
    $sa = superAdmin();
    $paket = ServicePackage::factory()->create(['name' => 'Servis Berkala 10.000 KM']);

    $this->actingAs($sa)->put(route('admin.service-packages.update', $paket), [
        'code' => $paket->code,
        'name' => 'Servis Berkala 20.000 KM',
        'category' => $paket->category->value,
        'description' => $paket->description,
        'applicable_series' => [],
        'estimated_duration_minutes' => $paket->estimated_duration_minutes,
        'price' => $paket->price,
        'is_free' => $paket->is_free,
        'is_active' => true,
        'sort_order' => $paket->sort_order,
    ])->assertRedirect();

    $log = Activity::query()->where('subject_type', ServicePackage::class)->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->causer_id)->toBe($sa->id)
        ->and($log->properties->get('attributes')['name'])->toBe('Servis Berkala 20.000 KM')
        ->and($log->properties->get('old')['name'])->toBe('Servis Berkala 10.000 KM');
});

it('mencatat perubahan akun staf', function () {
    $sa = superAdmin();
    $advisor = serviceAdvisor(['name' => 'Nama Lama']);

    $this->actingAs($sa)->put(route('admin.users.update', $advisor), [
        'name' => 'Nama Baru',
        'email' => $advisor->email,
        'phone_wa' => $advisor->phone_wa,
        'role' => 'service_advisor',
        'is_active' => true,
    ])->assertRedirect();

    $log = Activity::query()->where('subject_id', $advisor->id)->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->properties->get('attributes')['name'])->toBe('Nama Baru');
});

it('tidak pernah memuat password di isi log', function () {
    $sa = superAdmin();
    $advisor = serviceAdvisor();

    $this->actingAs($sa)->put(route('admin.users.reset-password', $advisor));

    $sementara = session('temporaryPassword')['password'];
    $seluruhLog = Activity::query()->get()->toJson();

    expect($seluruhLog)->not->toContain('password')
        ->and($seluruhLog)->not->toContain($sementara);
});

it('tidak mencatat perubahan status booking di activity log', function () {
    // Keputusan grill #3. Riwayat status tetap milik booking_status_histories
    // yang sudah dipakai detail booking sejak Tahap 6; menirunya di sini
    // membuat dua sumber kebenaran yang pasti akan berbeda suatu hari.
    $booking = bookingAdmin();
    $advisor = serviceAdvisor();

    // Dihitung khusus subjek Booking, bukan seluruh tabel: membuat akun
    // advisor untuk uji ini sendiri menghasilkan satu baris log, dan
    // menghitung semuanya membuat uji gagal karena sebab yang salah.
    $sebelum = Activity::query()->where('subject_type', Booking::class)->count();

    $this->actingAs($advisor)
        ->put(route('admin.bookings.status', $booking), [
            'status' => BookingStatus::Confirmed->value,
        ])
        ->assertRedirect();

    expect($booking->refresh()->status)->toBe(BookingStatus::Confirmed)
        ->and(Activity::query()->where('subject_type', Booking::class)->count())->toBe($sebelum)
        // Riwayatnya tetap tercatat — di tabel yang benar.
        ->and($booking->statusHistories()->count())->toBeGreaterThan(0);
});

it('menyaring log menurut pelaku', function () {
    $sa = superAdmin();
    $lain = superAdmin();

    ServicePackage::factory()->create();
    $paket = ServicePackage::factory()->create();

    $this->actingAs($lain)->put(route('admin.service-packages.update', $paket), [
        'code' => $paket->code,
        'name' => 'Diubah Orang Lain',
        'category' => $paket->category->value,
        'description' => $paket->description,
        'applicable_series' => [],
        'estimated_duration_minutes' => $paket->estimated_duration_minutes,
        'price' => $paket->price,
        'is_free' => $paket->is_free,
        'is_active' => true,
        'sort_order' => $paket->sort_order,
    ]);

    $this->actingAs($sa)
        ->get(route('admin.activity-log.index', ['causer' => $sa->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/activity-log/index')
            ->where('logs.data', fn ($rows) => collect($rows)->isEmpty()));
});

it('menolak rentang tanggal yang terbalik', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.activity-log.index', ['dari' => '2026-08-10', 'sampai' => '2026-08-01']))
        ->assertSessionHasErrors('sampai');
});

it('menolak advisor membuka activity log', function () {
    $this->actingAs(serviceAdvisor())->get(route('admin.activity-log.index'))->assertForbidden();
});
