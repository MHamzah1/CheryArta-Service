<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\BookingExportController;
use App\Http\Controllers\Admin\BookingStatusController;
use App\Http\Controllers\Admin\CarModelController;
use App\Http\Controllers\Admin\CarModelImageController;
use App\Http\Controllers\Admin\CarModelVariantController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ServicePackageController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\BookingHistoryController;
use App\Http\Controllers\Customer\VehicleController;
use App\Http\Controllers\Public\BookingTrackingController;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\ContentPageController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\SitemapController;
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
*/

// --- Publik -----------------------------------------------------------
Route::get('/', HomeController::class)->name('home');

// Katalog. `{slug}` diikat manual di controller agar scope `active()` ikut ke
// dalam pencariannya — model nonaktif berujung 404, bukan halaman yang
// terlanjur tersusun lalu diperiksa belakangan.
Route::get('katalog', [CatalogController::class, 'index'])->name('public.catalog.index');
Route::get('katalog/{slug}', [CatalogController::class, 'show'])->name('public.catalog.show');

Route::get('layanan', [ContentPageController::class, 'services'])->name('public.services');
Route::get('fasilitas', [ContentPageController::class, 'facilities'])->name('public.facilities');
Route::get('tentang', [ContentPageController::class, 'about'])->name('public.about');
Route::get('faq', [ContentPageController::class, 'faq'])->name('public.faq');

// Form kontak: 5 kirim per jam per IP (docs/09 §9.6). Halaman GET-nya tidak
// dibatasi — yang perlu direm adalah pengirimannya, bukan membacanya.
Route::get('kontak', [ContactController::class, 'show'])->name('public.contact.show');
Route::post('kontak', [ContactController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('public.contact.store');

Route::get('sitemap.xml', SitemapController::class)->name('public.sitemap');

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
        // --- A1 Dashboard ----------------------------------------------------
        // Redirect ke /admin/bookings dari F1.5.1 DICABUT di sini (roadmap
        // 2.1.3): dashboardnya kini ada, jadi keputusan R8 sudah selesai
        // masa berlakunya.
        Route::get('/', DashboardController::class)->name('dashboard');

        // --- A9 Laporan ------------------------------------------------------
        // Satu rute dengan tab di dalamnya; periode dan tab aktif hidup di
        // query string supaya tautannya bisa dibagikan.
        Route::get('laporan', ReportController::class)->name('reports.index');

        // Export dibatasi 10 per menit: satu berkas bisa memuat ribuan baris
        // berisi nama, telepon, dan plat pelanggan (docs/09 §9.7). Batasnya
        // menahan pengambilan berulang, bukan pemakaian wajar.
        Route::middleware('throttle:10,1')->group(function () {
            Route::get('laporan/export', [BookingExportController::class, 'reports'])->name('reports.export');
            Route::get('bookings/export', [BookingExportController::class, 'bookings'])->name('bookings.export');
        });

        // --- A3 Jadwal harian -----------------------------------------------
        Route::get('jadwal', ScheduleController::class)->name('schedule.index');

        // --- A2 Booking ------------------------------------------------------
        // `walk-in` didaftarkan SEBELUM `{booking}` agar tidak dikira kode
        // booking. `{booking}` diikat lewat kode (Booking::getRouteKeyName()).
        Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/walk-in', [AdminBookingController::class, 'create'])->name('bookings.create');
        Route::post('bookings', [AdminBookingController::class, 'store'])->name('bookings.store');
        Route::get('bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
        Route::put('bookings/{booking}/status', BookingStatusController::class)->name('bookings.status');
        // Soft delete — ditolak untuk advisor oleh BookingPolicy (docs/07 §A14).
        Route::delete('bookings/{booking}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');

        // --- A6 Customer & Kendaraan ----------------------------------------
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        // Dua aksi berikut khusus Super Admin. Penolakannya datang dari
        // UserPolicy, bukan dari middleware: sasarannya satu baris tertentu,
        // dan grup rute tidak bisa menilai itu (docs/09 §9.3).
        Route::put('customers/{customer}/status-akun', [CustomerController::class, 'toggleActive'])->name('customers.toggle-active');
        Route::put('customers/{customer}/reset-password', [CustomerController::class, 'resetPassword'])->name('customers.reset-password');

        Route::get('vehicles', [AdminVehicleController::class, 'index'])->name('vehicles.index');

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
