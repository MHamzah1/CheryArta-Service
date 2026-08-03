<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

/*
| Booking end-to-end lewat rute sungguhan (roadmap 1.4.5, 1.4.6, 1.4.10).
|
| Uji menembak rute, bukan memanggil service langsung — dengan begitu Form
| Request, Policy, dan Service ikut teruji sebagaimana dipakai di produksi
| (.claude/rules/60-testing-dan-git.md).
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

it('mengalihkan tamu ke halaman masuk saat membuka form booking', function () {
    $this->get(route('customer.booking.create'))->assertRedirect(route('login'));
});

it('menampilkan form booking beserta kendaraan dan paket layanan', function () {
    $user = pelangganSiapBooking();
    ServicePackage::factory()->create();

    $this->actingAs($user)
        ->get(route('customer.booking.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('booking/create')
            ->has('vehicles', 1)
            ->has('servicePackages', 1)
            ->where('slotRules.earliest_date', UJI_BESOK));
});

it('tidak menawarkan paket layanan yang sudah dinonaktifkan', function () {
    $user = pelangganSiapBooking();
    ServicePackage::factory()->inactive()->create();

    $this->actingAs($user)
        ->get(route('customer.booking.create'))
        ->assertInertia(fn ($page) => $page->has('servicePackages', 0));
});

it('menyimpan booking dan mengalihkan ke halaman sukses', function () {
    $user = pelangganSiapBooking();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user))
        ->assertSessionHasNoErrors();

    $booking = Booking::query()->firstOrFail();

    expect($booking->user_id)->toBe($user->id)
        ->and($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->booking_date->toDateString())->toBe(UJI_BESOK)
        ->and($booking->booking_time)->toBe('09:00:00');
});

it('menerbitkan kode booking berformat CA-YYYYMMDD-NNNN', function () {
    $user = pelangganSiapBooking();

    $this->actingAs($user)->post(route('customer.booking.store'), dataBooking($user));

    expect(Booking::query()->value('booking_code'))->toBe('CA-20260804-0001');
});

it('menaikkan nomor urut kode booking pada tanggal yang sama', function () {
    $user = pelangganSiapBooking();

    $this->actingAs($user)->post(route('customer.booking.store'), dataBooking($user));
    $this->actingAs($user)->post(route('customer.booking.store'), dataBooking($user, ['booking_time' => '10:00']));

    expect(Booking::query()->orderBy('id')->pluck('booking_code')->all())
        ->toBe(['CA-20260804-0001', 'CA-20260804-0002']);
});

it('mencatat satu baris riwayat status saat booking dibuat', function () {
    $user = pelangganSiapBooking();

    $this->actingAs($user)->post(route('customer.booking.store'), dataBooking($user));

    $riwayat = Booking::query()->firstOrFail()->statusHistories;

    expect($riwayat)->toHaveCount(1)
        ->and($riwayat->first()->from_status)->toBeNull()
        ->and($riwayat->first()->to_status)->toBe(BookingStatus::Pending);
});

it('menghitung perkiraan selesai dari durasi paket layanan', function () {
    $user = pelangganSiapBooking();
    $paket = ServicePackage::factory()->create(['estimated_duration_minutes' => 90]);

    $this->actingAs($user)->post(route('customer.booking.store'), dataBooking($user, [
        'service_package_id' => $paket->id,
    ]));

    expect(Booking::query()->firstOrFail()->estimated_finish_at->format('Y-m-d H:i'))
        ->toBe(UJI_BESOK.' 10:30');
});

it('menolak booking untuk hari ini karena aturan H-1', function () {
    $user = pelangganSiapBooking();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user, ['booking_date' => UJI_HARI_INI]))
        ->assertSessionHasErrors('booking_date');

    expect(Booking::query()->count())->toBe(0);
});

it('menolak booking pada hari Minggu', function () {
    $user = pelangganSiapBooking();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user, ['booking_date' => UJI_MINGGU]))
        ->assertSessionHasErrors('booking_date');

    expect(Booking::query()->count())->toBe(0);
});

it('menolak booking lebih dari 60 hari ke depan', function () {
    $user = pelangganSiapBooking();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user, ['booking_date' => UJI_TERLALU_JAUH]))
        ->assertSessionHasErrors('booking_date');

    expect(Booking::query()->count())->toBe(0);
});

it('menolak jam 13:30 pada hari Sabtu', function () {
    // Keputusan R7: slot Sabtu berhenti di 13:00 (docs/05 §Catatan konflik jam Sabtu).
    $user = pelangganSiapBooking();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user, [
            'booking_date' => UJI_SABTU,
            'booking_time' => '13:30',
        ]))
        ->assertSessionHasErrors('booking_time');

    expect(Booking::query()->count())->toBe(0);
});

it('menerima jam 13:00 pada hari Sabtu', function () {
    $user = pelangganSiapBooking();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user, [
            'booking_date' => UJI_SABTU,
            'booking_time' => '13:00',
        ]))
        ->assertSessionHasNoErrors();

    expect(Booking::query()->count())->toBe(1);
});

it('menolak jam yang bukan slot resmi', function () {
    $user = pelangganSiapBooking();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user, ['booking_time' => '12:00']))
        ->assertSessionHasErrors('booking_time');
});

it('menolak booking saat kuota slot sudah penuh', function () {
    $user = pelangganSiapBooking();
    Booking::factory()->count(2)->onSlot(UJI_BESOK, '09:00')->create();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user))
        ->assertSessionHasErrors('booking_time');

    expect(Booking::query()->count())->toBe(2);
});

it('menyebutkan jam pengganti ketika slot pilihan sudah penuh', function () {
    // "Jam 09:00 sudah penuh. Tersisa: …" — bukan sekadar alert('Slot penuh')
    // seperti sistem lama (docs/05 §5.8).
    $user = pelangganSiapBooking();
    Booking::factory()->count(2)->onSlot(UJI_BESOK, '09:00')->create();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user))
        ->assertSessionHasErrors('booking_time');

    expect(session('errors')->first('booking_time'))
        ->toContain('penuh')
        ->toContain('09:30');
});

it('menerima booking ketiga pada slot yang sama setelah satu dibatalkan', function () {
    $user = pelangganSiapBooking();
    Booking::factory()->onSlot(UJI_BESOK, '09:00')->create();
    Booking::factory()->onSlot(UJI_BESOK, '09:00')->status(BookingStatus::Cancelled)->create();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user))
        ->assertSessionHasNoErrors();
});

it('tidak pernah menembus kuota meski permintaan datang beruntun', function () {
    $user = pelangganSiapBooking();

    // Tiga permintaan pada slot yang sama; yang ketiga wajib ditolak.
    foreach (range(1, 3) as $ignored) {
        $this->actingAs($user)->post(route('customer.booking.store'), dataBooking($user));
    }

    expect(Booking::query()->occupyingQuota()->onSlot(UJI_BESOK, '09:00')->count())->toBe(2);
});

it('menghitung kuota di dalam transaksi, bukan sebelum transaksi dibuka', function () {
    /*
     | Inilah sifat yang membuat dua permintaan bersamaan tidak bisa menembus
     | kuota: pemeriksaan dan penulisan berada dalam SATU transaksi, dengan
     | `lockForUpdate()` pada kueri hitungnya (docs/05 §5.1).
     |
     | Penguncian barisnya sendiri hanya berlaku nyata di MySQL — SQLite yang
     | dipakai uji mengabaikan klausa itu. Yang bisa dan perlu dibuktikan di
     | sini adalah keatomikannya: kueri hitung berjalan saat transaksi sudah
     | terbuka, sehingga di MySQL kuncinya benar-benar menahan penulis lain.
     */
    $user = pelangganSiapBooking();
    $tingkatTransaksi = [];

    DB::listen(function ($query) use (&$tingkatTransaksi): void {
        if (str_contains($query->sql, 'bookings') && str_contains(strtolower($query->sql), 'count(*)')) {
            $tingkatTransaksi[] = DB::transactionLevel();
        }
    });

    $this->actingAs($user)->post(route('customer.booking.store'), dataBooking($user));

    expect($tingkatTransaksi)->not->toBeEmpty()
        ->and(min($tingkatTransaksi))->toBeGreaterThan(0);
});

it('mengulang penerbitan kode ketika nomor urutnya keburu dipakai permintaan lain', function () {
    /*
     | Nomor urut dikunci lewat baris terakhir hari itu — tetapi baris yang
     | BELUM ADA tidak bisa dikunci. Dua permintaan pertama pada tanggal yang
     | sama karena itu bisa menyusun `-0001` bersamaan.
     |
     | Di sini keadaan itu dibuat sengaja: tepat sebelum penyimpanan, kode yang
     | baru diterbitkan diserobot baris lain. Yang diuji adalah pemulihannya —
     | pengguna tetap mendapat booking, bukan halaman galat.
     */
    $user = pelangganSiapBooking();
    $sudahMenyerobot = false;

    Booking::creating(function (Booking $booking) use (&$sudahMenyerobot): void {
        if ($sudahMenyerobot) {
            return;
        }

        $sudahMenyerobot = true;

        DB::table('bookings')->insert([
            'booking_code' => $booking->booking_code,
            'user_id' => $booking->user_id,
            'vehicle_id' => $booking->vehicle_id,
            'service_package_id' => $booking->service_package_id,
            'booking_date' => $booking->booking_date->toDateString(),
            'booking_time' => $booking->booking_time,
            'status' => 'pending',
            'source' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user))
        ->assertSessionHasNoErrors();

    expect($sudahMenyerobot)->toBeTrue()
        ->and(Booking::query()->count())->toBe(1);
});

it('menolak kendaraan milik pengguna lain', function () {
    $user = pelangganSiapBooking();
    $milikOrangLain = Vehicle::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user, ['vehicle_id' => $milikOrangLain->id]))
        ->assertSessionHasErrors('vehicle_id');

    expect(Booking::query()->count())->toBe(0);
});

it('menolak paket layanan yang sudah dinonaktifkan', function () {
    $user = pelangganSiapBooking();
    $paket = ServicePackage::factory()->inactive()->create();

    $this->actingAs($user)
        ->post(route('customer.booking.store'), dataBooking($user, ['service_package_id' => $paket->id]))
        ->assertSessionHasErrors('service_package_id');
});

it('mengabaikan status dan kode booking yang dikirim dari request', function () {
    // Keduanya ditentukan server — menerimanya dari request adalah larangan
    // mutlak (.claude/rules/50-keamanan.md).
    $user = pelangganSiapBooking();

    $this->actingAs($user)->post(route('customer.booking.store'), dataBooking($user, [
        'status' => 'completed',
        'booking_code' => 'CA-19700101-9999',
        'user_id' => User::factory()->create()->id,
    ]));

    $booking = Booking::query()->firstOrFail();

    expect($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->booking_code)->toBe('CA-20260804-0001')
        ->and($booking->user_id)->toBe($user->id);
});

it('menampilkan halaman sukses berisi kode booking', function () {
    $user = pelangganSiapBooking();
    $this->actingAs($user)->post(route('customer.booking.store'), dataBooking($user));

    $booking = Booking::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('customer.booking.success', $booking))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('booking/sukses')
            ->where('booking.booking_code', $booking->booking_code));
});
