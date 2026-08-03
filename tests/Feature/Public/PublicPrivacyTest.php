<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\CarModel;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\ServicePackage;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Vehicle;

/*
| Kebocoran data pribadi di halaman publik (roadmap 1.6.7).
|
| Sistem lama mengirim seluruh isi tabel ke browser lalu menyaringnya di sana
| (temuan S8). Uji ini memeriksa muatan halaman APA ADANYA — termasuk props
| Inertia di dalam atribut data-page — bukan hanya apa yang terlihat mata.
|
| docs/09 §9.4: halaman publik tidak pernah menampilkan nama lengkap, nomor
| telepon, email, atau plat pelanggan.
*/

/** Pelanggan dengan nilai yang khas, supaya kemunculannya tidak mungkin kebetulan. */
function pelangganRahasia(): User
{
    $user = User::factory()->create([
        'name' => 'Zulkarnain Prasetyo Wijaya',
        'email' => 'zulkarnain.rahasia@contoh.test',
        'phone_wa' => '6281299887766',
        'address' => 'Jl. Rahasia Sekali No. 42',
    ]);

    Vehicle::factory()->create([
        'user_id' => $user->id,
        'plate_prefix' => 'B',
        'plate_number' => '9999',
        'plate_suffix' => 'ZZQ',
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'vehicle_id' => $user->vehicles()->value('id'),
        'booking_code' => 'CA-20260804-0777',
        'complaint' => 'Keluhan pribadi yang tidak boleh terbaca publik.',
    ]);

    return $user;
}

/** Isi setiap halaman publik agar seksinya benar-benar dirender. */
function isiKontenPublik(): void
{
    $carModel = CarModel::factory()->create(['name' => 'Tiggo 8 Pro', 'slug' => 'tiggo-8-pro']);
    ServicePackage::factory()->create(['name' => 'Servis Berkala 10.000 KM']);
    Facility::factory()->create(['title' => 'Ruang Tunggu Premium']);
    Faq::factory()->create(['question' => 'Kapan harus booking?']);
    Testimonial::factory()->create(['customer_name' => 'Budi S.']);

    // Kendaraan pelanggan menunjuk ke model katalog: kalau relasi ini ikut
    // termuat di halaman katalog, platnya bisa terbawa tanpa sengaja.
    Vehicle::query()->update(['car_model_id' => $carModel->id]);
}

it('tidak membocorkan data pribadi pelanggan di halaman publik mana pun', function (string $rute) {
    $user = pelangganRahasia();
    isiKontenPublik();

    $this->get(route($rute))
        ->assertOk()
        ->assertDontSee($user->name, false)
        ->assertDontSee($user->email, false)
        ->assertDontSee((string) $user->phone_wa, false)
        ->assertDontSee('B-9999-ZZQ', false)
        ->assertDontSee('ZZQ', false)
        ->assertDontSee($user->address ?? '', false)
        ->assertDontSee('Keluhan pribadi', false);
})->with([
    'home',
    'public.catalog.index',
    'public.services',
    'public.facilities',
    'public.about',
    'public.faq',
    'public.contact.show',
]);

it('tidak membocorkan data pribadi pelanggan di detail katalog', function () {
    $user = pelangganRahasia();
    isiKontenPublik();

    $this->get(route('public.catalog.show', 'tiggo-8-pro'))
        ->assertOk()
        ->assertDontSee($user->name, false)
        ->assertDontSee($user->email, false)
        ->assertDontSee((string) $user->phone_wa, false)
        ->assertDontSee('B-9999-ZZQ', false);
});

it('tidak membocorkan kode booking orang lain lewat beranda', function () {
    pelangganRahasia();

    $this->get(route('home'))->assertDontSee('CA-20260804-0777', false);
});

it('hanya mengirim jam, sisa kuota, dan penanda penuh pada kartu ketersediaan', function () {
    bekukanWaktuUji();
    pelangganRahasia();

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('slotPreview.slots.0', fn ($slot) => $slot
                ->has('time')
                ->has('remaining')
                ->has('is_full')));

    cairkanWaktuUji();
});
