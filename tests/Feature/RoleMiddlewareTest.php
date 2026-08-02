<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
| Middleware 'role' menjaga pintu masuk grup rute.
| Sistem lama menentukan hak akses dari localStorage di browser, sehingga
| siapa pun bisa masuk panel admin (temuan S3).
| Lihat docs/09-keamanan-hak-akses.md §9.3.
*/

beforeEach(function () {
    Route::middleware(['web', 'auth', 'role:super_admin'])
        ->get('/_uji/hanya-super-admin', fn () => response('ok'));

    Route::middleware(['web', 'auth', 'role:super_admin,service_advisor'])
        ->get('/_uji/staf', fn () => response('ok'));
});

it('menolak tamu yang belum login', function () {
    $this->get('/_uji/staf')->assertRedirect('/login');
});

it('menolak customer masuk area staf', function () {
    $this->actingAs(customer())->get('/_uji/staf')->assertForbidden();
});

it('mengizinkan service advisor masuk area staf', function () {
    $this->actingAs(serviceAdvisor())->get('/_uji/staf')->assertOk();
});

it('mengizinkan super admin masuk area staf', function () {
    $this->actingAs(superAdmin())->get('/_uji/staf')->assertOk();
});

it('menolak service advisor pada rute khusus super admin', function () {
    $this->actingAs(serviceAdvisor())->get('/_uji/hanya-super-admin')->assertForbidden();
});

it('mengizinkan super admin pada rute khusus super admin', function () {
    $this->actingAs(superAdmin())->get('/_uji/hanya-super-admin')->assertOk();
});

it('menolak akun yang dinonaktifkan meski rolenya staf', function () {
    $advisor = serviceAdvisor(['is_active' => false]);

    $this->actingAs($advisor)->get('/_uji/staf')->assertForbidden();
});
