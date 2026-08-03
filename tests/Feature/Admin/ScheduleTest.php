<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;

/*
| A3 — jadwal harian (roadmap 1.5.5, docs/07 §A3).
|
| Layar yang dibuka advisor saat menerima telepon. Yang dijaga: barisnya
| mengikuti daftar slot dari config (termasuk penimpaan Sabtu, keputusan R7),
| dan booking yang batal melepaskan slotnya kembali.
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function bookingJadwal(string $tanggal, string $jam, BookingStatus $status = BookingStatus::Confirmed): Booking
{
    $pelanggan = pelangganSiapBooking();

    return Booking::factory()->create([
        'user_id' => $pelanggan->id,
        'vehicle_id' => $pelanggan->vehicles()->value('id'),
        'booking_date' => $tanggal,
        'booking_time' => $jam,
        'status' => $status,
    ]);
}

it('menampilkan sebelas baris slot untuk hari kerja biasa', function () {
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.schedule.index', ['tanggal' => UJI_BESOK]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/jadwal/index')
            ->where('date', UJI_BESOK)
            ->where('quotaPerSlot', 2)
            ->where('closedReason', null)
            ->has('rows', 11)
            ->where('rows.0.time', '08:00')
            ->where('rows.10.time', '14:00'));
});

it('menampilkan sembilan baris untuk hari Sabtu sesuai keputusan R7', function () {
    // Slot Sabtu berhenti di 13:00; angkanya dibaca dari config, bukan dari
    // percabangan hari yang ditulis di kode.
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.schedule.index', ['tanggal' => UJI_SABTU]))
        ->assertInertia(fn ($page) => $page
            ->has('rows', 9)
            ->where('rows.8.time', '13:00'));
});

it('menjelaskan bahwa bengkel tutup pada hari Minggu', function () {
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.schedule.index', ['tanggal' => UJI_MINGGU]))
        ->assertInertia(fn ($page) => $page
            ->has('rows', 0)
            ->where('closedReason', fn (string $alasan) => str_contains($alasan, 'tutup')));
});

it('menempatkan booking pada baris slotnya', function () {
    $booking = bookingJadwal(UJI_BESOK, '09:30');

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.schedule.index', ['tanggal' => UJI_BESOK]))
        ->assertInertia(fn ($page) => $page
            ->where('rows.3.time', '09:30')
            ->has('rows.3.bookings', 1)
            ->where('rows.3.bookings.0.booking_code', $booking->booking_code)
            ->where('rows.3.remaining', 1)
            ->where('rows.3.is_full', false));
});

it('menandai slot yang sudah penuh', function () {
    bookingJadwal(UJI_BESOK, '08:00');
    bookingJadwal(UJI_BESOK, '08:00');

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.schedule.index', ['tanggal' => UJI_BESOK]))
        ->assertInertia(fn ($page) => $page
            ->has('rows.0.bookings', 2)
            ->where('rows.0.remaining', 0)
            ->where('rows.0.is_full', true));
});

it('tidak menghitung booking yang dibatalkan sebagai slot terisi', function () {
    // Sistem lama menghitung seluruh booking termasuk yang batal, sehingga
    // slot bisa "penuh palsu" (docs/05 §5.1).
    bookingJadwal(UJI_BESOK, '11:00', BookingStatus::Cancelled);
    bookingJadwal(UJI_BESOK, '11:00', BookingStatus::NoShow);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.schedule.index', ['tanggal' => UJI_BESOK]))
        ->assertInertia(fn ($page) => $page
            ->where('rows.6.time', '11:00')
            ->has('rows.6.bookings', 0)
            ->where('rows.6.remaining', 2));
});

it('membuka hari ini bila tanggal tidak disebut', function () {
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.schedule.index'))
        ->assertInertia(fn ($page) => $page
            ->where('date', UJI_HARI_INI)
            ->where('isToday', true)
            ->where('previousDate', '2026-08-02')
            ->where('nextDate', UJI_BESOK));
});

it('boleh menengok jadwal yang sudah lewat', function () {
    // Berbeda dari form booking, jadwal tidak dibatasi H-1 maupun batas 60
    // hari: advisor memang perlu memeriksa hari kemarin.
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.schedule.index', ['tanggal' => '2026-07-01']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('date', '2026-07-01')->where('isToday', false));
});

it('tidak membocorkan keluhan atau nomor telepon ke kartu jadwal', function () {
    $booking = bookingJadwal(UJI_BESOK, '09:00');
    $booking->forceFill(['complaint' => 'Bunyi di rem depan.'])->save();

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.schedule.index', ['tanggal' => UJI_BESOK]))
        ->assertInertia(fn ($page) => $page
            ->missing('rows.2.bookings.0.complaint')
            ->missing('rows.2.bookings.0.phone_wa'));
});
