<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rute Web — Chery Arta
|--------------------------------------------------------------------------
|
| Peta rute lengkap ada di docs/03-arsitektur-teknis.md §3.5.
| Berkas ini masih kerangka Fase 0: hanya satu halaman per layout untuk
| membuktikan kerangkanya berjalan. Rute publik, customer, dan admin yang
| sebenarnya dibuat di Fase 2, 3, dan 4.
|
*/

// --- Publik -----------------------------------------------------------
Route::get('/', fn () => Inertia::render('welcome'))->name('home');

// --- Customer ---------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');
});

// --- Admin internal ---------------------------------------------------
// FASE 1: tambahkan ->middleware('role:super_admin,service_advisor') setelah
// kolom users.role & users.is_active dibuat. Middleware 'role' sudah
// terdaftar di bootstrap/app.php.
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => Inertia::render('admin/dashboard'))->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
