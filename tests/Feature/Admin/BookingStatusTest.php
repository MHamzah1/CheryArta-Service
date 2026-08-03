<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SlotService;

/*
| A2 — panel ubah status (roadmap 1.5.3, docs/05 §5.3).
|
| Yang dijaga: SETIAP transisi sah berhasil, setiap transisi tidak sah ditolak
| server (bukan sekadar disembunyikan di UI), dan tiap perubahan meninggalkan
| tepat satu baris riwayat. Sistem lama tidak punya jejak perubahan sama
| sekali.
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function bookingStatus(BookingStatus $status, array $timpa = []): Booking
{
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'last_odometer' => null]);

    return Booking::factory()->create([
        'user_id' => $user->id,
        'vehicle_id' => $vehicle->id,
        'service_package_id' => ServicePackage::factory()->create(['estimated_duration_minutes' => 90])->id,
        'booking_date' => UJI_BESOK,
        // Diisi seperti yang dilakukan BookingService::create(), supaya uji
        // "dihitung ulang" benar-benar membandingkan dua nilai.
        'estimated_finish_at' => UJI_BESOK.' 10:30:00',
        'status' => $status,
        ...$timpa,
    ]);
}

dataset('transisi sah', [
    'pending → confirmed' => [BookingStatus::Pending, BookingStatus::Confirmed],
    'pending → cancelled' => [BookingStatus::Pending, BookingStatus::Cancelled],
    'confirmed → in_progress' => [BookingStatus::Confirmed, BookingStatus::InProgress],
    'confirmed → cancelled' => [BookingStatus::Confirmed, BookingStatus::Cancelled],
    'confirmed → no_show' => [BookingStatus::Confirmed, BookingStatus::NoShow],
    'in_progress → completed' => [BookingStatus::InProgress, BookingStatus::Completed],
    'in_progress → cancelled' => [BookingStatus::InProgress, BookingStatus::Cancelled],
]);

dataset('transisi tidak sah', [
    'pending → in_progress' => [BookingStatus::Pending, BookingStatus::InProgress],
    'pending → completed' => [BookingStatus::Pending, BookingStatus::Completed],
    'pending → no_show' => [BookingStatus::Pending, BookingStatus::NoShow],
    'confirmed → completed' => [BookingStatus::Confirmed, BookingStatus::Completed],
    'completed → cancelled' => [BookingStatus::Completed, BookingStatus::Cancelled],
    'cancelled → confirmed' => [BookingStatus::Cancelled, BookingStatus::Confirmed],
    'no_show → confirmed' => [BookingStatus::NoShow, BookingStatus::Confirmed],
]);

it('menjalankan setiap transisi yang sah', function (BookingStatus $dari, BookingStatus $ke) {
    $booking = bookingStatus($dari);

    $this->actingAs(serviceAdvisor())
        ->from(route('admin.bookings.show', $booking))
        ->put(route('admin.bookings.status', $booking), [
            'status' => $ke->value,
            'note' => 'Dicatat oleh advisor.',
        ])
        ->assertRedirect(route('admin.bookings.show', $booking));

    expect($booking->refresh()->status)->toBe($ke);
})->with('transisi sah');

it('menolak setiap transisi yang tidak sah dengan 422', function (BookingStatus $dari, BookingStatus $ke) {
    $booking = bookingStatus($dari);

    $this->actingAs(serviceAdvisor())
        ->putJson(route('admin.bookings.status', $booking), [
            'status' => $ke->value,
            'note' => 'Alasan apa pun.',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');

    expect($booking->refresh()->status)->toBe($dari);
})->with('transisi tidak sah');

it('mencatat satu baris riwayat untuk tiap perubahan status', function () {
    $advisor = serviceAdvisor(['name' => 'Andre']);
    $booking = bookingStatus(BookingStatus::Pending);
    $riwayatAwal = $booking->statusHistories()->count();

    $this->actingAs($advisor)->put(route('admin.bookings.status', $booking), [
        'status' => 'confirmed',
        'note' => 'Sampai jumpa besok pukul 09.00.',
    ]);

    $riwayat = $booking->statusHistories()->latest('id')->first();

    expect($booking->statusHistories()->count())->toBe($riwayatAwal + 1)
        ->and($riwayat->from_status)->toBe(BookingStatus::Pending)
        ->and($riwayat->to_status)->toBe(BookingStatus::Confirmed)
        ->and($riwayat->changed_by)->toBe($advisor->id)
        ->and($riwayat->note)->toBe('Sampai jumpa besok pukul 09.00.');
});

it('mewajibkan alasan saat membatalkan', function () {
    $booking = bookingStatus(BookingStatus::Confirmed);

    $this->actingAs(serviceAdvisor())
        ->putJson(route('admin.bookings.status', $booking), ['status' => 'cancelled'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('note');

    expect($booking->refresh()->status)->toBe(BookingStatus::Confirmed);
});

it('menyimpan alasan pembatalan ke kolom cancel_reason', function () {
    $booking = bookingStatus(BookingStatus::Confirmed);

    $this->actingAs(serviceAdvisor())->put(route('admin.bookings.status', $booking), [
        'status' => 'cancelled',
        'note' => 'Pelanggan meminta digeser ke minggu depan.',
    ]);

    expect($booking->refresh()->cancel_reason)->toBe('Pelanggan meminta digeser ke minggu depan.')
        ->and($booking->cancelled_at)->not->toBeNull();
});

it('mengisi confirmed_at dan menandai advisor penanggung jawab', function () {
    $advisor = serviceAdvisor();
    $booking = bookingStatus(BookingStatus::Pending);

    $this->actingAs($advisor)->put(route('admin.bookings.status', $booking), ['status' => 'confirmed']);

    expect($booking->refresh()->confirmed_at)->not->toBeNull()
        ->and($booking->handled_by)->toBe($advisor->id);
});

it('menghitung ulang perkiraan selesai saat kendaraan mulai dikerjakan', function () {
    // Janji yang ditinggalkan F1.4: mobil yang baru masuk pukul 09.00 hari ini
    // tidak selesai menurut jadwal slot besok pukul 09.00.
    $booking = bookingStatus(BookingStatus::Confirmed);
    $sebelum = $booking->estimated_finish_at;

    $this->actingAs(serviceAdvisor())->put(route('admin.bookings.status', $booking), ['status' => 'in_progress']);

    $booking->refresh();

    // Waktu uji dibekukan pada 3 Agustus 2026 pukul 09.00; durasi paket 90 menit.
    expect($booking->started_at)->not->toBeNull()
        ->and($booking->estimated_finish_at->format('Y-m-d H:i'))->toBe('2026-08-03 10:30')
        ->and($booking->estimated_finish_at->equalTo($sebelum))->toBeFalse();
});

it('memperbarui odometer kendaraan saat pekerjaan selesai', function () {
    $booking = bookingStatus(BookingStatus::InProgress);

    $this->actingAs(serviceAdvisor())->put(route('admin.bookings.status', $booking), [
        'status' => 'completed',
        'odometer' => 24500,
    ]);

    expect($booking->refresh()->odometer)->toBe(24500)
        ->and($booking->vehicle->refresh()->last_odometer)->toBe(24500)
        ->and($booking->completed_at)->not->toBeNull();
});

it('menolak odometer yang lebih kecil daripada catatan terakhir kendaraan', function () {
    $booking = bookingStatus(BookingStatus::InProgress);
    $booking->vehicle->forceFill(['last_odometer' => 30000])->save();

    $this->actingAs(serviceAdvisor())
        ->putJson(route('admin.bookings.status', $booking), ['status' => 'completed', 'odometer' => 12000])
        ->assertStatus(422)
        ->assertJsonValidationErrors('odometer');

    expect($booking->refresh()->status)->toBe(BookingStatus::InProgress);
});

it('melepas kuota slot setelah booking dibatalkan', function () {
    // Kuota 2 per slot; dua booking mengisinya penuh, satu dibatalkan,
    // sehingga slotnya kembali punya sisa. Sistem lama menghitung booking
    // batal sebagai terisi sehingga slot bisa "penuh palsu" (docs/05 §5.1).
    $slots = app(SlotService::class);
    $besok = $slots->parseDate(UJI_BESOK);

    $pertama = bookingStatus(BookingStatus::Confirmed, ['booking_time' => '09:00']);
    bookingStatus(BookingStatus::Confirmed, ['booking_time' => '09:00']);

    expect($slots->remaining($besok, '09:00'))->toBe(0);

    $this->actingAs(serviceAdvisor())->put(route('admin.bookings.status', $pertama), [
        'status' => 'cancelled',
        'note' => 'Pelanggan berubah rencana.',
    ]);

    expect($slots->remaining($besok, '09:00'))->toBe(1);
});

it('tidak menawarkan satu pun transisi untuk booking yang sudah berakhir', function () {
    // Advisornya tetap berhak (canUpdateStatus true) — yang habis adalah
    // pilihan tujuannya. Percobaan menembak rutenya berujung 422, bukan 403;
    // lihat uji "menolak setiap transisi yang tidak sah" di atas.
    $booking = bookingStatus(BookingStatus::Completed);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(fn ($page) => $page
            ->component('admin/bookings/show')
            ->where('canUpdateStatus', true)
            ->has('statusOptions', 0));
});

it('mengirim hanya transisi yang sah sebagai pilihan di detail', function () {
    $booking = bookingStatus(BookingStatus::Confirmed);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(fn ($page) => $page
            ->where('canUpdateStatus', true)
            ->has('statusOptions', 3)
            ->where('statusOptions.0.value', 'in_progress')
            ->where('statusOptions.1.value', 'cancelled')
            ->where('statusOptions.1.requires_note', true));
});

it('menyiapkan draft WhatsApp berisi kode booking di detail', function () {
    $booking = bookingStatus(BookingStatus::Confirmed);
    $booking->user->forceFill(['phone_wa' => '081234567890'])->save();

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(fn ($page) => $page
            ->where('whatsapp.phone_display', '0812-3456-7890')
            ->where('whatsapp.message', fn (string $pesan) => str_contains($pesan, $booking->booking_code))
            ->where('whatsapp.url', fn (string $url) => str_starts_with($url, 'https://wa.me/6281234567890?text=')));
});

it('tidak menyiapkan draft WhatsApp bila pelanggan tanpa nomor', function () {
    $booking = bookingStatus(BookingStatus::Confirmed);
    $booking->user->forceFill(['phone_wa' => null])->save();

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(fn ($page) => $page->where('whatsapp', null));
});
