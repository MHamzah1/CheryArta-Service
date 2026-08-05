<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Support\ReportPeriod;
use Inertia\Testing\AssertableInertia;

/*
|--------------------------------------------------------------------------
| Laporan Pendapatan (roadmap 2.4.7, keputusan grill Q9)
|--------------------------------------------------------------------------
|
| Dikelompokkan menurut `issued_at`, BUKAN `paid_at`: memakai tanggal bayar
| membuat invoice yang belum dibayar tidak muncul di mana pun, sehingga piutang
| menjadi tak terlihat — justru angka yang paling ingin diketahui pemilik.
|
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function laporanPendapatan(): array
{
    $props = [];

    test()->actingAs(superAdmin())
        ->get(route('admin.reports.index', ['tab' => 'pendapatan', 'periode' => ReportPeriod::PRESET_30_HARI]))
        ->assertInertia(function (AssertableInertia $page) use (&$props): void {
            $props = $page->toArray()['props'];
        });

    return $props['revenue'];
}

it('menjumlahkan invoice terbit dan lunas sebagai pendapatan', function () {
    Invoice::factory()->issued()->amounts(500_000)->create();
    Invoice::factory()->paid()->amounts(300_000)->create();

    $revenue = laporanPendapatan();

    expect($revenue['issued_count'])->toBe(2)
        ->and($revenue['issued_total'])->toBe('800000.00')
        ->and($revenue['paid_count'])->toBe(1)
        ->and($revenue['paid_total'])->toBe('300000.00');
});

it('menjaga diterbitkan dikurangi lunas sama dengan belum dibayar', function () {
    Invoice::factory()->issued()->amounts(500_000)->create();
    Invoice::factory()->issued()->amounts(250_000)->create();
    Invoice::factory()->paid()->amounts(300_000)->create();

    $revenue = laporanPendapatan();

    expect((float) $revenue['issued_total'] - (float) $revenue['paid_total'])
        ->toBe((float) $revenue['unpaid_total'])
        ->and($revenue['unpaid_total'])->toBe('750000.00')
        ->and($revenue['unpaid_count'])->toBe(2);
});

it('mengecualikan invoice draft dan yang dibatalkan', function () {
    Invoice::factory()->issued()->amounts(500_000)->create();
    Invoice::factory()->amounts(900_000)->create();            // draft
    Invoice::factory()->voided()->amounts(900_000)->create();  // void

    $revenue = laporanPendapatan();

    expect($revenue['issued_count'])->toBe(1)
        ->and($revenue['issued_total'])->toBe('500000.00');
});

it('mengecualikan invoice yang terbit di luar periode', function () {
    Invoice::factory()->issued()->amounts(500_000)->create();

    Invoice::factory()->issued()->amounts(999_000)->create([
        'issued_at' => now()->subMonths(6),
    ]);

    $revenue = laporanPendapatan();

    expect($revenue['issued_total'])->toBe('500000.00');
});

it('memecah pendapatan per metode pembayaran termasuk yang nol', function () {
    Invoice::factory()->paid('transfer')->amounts(300_000)->create();

    $metode = collect(laporanPendapatan()['by_payment_method'])->keyBy('label');

    // Seluruh metode ditampilkan meski nol: metode yang hilang dari daftar
    // terbaca sebagai "tidak ada datanya", bukan "tidak pernah dipakai".
    expect($metode)->toHaveCount(3)
        ->and($metode['Transfer']['total'])->toBe('300000.00')
        ->and($metode['Tunai']['total'])->toBe('0.00');
});

it('tidak mengirim data pendapatan kepada service advisor', function () {
    Invoice::factory()->issued()->amounts(500_000)->create();

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.reports.index', ['tab' => 'pendapatan']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            // Bukan angka nol — datanya tidak dikirim sama sekali. Angka yang
            // dikirim lalu disembunyikan di React bukan pengaman (temuan S3).
            ->where('revenue', null)
            ->where('tab', 'rekap'));
});
