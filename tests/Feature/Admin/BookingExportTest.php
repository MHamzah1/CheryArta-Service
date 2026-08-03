<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\ReportService;

/*
| Export daftar booking (roadmap 2.1.5, docs/07 §A9).
|
| Dua hal yang paling penting dijaga di sini:
|
| 1. Berkasnya MENGIKUTI saringan aktif. Export yang diam-diam mengabaikan
|    saringan mengirim seluruh basis pelanggan ke berkas yang lalu dibagikan.
| 2. Rutenya tertutup untuk customer dan tamu — isinya nama, nomor telepon,
|    dan plat (docs/09 §9.7).
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function bookingExport(string $tanggal, BookingStatus $status = BookingStatus::Completed): Booking
{
    $pelanggan = pelangganSiapBooking();

    return Booking::factory()->create([
        'user_id' => $pelanggan->id,
        'vehicle_id' => $pelanggan->vehicles()->value('id'),
        'booking_date' => $tanggal,
        'booking_time' => '09:00',
        'status' => $status,
    ]);
}

it('mengunduh CSV berisi kolom yang dipertahankan dari sistem lama', function () {
    bookingExport(UJI_HARI_INI);

    $respons = $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.export', ['format' => 'csv']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $isi = $respons->streamedContent();

    foreach (ReportService::EXPORT_HEADERS as $kolom) {
        expect($isi)->toContain($kolom);
    }
});

it('menyisipkan BOM UTF-8 agar Excel membaca huruf beraksen dengan benar', function () {
    // Tanpa BOM, "Müller" menjadi "MÃ¼ller" di Excel Windows — dan yang
    // disalahkan biasanya datanya, bukan berkasnya.
    bookingExport(UJI_HARI_INI);

    $isi = $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.export', ['format' => 'csv']))
        ->streamedContent();

    expect(str_starts_with($isi, "\xEF\xBB\xBF"))->toBeTrue();
});

it('mengikuti saringan status yang sedang aktif', function () {
    $selesai = bookingExport(UJI_HARI_INI, BookingStatus::Completed);
    $batal = bookingExport(UJI_HARI_INI, BookingStatus::Cancelled);

    $isi = $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.export', ['format' => 'csv', 'status' => 'completed']))
        ->streamedContent();

    expect($isi)->toContain($selesai->booking_code)
        ->not->toContain($batal->booking_code);
});

it('mengikuti saringan tanggal yang sedang aktif', function () {
    $masuk = bookingExport(UJI_HARI_INI);
    $luar = bookingExport('2026-07-01');

    $isi = $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.export', [
            'format' => 'csv',
            'dari' => UJI_HARI_INI,
            'sampai' => UJI_HARI_INI,
        ]))
        ->streamedContent();

    expect($isi)->toContain($masuk->booking_code)
        ->not->toContain($luar->booking_code);
});

it('mengembalikan TSV sebagai teks biasa untuk clipboard', function () {
    $booking = bookingExport(UJI_HARI_INI);

    $respons = $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.export', ['format' => 'tsv']))
        ->assertOk()
        ->assertHeader('content-type', 'text/plain; charset=UTF-8');

    expect($respons->getContent())
        ->toContain("Date\tTime\tName")
        ->toContain($booking->booking_code);
});

it('membersihkan tab dan baris baru di dalam sel TSV', function () {
    // Keluhan pelanggan adalah kolom yang paling mungkin memuat baris baru,
    // dan satu saja merusak seluruh struktur kolom saat ditempel.
    $booking = bookingExport(UJI_HARI_INI);
    $booking->forceFill(['complaint' => "Bunyi\tdi rem\ndepan."])->save();

    $isi = $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.export', ['format' => 'tsv']))
        ->getContent();

    // Baris judul + satu baris data, tidak lebih.
    expect(substr_count($isi, "\n"))->toBe(1)
        ->and($isi)->toContain('Bunyi di rem depan.');
});

it('mengekspor laporan mengikuti periode yang dipilih', function () {
    $masuk = bookingExport('2026-07-15');
    $luar = bookingExport('2026-05-01');

    $isi = $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.export', [
            'format' => 'csv',
            'periode' => 'kustom',
            'dari' => '2026-07-01',
            'sampai' => '2026-07-31',
        ]))
        ->streamedContent();

    expect($isi)->toContain($masuk->booking_code)
        ->not->toContain($luar->booking_code);
});

it('memakai CSV bila format tidak disebut', function () {
    bookingExport(UJI_HARI_INI);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('melarang berkas export tersimpan di cache perantara', function () {
    bookingExport(UJI_HARI_INI);

    // Urutan direktifnya dinormalkan Laravel, jadi yang diperiksa adalah
    // isinya: `no-store` yang menahan berkas berisi nomor telepon pelanggan
    // tersimpan di proxy mana pun.
    $cacheControl = $this->actingAs(serviceAdvisor())
        ->get(route('admin.bookings.export', ['format' => 'tsv']))
        ->headers->get('cache-control');

    expect($cacheControl)->toContain('no-store')->toContain('no-cache');
});

it('membuka export untuk Super Admin', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.bookings.export', ['format' => 'tsv']))
        ->assertOk();
});

it('menolak customer di rute export booking', function () {
    $this->actingAs(customer())
        ->get(route('admin.bookings.export', ['format' => 'csv']))
        ->assertForbidden();
});

it('menolak customer di rute export laporan', function () {
    $this->actingAs(customer())
        ->get(route('admin.reports.export', ['format' => 'csv']))
        ->assertForbidden();
});

it('mengantar tamu dari export ke halaman masuk', function () {
    $this->get(route('admin.bookings.export', ['format' => 'csv']))
        ->assertRedirect(route('login'));
});
