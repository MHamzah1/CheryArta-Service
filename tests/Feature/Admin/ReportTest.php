<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\User;

/*
| A9 — Laporan (roadmap 2.1.4, docs/07 §A9).
|
| Yang dijaga: batas periode benar-benar mengiris data, rentang kustom ditolak
| bila melampaui batas config, dan tab Pendapatan TIDAK dirender selama tabel
| `invoices` belum ada (keputusan grill #1).
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function bookingLaporan(string $tanggal, BookingStatus $status = BookingStatus::Completed, string $jam = '09:00'): Booking
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

it('membuka laporan dengan periode bawaan 30 hari', function () {
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/laporan/index')
            ->where('period.periode', '30_hari')
            ->where('period.sampai', UJI_HARI_INI)
            ->where('period.dari', '2026-07-05')
            ->where('period.jumlah_hari', 30)
            ->where('tab', 'rekap'));
});

it('tidak merender tab pendapatan selama tabel invoice belum ada', function () {
    // Keputusan grill #1. Tab yang tampil lalu dinonaktifkan membuat orang
    // menyangka fiturnya rusak.
    $this->actingAs(superAdmin())
        ->get(route('admin.reports.index'))
        ->assertInertia(fn ($page) => $page
            ->missing('revenue')
            ->missing('pendapatan'));
});

it('mengiris rekap sesuai periode yang dipilih', function () {
    bookingLaporan(UJI_HARI_INI);
    bookingLaporan('2026-07-01'); // di luar 7 hari terakhir

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index', ['periode' => '7_hari']))
        ->assertInertia(fn ($page) => $page
            ->where('period.jumlah_hari', 7)
            ->where('recap.total', 1)
            ->where('recap.total_selesai', 1));
});

it('menampilkan seluruh status termasuk yang bernilai nol', function () {
    // Status yang hilang dari daftar terbaca sebagai "tidak ada datanya",
    // bukan "tidak pernah terjadi" — dua hal yang berbeda bagi pembacanya.
    bookingLaporan(UJI_HARI_INI, BookingStatus::Completed);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index'))
        ->assertInertia(fn ($page) => $page->has('recap.per_status', 6));
});

it('memecah rekap per paket layanan dan per model', function () {
    bookingLaporan(UJI_HARI_INI);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index'))
        ->assertInertia(fn ($page) => $page
            ->has('recap.per_paket', 1)
            ->where('recap.per_paket.0.jumlah', 1)
            ->has('recap.per_model', 1));
});

it('tidak menghitung booking batal sebagai slot terisi di okupansi', function () {
    // `cancelled` dan `no_show` melepaskan kuotanya kembali — sistem lama
    // menghitung semuanya sehingga slot terlihat "penuh palsu".
    bookingLaporan(UJI_HARI_INI, BookingStatus::Completed, '08:00');
    bookingLaporan(UJI_HARI_INI, BookingStatus::Cancelled, '08:00');
    bookingLaporan(UJI_HARI_INI, BookingStatus::NoShow, '08:00');

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index', ['tab' => 'okupansi']))
        ->assertInertia(fn ($page) => $page
            ->where('tab', 'okupansi')
            ->where('occupancy.total_terisi', 1)
            ->where('occupancy.jam_tersibuk', '08:00')
            ->where('occupancy.quota_per_slot', 2));
});

it('menghitung customer baru dari tanggal registrasi', function () {
    User::factory()->create(['role' => UserRole::Customer, 'created_at' => now()]);
    User::factory()->create(['role' => UserRole::Customer, 'created_at' => now()->subMonths(3)]);
    User::factory()->create(['role' => UserRole::ServiceAdvisor, 'created_at' => now()]);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index', ['tab' => 'customer']))
        ->assertInertia(fn ($page) => $page
            ->where('tab', 'customer')
            // Hanya satu customer yang mendaftar dalam 30 hari terakhir;
            // akun staf tidak pernah ikut terhitung.
            ->where('newCustomers.total', 1)
            ->has('newCustomers.per_hari', 30));
});

it('menerima rentang kustom yang masuk akal', function () {
    bookingLaporan('2026-07-15');

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index', [
            'periode' => 'kustom',
            'dari' => '2026-07-01',
            'sampai' => '2026-07-31',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('period.periode', 'kustom')
            ->where('period.jumlah_hari', 31)
            ->where('recap.total', 1));
});

it('menolak rentang kustom yang melampaui batas config', function () {
    // Tanpa batas atas, satu tautan berisi tanggal jauh memaksa pemindaian
    // seluruh tabel bookings.
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index', [
            'periode' => 'kustom',
            'dari' => '2020-01-01',
            'sampai' => '2026-08-03',
        ]))
        ->assertSessionHasErrors('sampai');
});

it('menolak tanggal akhir yang mendahului tanggal mulai', function () {
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index', [
            'periode' => 'kustom',
            'dari' => '2026-08-03',
            'sampai' => '2026-07-01',
        ]))
        ->assertSessionHasErrors('sampai');
});

it('menolak preset periode yang tidak dikenal', function () {
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index', ['periode' => 'sepanjang_masa']))
        ->assertSessionHasErrors('periode');
});

it('membuka laporan untuk kedua role staf', function () {
    $this->actingAs(superAdmin())->get(route('admin.reports.index'))->assertOk();
});

it('menolak customer di halaman laporan', function () {
    $this->actingAs(customer())
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});

it('mengantar tamu dari laporan ke halaman masuk', function () {
    $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
});
