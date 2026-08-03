<?php

declare(strict_types=1);

use App\Http\Controllers\Customer\VehicleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rute Web — Chery Arta
|--------------------------------------------------------------------------
|
| Peta rute lengkap ada di docs/03-arsitektur-teknis.md §3.5.
| URL berbahasa Indonesia (/kendaraan, /jadwal, /riwayat) agar sejalan dengan
| antarmukanya; nama rutenya tetap Inggris (customer.vehicles.index).
|
| Rute booking (F1.4) dan katalog publik (F1.6) menyusul.
|
*/

// --- Publik -----------------------------------------------------------
Route::get('/', fn () => Inertia::render('welcome'))->name('home');

// --- Customer ---------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');

    Route::name('customer.')->group(function () {
        Route::get('kendaraan', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('kendaraan/tambah', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('kendaraan', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('kendaraan/{vehicle}/ubah', [VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('kendaraan/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('kendaraan/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
    });
});

// --- Admin internal ---------------------------------------------------
// Middleware `role` menjaga pintu masuk grup ini, tetapi TIDAK menggantikan
// Policy pada tiap sumber daya di dalamnya — docs/09 §9.3.
Route::middleware(['auth', 'role:super_admin,service_advisor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => Inertia::render('admin/dashboard'))->name('dashboard');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
