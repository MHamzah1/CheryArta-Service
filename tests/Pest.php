<?php

declare(strict_types=1);

use App\Models\ServicePackage;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Seluruh uji di direktori Feature memakai Tests\TestCase dan menyegarkan
| database (SQLite in-memory, lihat phpunit.xml).
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
|
| Pembuat pengguna per peran, supaya uji otorisasi ringkas dan konsisten.
| Lihat matriks hak akses di docs/09-keamanan-hak-akses.md §9.3.
|
*/

function superAdmin(array $attributes = []): User
{
    return User::factory()->superAdmin()->create($attributes);
}

function serviceAdvisor(array $attributes = []): User
{
    return User::factory()->serviceAdvisor()->create($attributes);
}

function customer(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

/*
|--------------------------------------------------------------------------
| Helper Booking
|--------------------------------------------------------------------------
|
| Seluruh aturan slot bergantung pada "hari ini", sehingga uji yang tidak
| membekukan waktu akan lulus hari ini dan gagal hari Minggu depan. Waktu
| dibekukan pada Senin, 3 Agustus 2026 — tanggal yang dipakai contoh di
| docs/05-alur-bisnis.md.
|
*/

const UJI_HARI_INI = '2026-08-03';      // Senin
const UJI_BESOK = '2026-08-04';         // Selasa — tanggal sah paling awal
const UJI_SABTU = '2026-08-08';
const UJI_MINGGU = '2026-08-09';        // bengkel tutup
const UJI_TERLALU_JAUH = '2026-10-05';  // > 60 hari dari hari ini

function bekukanWaktuUji(): void
{
    $waktu = CarbonImmutable::parse(UJI_HARI_INI.' 09:00:00', config('booking.timezone'));

    Carbon::setTestNow($waktu);
    CarbonImmutable::setTestNow($waktu);
}

function cairkanWaktuUji(): void
{
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
}

/** Pelanggan lengkap dengan satu kendaraan — bahan dasar hampir semua uji booking. */
function pelangganSiapBooking(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    Vehicle::factory()->create(['user_id' => $user->id]);

    return $user;
}

/** @return array<string, mixed> */
function dataBooking(User $user, array $timpa = []): array
{
    return array_merge([
        'vehicle_id' => $user->vehicles()->value('id'),
        'service_package_id' => ServicePackage::factory()->create()->id,
        'booking_date' => UJI_BESOK,
        'booking_time' => '09:00',
        'odometer' => 12000,
        'complaint' => 'Ada bunyi dari rem depan.',
    ], $timpa);
}
