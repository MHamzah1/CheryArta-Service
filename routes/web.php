<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CarModelController;
use App\Http\Controllers\Admin\CarModelImageController;
use App\Http\Controllers\Admin\CarModelVariantController;
use App\Http\Controllers\Admin\ServicePackageController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\BookingHistoryController;
use App\Http\Controllers\Customer\VehicleController;
use App\Http\Controllers\Public\BookingTrackingController;
use App\Http\Controllers\Public\SlotAvailabilityController;
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

// Ketersediaan slot: satu-satunya endpoint JSON di alur booking (docs/03 §3.4).
// Didaftarkan SEBELUM `booking/{booking}` agar "slots" tidak dikira kode booking.
Route::get('booking/slots', SlotAvailabilityController::class)
    ->middleware('throttle:60,1')
    ->name('booking.slots');

// Pelacakan tanpa login — hanya kode, jadwal, dan status (docs/05 §5.7).
// Throttle-nya yang membuat kode booking tidak bisa ditebak dengan mencoba
// berulang kali.
Route::get('cek-service', BookingTrackingController::class)
    ->middleware('throttle:10,1')
    ->name('public.tracking');

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

        // Booking servis. `{booking}` diikat lewat kode booking, bukan id yang
        // bisa ditebak berurutan (Booking::getRouteKeyName()).
        Route::get('booking', [BookingController::class, 'create'])->name('booking.create');
        Route::post('booking', [BookingController::class, 'store'])->name('booking.store');
        Route::get('booking/{booking}/sukses', [BookingController::class, 'success'])->name('booking.success');
        Route::get('booking/{booking}', [BookingController::class, 'show'])->name('booking.show');
        Route::put('booking/{booking}/jadwal-ulang', [BookingController::class, 'reschedule'])->name('booking.reschedule');
        Route::put('booking/{booking}/batal', [BookingController::class, 'cancel'])->name('booking.cancel');

        Route::get('riwayat', [BookingHistoryController::class, 'index'])->name('history.index');
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

        // --- Master data — Super Admin saja (docs/07 §A14) ------------------
        // Advisor ditolak di sini, bukan sekadar tidak melihat menunya.
        Route::middleware('role:super_admin')->group(function () {

            // A5 — Paket Layanan
            Route::get('paket-layanan', [ServicePackageController::class, 'index'])->name('service-packages.index');
            Route::get('paket-layanan/tambah', [ServicePackageController::class, 'create'])->name('service-packages.create');
            Route::post('paket-layanan', [ServicePackageController::class, 'store'])->name('service-packages.store');
            Route::get('paket-layanan/{service_package}/ubah', [ServicePackageController::class, 'edit'])->name('service-packages.edit');
            Route::put('paket-layanan/{service_package}', [ServicePackageController::class, 'update'])->name('service-packages.update');
            Route::delete('paket-layanan/{service_package}', [ServicePackageController::class, 'destroy'])->name('service-packages.destroy');

            // A4 — Katalog Mobil. Diikat lewat id, bukan slug: slug boleh
            // disunting, dan URL panel tidak boleh berubah di tengah jalan.
            Route::get('katalog', [CarModelController::class, 'index'])->name('car-models.index');
            Route::get('katalog/tambah', [CarModelController::class, 'create'])->name('car-models.create');
            Route::post('katalog', [CarModelController::class, 'store'])->name('car-models.store');

            Route::prefix('katalog/{car_model:id}')
                ->name('car-models.')
                ->scopeBindings()
                ->group(function () {
                    Route::get('ubah', [CarModelController::class, 'edit'])->name('edit');
                    // Form model membawa brosur PDF, jadi pengirimannya
                    // multipart lewat POST + _method=PUT (pola baku Inertia).
                    Route::put('/', [CarModelController::class, 'update'])->name('update');
                    Route::delete('/', [CarModelController::class, 'destroy'])->name('destroy');
                    Route::delete('brosur', [CarModelController::class, 'destroyBrochure'])->name('brochure.destroy');

                    // Varian — tabel di dalam halaman ubah model
                    Route::post('varian', [CarModelVariantController::class, 'store'])->name('variants.store');
                    Route::put('varian/{variant}', [CarModelVariantController::class, 'update'])->name('variants.update');
                    Route::delete('varian/{variant}', [CarModelVariantController::class, 'destroy'])->name('variants.destroy');

                    // Galeri — scopeBindings memastikan gambar milik model lain
                    // berujung 404, bukan tersunting diam-diam.
                    Route::post('galeri', [CarModelImageController::class, 'store'])->name('images.store');
                    Route::put('galeri/urutan', [CarModelImageController::class, 'reorder'])->name('images.reorder');
                    Route::put('galeri/{image}', [CarModelImageController::class, 'update'])->name('images.update');
                    Route::put('galeri/{image}/utama', [CarModelImageController::class, 'primary'])->name('images.primary');
                    Route::delete('galeri/{image}', [CarModelImageController::class, 'destroy'])->name('images.destroy');
                });
        });
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
