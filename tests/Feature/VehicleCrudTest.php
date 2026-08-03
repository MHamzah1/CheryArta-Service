<?php

declare(strict_types=1);

use App\Models\CarModel;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array<string, mixed> */
function dataKendaraan(array $timpa = []): array
{
    return array_merge([
        'car_model_id' => null,
        'model_name_manual' => 'Tiggo 8 Pro',
        'plate_prefix' => 'B',
        'plate_number' => '1234',
        'plate_suffix' => 'ABC',
        'year' => 2024,
        'color' => 'Putih',
        'vin' => null,
        'is_primary' => false,
    ], $timpa);
}

it('menolak tamu membuka daftar kendaraan', function () {
    $this->get(route('customer.vehicles.index'))->assertRedirect(route('login'));
});

it('hanya menampilkan kendaraan milik pengguna yang masuk', function () {
    $saya = User::factory()->create();
    $orangLain = User::factory()->create();

    $milikSaya = Vehicle::factory()->create(['user_id' => $saya->id]);
    Vehicle::factory()->create(['user_id' => $orangLain->id]);

    $this->actingAs($saya)
        ->get(route('customer.vehicles.index'))
        ->assertInertia(fn ($page) => $page
            ->component('kendaraan/index')
            ->has('vehicles', 1)
            ->where('vehicles.0.id', $milikSaya->id));
});

it('menyimpan kendaraan baru milik pengguna yang masuk', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('customer.vehicles.store'), dataKendaraan())
        ->assertRedirect(route('customer.vehicles.index'));

    expect($user->vehicles()->count())->toBe(1)
        ->and($user->vehicles()->first()->plate_full)->toBe('B-1234-ABC');
});

it('menerima plat berprefiks satu huruf lewat rute', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('customer.vehicles.store'), dataKendaraan(['plate_prefix' => 'B']))
        ->assertSessionHasNoErrors();
});

it('menerima plat berprefiks dua huruf lewat rute', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('customer.vehicles.store'), dataKendaraan(['plate_prefix' => 'AB']))
        ->assertSessionHasNoErrors();
});

it('menolak prefiks plat lebih dari dua huruf', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('customer.vehicles.store'), dataKendaraan(['plate_prefix' => 'ABC']))
        ->assertSessionHasErrors('plate_prefix');
});

it('menjadikan kendaraan pertama sebagai kendaraan utama', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('customer.vehicles.store'), dataKendaraan(['is_primary' => false]));

    expect($user->vehicles()->first()->is_primary)->toBeTrue();
});

it('menurunkan kendaraan utama lama saat yang baru ditandai utama', function () {
    $user = User::factory()->create();
    $lama = Vehicle::factory()->primary()->create(['user_id' => $user->id]);

    $this->actingAs($user)->post(route('customer.vehicles.store'), dataKendaraan([
        'plate_number' => '9999',
        'is_primary' => true,
    ]));

    expect($lama->refresh()->is_primary)->toBeFalse()
        ->and($user->vehicles()->primary()->count())->toBe(1);
});

it('menolak plat kembar di akun yang sama lewat rute', function () {
    $user = User::factory()->create();
    Vehicle::factory()->create([
        'user_id' => $user->id,
        'plate_prefix' => 'B',
        'plate_number' => '1234',
        'plate_suffix' => 'ABC',
    ]);

    $this->actingAs($user)
        ->post(route('customer.vehicles.store'), dataKendaraan())
        ->assertSessionHasErrors('plate_number');
});

it('mewajibkan nama kendaraan bila model Chery tidak dipilih', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('customer.vehicles.store'), dataKendaraan([
            'car_model_id' => null,
            'model_name_manual' => null,
        ]))
        ->assertSessionHasErrors('model_name_manual');
});

it('menerima kendaraan Chery tanpa nama manual', function () {
    $user = User::factory()->create();
    $model = CarModel::factory()->create();

    $this->actingAs($user)
        ->post(route('customer.vehicles.store'), dataKendaraan([
            'car_model_id' => $model->id,
            'model_name_manual' => null,
        ]))
        ->assertSessionHasNoErrors();
});

it('memperbarui kendaraan milik sendiri', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('customer.vehicles.update', $vehicle), dataKendaraan(['color' => 'Merah']))
        ->assertRedirect(route('customer.vehicles.index'));

    expect($vehicle->refresh()->color)->toBe('Merah');
});

it('menghapus kendaraan milik sendiri', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->delete(route('customer.vehicles.destroy', $vehicle));

    expect($user->vehicles()->count())->toBe(0)
        ->and($vehicle->fresh()->trashed())->toBeTrue();
});

it('memindahkan status utama saat kendaraan utama dihapus', function () {
    $user = User::factory()->create();
    $utama = Vehicle::factory()->primary()->create(['user_id' => $user->id]);
    $lainnya = Vehicle::factory()->create(['user_id' => $user->id, 'plate_number' => '4321']);

    $this->actingAs($user)->delete(route('customer.vehicles.destroy', $utama));

    expect($lainnya->refresh()->is_primary)->toBeTrue();
});

it('menolak pengguna membuka form ubah kendaraan orang lain', function () {
    $orangLain = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $orangLain->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('customer.vehicles.edit', $vehicle))
        ->assertForbidden();
});

it('menolak pengguna memperbarui kendaraan orang lain', function () {
    $orangLain = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $orangLain->id, 'color' => 'Hitam']);

    $this->actingAs(User::factory()->create())
        ->put(route('customer.vehicles.update', $vehicle), dataKendaraan(['color' => 'Merah']))
        ->assertForbidden();

    expect($vehicle->refresh()->color)->toBe('Hitam');
});

it('menolak pengguna menghapus kendaraan orang lain', function () {
    $orangLain = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $orangLain->id]);

    $this->actingAs(User::factory()->create())
        ->delete(route('customer.vehicles.destroy', $vehicle))
        ->assertForbidden();

    expect($vehicle->fresh()->trashed())->toBeFalse();
});
