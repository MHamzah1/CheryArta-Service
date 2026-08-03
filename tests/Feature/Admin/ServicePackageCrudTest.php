<?php

declare(strict_types=1);

use App\Models\CarModel;
use App\Models\ServicePackage;

/*
| A5 — Paket Layanan (docs/07-modul-admin.md §A5), roadmap 1.3.1 & 1.3.7.
|
| Yang dijaga di sini: hanya Super Admin yang boleh menyentuh master data ini,
| dan angka aturannya (durasi 15–480, harga vs gratis) ditegakkan server —
| bukan hanya di form React (temuan B3 sistem lama).
*/

/** @return array<string, mixed> */
function dataPaket(array $timpa = []): array
{
    return array_merge([
        'code' => 'paket_uji_baru',
        'name' => 'Paket Uji Baru',
        'category' => 'other',
        'description' => 'Keterangan singkat.',
        'applicable_series' => [],
        'estimated_duration_minutes' => 60,
        'price' => 350000,
        'is_free' => false,
        'is_active' => true,
        'sort_order' => 5,
    ], $timpa);
}

/** @return list<array{0: string, 1: string}> */
function rutePaketLayanan(ServicePackage $package): array
{
    return [
        ['get', route('admin.service-packages.index')],
        ['get', route('admin.service-packages.create')],
        ['post', route('admin.service-packages.store')],
        ['get', route('admin.service-packages.edit', $package)],
        ['put', route('admin.service-packages.update', $package)],
        ['delete', route('admin.service-packages.destroy', $package)],
    ];
}

it('menolak service advisor di seluruh rute paket layanan', function () {
    $advisor = serviceAdvisor();
    $package = ServicePackage::factory()->create();

    foreach (rutePaketLayanan($package) as [$method, $url]) {
        $this->actingAs($advisor)->$method($url)->assertForbidden();
    }
});

it('menolak customer di seluruh rute paket layanan', function () {
    $customer = customer();
    $package = ServicePackage::factory()->create();

    foreach (rutePaketLayanan($package) as [$method, $url]) {
        $this->actingAs($customer)->$method($url)->assertForbidden();
    }
});

it('mengalihkan tamu ke halaman masuk', function () {
    $this->get(route('admin.service-packages.index'))->assertRedirect(route('login'));
});

it('menampilkan daftar paket kepada super admin', function () {
    $package = ServicePackage::factory()->create(['name' => 'Servis Berkala 10.000 KM']);

    $this->actingAs(superAdmin())
        ->get(route('admin.service-packages.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/paket-layanan/index')
            ->has('packages.data', 1)
            ->where('packages.data.0.name', $package->name));
});

it('menampilkan form tambah paket beserta pilihannya', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.service-packages.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/paket-layanan/create')
            ->has('categories')
            ->has('seriesOptions'));
});

it('menampilkan form ubah paket', function () {
    $package = ServicePackage::factory()->create();

    $this->actingAs(superAdmin())
        ->get(route('admin.service-packages.edit', $package))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/paket-layanan/edit')
            ->where('package.id', $package->id));
});

it('menyimpan paket layanan baru', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket())
        ->assertRedirect(route('admin.service-packages.index'));

    expect(ServicePackage::where('code', 'paket_uji_baru')->exists())->toBeTrue();
});

it('menolak kode paket yang sudah dipakai', function () {
    ServicePackage::factory()->create(['code' => 'Tiggo_8_Free']);

    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket(['code' => 'Tiggo_8_Free']))
        ->assertSessionHasErrors('code');
});

it('menolak kode paket yang memuat spasi', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket(['code' => 'paket uji']))
        ->assertSessionHasErrors('code');
});

it('menolak durasi di bawah 15 menit', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket(['estimated_duration_minutes' => 10]))
        ->assertSessionHasErrors('estimated_duration_minutes');
});

it('menolak durasi di atas 480 menit', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket(['estimated_duration_minutes' => 481]))
        ->assertSessionHasErrors('estimated_duration_minutes');
});

it('mewajibkan harga bila paket tidak gratis', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket(['is_free' => false, 'price' => null]))
        ->assertSessionHasErrors('price');
});

it('memaksa harga nol pada paket gratis meski request mengirim angka', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket(['is_free' => true, 'price' => 750000]));

    $package = ServicePackage::where('code', 'paket_uji_baru')->firstOrFail();

    expect((float) $package->price)->toBe(0.0)
        ->and($package->is_free)->toBeTrue();
});

it('menyimpan seri kosong sebagai null, bukan array kosong', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket(['applicable_series' => []]));

    expect(ServicePackage::where('code', 'paket_uji_baru')->firstOrFail()->applicable_series)->toBeNull();
});

it('menerima seri yang ada di katalog', function () {
    CarModel::factory()->create(['series_code' => 'tiggo_8']);

    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket(['applicable_series' => ['tiggo_8']]))
        ->assertSessionHasNoErrors();

    expect(ServicePackage::where('code', 'paket_uji_baru')->firstOrFail()->applicable_series)->toBe(['tiggo_8']);
});

it('menolak seri yang tidak ada di katalog', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket(['applicable_series' => ['seri_karangan']]))
        ->assertSessionHasErrors('applicable_series.0');
});

it('menerima urutan tampil yang dikosongkan', function () {
    // Input kosong sampai ke server sebagai null, sedangkan kolomnya NOT NULL.
    $this->actingAs(superAdmin())
        ->post(route('admin.service-packages.store'), dataPaket(['sort_order' => '']))
        ->assertSessionHasNoErrors();

    expect(ServicePackage::where('code', 'paket_uji_baru')->firstOrFail()->sort_order)->toBe(0);
});

it('memperbarui paket layanan', function () {
    $package = ServicePackage::factory()->create(['name' => 'Nama Lama']);

    $this->actingAs(superAdmin())
        ->put(route('admin.service-packages.update', $package), dataPaket([
            'code' => $package->code,
            'name' => 'Nama Baru',
        ]))
        ->assertRedirect(route('admin.service-packages.index'));

    expect($package->refresh()->name)->toBe('Nama Baru');
});

it('mengizinkan paket menyimpan kodenya sendiri saat disunting', function () {
    $package = ServicePackage::factory()->create(['code' => 'Tiggo_8_Free']);

    $this->actingAs(superAdmin())
        ->put(route('admin.service-packages.update', $package), dataPaket(['code' => 'Tiggo_8_Free']))
        ->assertSessionHasNoErrors();
});

it('menolak menghapus paket yang sudah dipakai booking', function () {
    // Aturan 1.3.5 baru benar-benar hidup setelah tabel `bookings` lahir di
    // F1.4 — sebelumnya ServicePackage::isReferenced() selalu mengembalikan
    // false karena tidak ada tabel yang merujuknya.
    $package = ServicePackage::factory()->create();
    App\Models\Booking::factory()->create(['service_package_id' => $package->id]);

    $this->actingAs(superAdmin())
        ->delete(route('admin.service-packages.destroy', $package))
        ->assertSessionHas('error');

    expect(ServicePackage::whereKey($package->id)->exists())->toBeTrue();
});

it('menghapus paket yang belum dipakai', function () {
    $package = ServicePackage::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('admin.service-packages.destroy', $package))
        ->assertRedirect(route('admin.service-packages.index'));

    expect(ServicePackage::whereKey($package->id)->exists())->toBeFalse();
});
