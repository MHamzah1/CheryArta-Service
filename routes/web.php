<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\BookingExportController;
use App\Http\Controllers\Admin\BookingStatusController;
use App\Http\Controllers\Admin\CarModelController;
use App\Http\Controllers\Admin\CarModelImageController;
use App\Http\Controllers\Admin\CarModelVariantController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\InvoicePdfController;
use App\Http\Controllers\Admin\InvoiceStatusController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ServicePackageController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\WhatsAppMessageController;
use App\Http\Controllers\Admin\WhatsAppTemplateController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\BookingHistoryController;
use App\Http\Controllers\Customer\InvoiceController as CustomerInvoiceController;
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
// `role:customer` menjaga grup ini sejajar dengan penjagaan grup admin di
// bawah (roadmap 2.2.5). Sebelumnya hanya `auth`, sehingga staf yang login
// sah membuka layar pelanggan — dan justru ke sanalah mereka diantar setelah
// masuk. Jalur staf untuk memesankan servis adalah walk-in di
// `admin.bookings.create`, bukan form customer ini.
Route::middleware(['auth', 'role:customer'])->group(function () {
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

        // Invoice milik sendiri (US-C7, roadmap 2.4.4). Pintu masuknya detail
        // booking — sengaja TIDAK ada menu tersendiri, karena riwayat servis
        // sudah ada dan dua daftar untuk satu kenyataan hanya membingungkan.
        //
        // Diikat lewat id, tetapi dicari ULANG lewat relasi penggunanya di
        // controller: invoice milik orang lain tidak pernah terambil sama
        // sekali (.claude/rules/50 #2).
        Route::get('invoice/{invoice}', [CustomerInvoiceController::class, 'show'])->name('invoice.show');
        Route::get('invoice/{invoice}/pdf', [CustomerInvoiceController::class, 'download'])
            ->middleware('throttle:10,1')
            ->name('invoice.pdf');
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

        // --- A7 Jejak pesan WhatsApp (docs/08 §8.2) -------------------------
        // Bukan Policy tersendiri: siapa pun yang boleh membuka booking boleh
        // mengabari pelanggannya (matriks docs/09 §9.3). `scopeBindings`
        // memastikan pesan milik booking lain berujung 404, bukan tertandai
        // diam-diam.
        Route::prefix('bookings/{booking}/whatsapp')
            ->name('bookings.whatsapp.')
            ->scopeBindings()
            ->group(function () {
                Route::post('/', [WhatsAppMessageController::class, 'store'])->name('store');
                Route::put('{whatsapp_message}/terkirim', [WhatsAppMessageController::class, 'markSent'])->name('sent');
                Route::put('{whatsapp_message}/lewati', [WhatsAppMessageController::class, 'skip'])->name('skip');
            });

        // Soft delete — ditolak untuk advisor oleh BookingPolicy (docs/07 §A14).
        Route::delete('bookings/{booking}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');

        // --- A8 Invoice (docs/07 §A8) ---------------------------------------
        // TIDAK ada `create`/`store` yang berdiri sendiri: invoice selalu lahir
        // dari sebuah booking yang sudah selesai (keputusan grill Q3). Karena
        // itu pembuatannya bersarang di bawah bookings, bukan di bawah invoices.
        Route::post('bookings/{booking}/invoice', [AdminInvoiceController::class, 'store'])->name('bookings.invoice.store');

        Route::get('invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
        Route::put('invoices/{invoice}', [AdminInvoiceController::class, 'update'])->name('invoices.update');

        // Tiga aksi status. `void` khusus Super Admin — penolakannya datang
        // dari InvoicePolicy, bukan dari middleware: sasarannya satu invoice
        // tertentu, dan pesannya harus menyebut invoice itu.
        Route::put('invoices/{invoice}/terbitkan', [InvoiceStatusController::class, 'issue'])->name('invoices.issue');
        Route::put('invoices/{invoice}/lunas', [InvoiceStatusController::class, 'markPaid'])->name('invoices.paid');
        Route::put('invoices/{invoice}/batal', [InvoiceStatusController::class, 'void'])->name('invoices.void');

        // Alasan throttle-nya sama dengan export di atas: satu berkas memuat
        // nama, plat, dan rincian biaya pelanggan (docs/09 §9.7).
        Route::get('invoices/{invoice}/pdf', InvoicePdfController::class)
            ->middleware('throttle:10,1')
            ->name('invoices.pdf');

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

            // --- A10 Konten landing page --------------------------------
            // Tabelnya sudah terisi sejak Tahap 3 (**R4**); yang lahir di sini
            // hanyalah layar pengelolaannya. URL berbahasa Indonesia, nama
            // rute Inggris — sama seperti katalog dan paket layanan.
            // `urutan` didaftarkan SEBELUM `{model}` agar tidak dikira id.
            Route::get('fasilitas', [FacilityController::class, 'index'])->name('facilities.index');
            Route::get('fasilitas/tambah', [FacilityController::class, 'create'])->name('facilities.create');
            Route::post('fasilitas', [FacilityController::class, 'store'])->name('facilities.store');
            Route::put('fasilitas/urutan', [FacilityController::class, 'reorder'])->name('facilities.reorder');
            Route::get('fasilitas/{facility}/ubah', [FacilityController::class, 'edit'])->name('facilities.edit');
            Route::put('fasilitas/{facility}', [FacilityController::class, 'update'])->name('facilities.update');
            Route::delete('fasilitas/{facility}', [FacilityController::class, 'destroy'])->name('facilities.destroy');

            Route::get('faq', [FaqController::class, 'index'])->name('faqs.index');
            Route::get('faq/tambah', [FaqController::class, 'create'])->name('faqs.create');
            Route::post('faq', [FaqController::class, 'store'])->name('faqs.store');
            Route::put('faq/urutan', [FaqController::class, 'reorder'])->name('faqs.reorder');
            Route::get('faq/{faq}/ubah', [FaqController::class, 'edit'])->name('faqs.edit');
            Route::put('faq/{faq}', [FaqController::class, 'update'])->name('faqs.update');
            Route::delete('faq/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');

            Route::get('testimoni', [TestimonialController::class, 'index'])->name('testimonials.index');
            Route::get('testimoni/tambah', [TestimonialController::class, 'create'])->name('testimonials.create');
            Route::post('testimoni', [TestimonialController::class, 'store'])->name('testimonials.store');
            Route::put('testimoni/urutan', [TestimonialController::class, 'reorder'])->name('testimonials.reorder');
            Route::get('testimoni/{testimonial}/ubah', [TestimonialController::class, 'edit'])->name('testimonials.edit');
            Route::put('testimoni/{testimonial}', [TestimonialController::class, 'update'])->name('testimonials.update');
            Route::delete('testimoni/{testimonial}', [TestimonialController::class, 'destroy'])->name('testimonials.destroy');

            // Pesan kontak: tidak ada tambah/ubah — isinya datang dari
            // pengunjung. Hapus di sini benar-benar menghapus (keputusan
            // grill #4), dikawal ConfirmDialog di sisi layar.
            Route::get('pesan-masuk', [ContactMessageController::class, 'index'])->name('contact-messages.index');
            Route::put('pesan-masuk/{contactMessage}/dibaca', [ContactMessageController::class, 'markRead'])->name('contact-messages.read');
            Route::delete('pesan-masuk/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

            // --- A11 Pengguna Internal ----------------------------------
            // Menutup **R9**: sampai sini akun staf hanya lahir dari
            // UserSeeder. Tidak ada rute hapus — akun dinonaktifkan, tidak
            // pernah dihapus permanen (docs/07 §A11).
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/tambah', [UserController::class, 'create'])->name('users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::get('users/{user}/ubah', [UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::put('users/{user}/status-akun', [UserController::class, 'toggleActive'])->name('users.toggle-active');
            Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

            // --- A7 Template WhatsApp -----------------------------------
            // Tidak ada `tambah` maupun `hapus`: himpunan kuncinya milik
            // App\Enums\WhatsAppTemplateKey karena tiap kunci punya pemicunya
            // sendiri di dalam kode (keputusan grill Q5).
            Route::get('template-wa', [WhatsAppTemplateController::class, 'index'])->name('whatsapp-templates.index');
            Route::get('template-wa/{whatsapp_template}/ubah', [WhatsAppTemplateController::class, 'edit'])->name('whatsapp-templates.edit');
            Route::put('template-wa/{whatsapp_template}', [WhatsAppTemplateController::class, 'update'])->name('whatsapp-templates.update');

            // --- A12 Activity Log ---------------------------------------
            Route::get('activity-log', ActivityLogController::class)->name('activity-log.index');
        });
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
