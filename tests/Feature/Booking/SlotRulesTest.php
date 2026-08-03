<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\SlotService;
use Carbon\CarbonImmutable;

/*
| Aturan slot (docs/05 §5.1), roadmap 1.4.3 & 1.4.10.
|
| Uji di berkas ini menembak SlotService langsung karena inilah satu-satunya
| tempat aturan slot hidup; penegakannya lewat rute diuji di CreateBookingTest.
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function slots(): SlotService
{
    return app(SlotService::class);
}

function tanggal(string $date): CarbonImmutable
{
    return slots()->parseDate($date);
}

it('memakai hari ini menurut zona waktu bengkel, bukan zona server', function () {
    expect(slots()->today()->toDateString())->toBe(UJI_HARI_INI);
});

it('menetapkan tanggal sah paling awal pada H+1', function () {
    expect(slots()->earliestDate()->toDateString())->toBe(UJI_BESOK);
});

it('menetapkan batas terjauh 60 hari ke depan', function () {
    expect(slots()->latestDate()->toDateString())->toBe('2026-10-02');
});

it('menolak booking pada hari Minggu', function () {
    expect(slots()->isBookableDate(tanggal(UJI_MINGGU)))->toBeFalse()
        ->and(slots()->dateRejectionReason(tanggal(UJI_MINGGU)))->toContain('tutup');
});

it('menolak booking untuk hari ini karena aturan H-1', function () {
    expect(slots()->isBookableDate(tanggal(UJI_HARI_INI)))->toBeFalse()
        ->and(slots()->dateRejectionReason(tanggal(UJI_HARI_INI)))->toContain('H-1');
});

it('menolak booking lebih dari 60 hari ke depan', function () {
    expect(slots()->isBookableDate(tanggal(UJI_TERLALU_JAUH)))->toBeFalse()
        ->and(slots()->dateRejectionReason(tanggal(UJI_TERLALU_JAUH)))->toContain('60 hari');
});

it('menerima tanggal kerja di dalam rentang yang sah', function () {
    expect(slots()->isBookableDate(tanggal(UJI_BESOK)))->toBeTrue()
        ->and(slots()->dateRejectionReason(tanggal(UJI_BESOK)))->toBeNull();
});

it('mempertahankan sebelas slot pada hari kerja', function () {
    expect(slots()->slotsOn(tanggal(UJI_BESOK)))->toBe([
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
        '11:00', '11:30', '13:00', '13:30', '14:00',
    ]);
});

it('menghentikan slot Sabtu di 13:00', function () {
    // Keputusan R7 — docs/05 §Catatan konflik jam Sabtu.
    expect(slots()->slotsOn(tanggal(UJI_SABTU)))->toBe([
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00',
    ]);
});

it('menolak jam 13:30 pada hari Sabtu', function () {
    expect(slots()->isValidTimeOn(tanggal(UJI_SABTU), '13:30'))->toBeFalse();
});

it('menolak jam 14:00 pada hari Sabtu', function () {
    expect(slots()->isValidTimeOn(tanggal(UJI_SABTU), '14:00'))->toBeFalse();
});

it('menerima jam 13:30 pada hari kerja biasa', function () {
    expect(slots()->isValidTimeOn(tanggal(UJI_BESOK), '13:30'))->toBeTrue();
});

it('tidak menyediakan slot sama sekali pada hari tutup', function () {
    expect(slots()->slotsOn(tanggal(UJI_MINGGU)))->toBe([]);
});

it('mengurangi sisa kuota untuk setiap booking yang aktif', function () {
    Booking::factory()->onSlot(UJI_BESOK, '09:00')->create();

    expect(slots()->remaining(tanggal(UJI_BESOK), '09:00'))->toBe(1);
});

it('menandai slot penuh setelah kuota dua tercapai', function () {
    Booking::factory()->count(2)->onSlot(UJI_BESOK, '09:00')->create();

    $slot = collect(slots()->availability(tanggal(UJI_BESOK)))->firstWhere('time', '09:00');

    expect($slot['remaining'])->toBe(0)
        ->and($slot['is_full'])->toBeTrue();
});

it('melepaskan kuota kembali saat booking dibatalkan', function () {
    // Sistem lama menghitung semua booking termasuk yang batal, sehingga slot
    // bisa "penuh palsu" (docs/05 §5.1).
    Booking::factory()->onSlot(UJI_BESOK, '09:00')->create();
    Booking::factory()->onSlot(UJI_BESOK, '09:00')->status(BookingStatus::Cancelled)->create();

    expect(slots()->remaining(tanggal(UJI_BESOK), '09:00'))->toBe(1);
});

it('melepaskan kuota kembali untuk booking tidak hadir', function () {
    Booking::factory()->onSlot(UJI_BESOK, '09:00')->status(BookingStatus::NoShow)->create();

    expect(slots()->remaining(tanggal(UJI_BESOK), '09:00'))->toBe(2);
});

it('tidak menghitung booking yang sedang dijadwal ulang sebagai penghalang', function () {
    $booking = Booking::factory()->onSlot(UJI_BESOK, '09:00')->create();
    Booking::factory()->onSlot(UJI_BESOK, '09:00')->create();

    expect(slots()->remaining(tanggal(UJI_BESOK), '09:00'))->toBe(0)
        ->and(slots()->remaining(tanggal(UJI_BESOK), '09:00', $booking->id))->toBe(1);
});

it('menyebutkan jam yang masih tersisa pada hari itu', function () {
    Booking::factory()->count(2)->onSlot(UJI_BESOK, '08:00')->create();

    expect(slots()->openTimesOn(tanggal(UJI_BESOK)))
        ->not->toContain('08:00')
        ->toContain('08:30');
});

it('menghitung perkiraan selesai dari durasi paket', function () {
    expect(slots()->estimatedFinishAt(tanggal(UJI_BESOK), '09:00', 90)->format('Y-m-d H:i'))
        ->toBe(UJI_BESOK.' 10:30');
});
