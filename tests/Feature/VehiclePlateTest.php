<?php

declare(strict_types=1);

use App\Models\CarModel;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('menerima plat berprefiks satu huruf', function () {
    $vehicle = Vehicle::factory()->singleLetterPrefix()->create([
        'plate_number' => '1234',
        'plate_suffix' => 'ABC',
    ]);

    expect($vehicle->plate_prefix)->toBe('B')
        ->and($vehicle->plate_full)->toBe('B-1234-ABC');
});

it('menerima plat berprefiks dua huruf', function () {
    $vehicle = Vehicle::factory()->doubleLetterPrefix()->create([
        'plate_number' => '1234',
        'plate_suffix' => 'ABC',
    ]);

    expect($vehicle->plate_prefix)->toBe('AB')
        ->and($vehicle->plate_full)->toBe('AB-1234-ABC');
});

it('menyimpan segmen plat dalam huruf besar', function () {
    $vehicle = Vehicle::factory()->create([
        'plate_prefix' => 'b',
        'plate_number' => '99',
        'plate_suffix' => 'xyz',
    ]);

    expect($vehicle->plate_full)->toBe('B-99-XYZ');
});

it('menolak plat kembar milik pengguna yang sama', function () {
    $user = User::factory()->create();

    $user->vehicles()->create([
        'plate_prefix' => 'B',
        'plate_number' => '1234',
        'plate_suffix' => 'ABC',
        'model_name_manual' => 'Tiggo 8 Pro',
    ]);

    expect(fn () => $user->vehicles()->create([
        'plate_prefix' => 'B',
        'plate_number' => '1234',
        'plate_suffix' => 'ABC',
        'model_name_manual' => 'Tiggo 8 Pro',
    ]))->toThrow(QueryException::class);
});

it('mengizinkan plat yang sama dimiliki dua pengguna berbeda', function () {
    $plate = [
        'plate_prefix' => 'B',
        'plate_number' => '1234',
        'plate_suffix' => 'ABC',
        'model_name_manual' => 'Tiggo 8 Pro',
    ];

    User::factory()->create()->vehicles()->create($plate);
    $second = User::factory()->create()->vehicles()->create($plate);

    // Mobil bekas berpindah tangan; keunikan hanya berlaku per pemilik.
    expect($second->exists)->toBeTrue();
});

it('memakai nama katalog bila kendaraannya Chery', function () {
    $model = CarModel::factory()->create(['name' => 'Tiggo 8 Pro']);
    $vehicle = Vehicle::factory()->create([
        'car_model_id' => $model->id,
        'model_name_manual' => null,
    ]);

    expect($vehicle->display_model)->toBe('Tiggo 8 Pro');
});

it('memakai nama ketikan pemilik bila kendaraannya bukan Chery', function () {
    $vehicle = Vehicle::factory()->create([
        'car_model_id' => null,
        'model_name_manual' => 'Honda Brio',
    ]);

    expect($vehicle->display_model)->toBe('Honda Brio');
});
