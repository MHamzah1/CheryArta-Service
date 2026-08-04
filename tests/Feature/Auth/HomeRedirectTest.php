<?php

declare(strict_types=1);

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Tujuan setelah masuk, dan batas grup rute customer (roadmap 2.2.5)
|--------------------------------------------------------------------------
|
| Dua cacat warisan F1.2 yang diperbaiki bersama karena menyentuh alur yang
| sama: seluruh berkas Auth mengantar ke `route('dashboard')` apa adanya, dan
| grup rute customer hanya dijaga `auth`. Akibatnya Super Admin yang login
| mendarat di layar pelanggan lengkap dengan menunya.
|
| Uji ini menembak rute sungguhan, bukan memanggil HomeRoute langsung —
| kalau tidak, memindahkan pemanggilnya ke berkas lain tidak akan ketahuan.
|
*/

function masuk(User $user): Illuminate\Testing\TestResponse
{
    return test()->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);
}

it('mengantar super admin ke dashboard admin setelah masuk', function () {
    masuk(superAdmin())->assertRedirect(route('admin.dashboard', absolute: false));
});

it('mengantar service advisor ke dashboard admin setelah masuk', function () {
    masuk(serviceAdvisor())->assertRedirect(route('admin.dashboard', absolute: false));
});

it('mengantar customer ke dashboard customer setelah masuk', function () {
    masuk(customer())->assertRedirect(route('dashboard', absolute: false));
});

it('tetap menghormati tautan dalam yang memicu login', function () {
    // `intended()` harus menang atas tujuan per peran, kalau tidak staf yang
    // mengeklik tautan ke satu booking akan terlempar ke dashboard.
    $this->get(route('admin.bookings.index'))->assertRedirect(route('login'));

    masuk(superAdmin())->assertRedirect(route('admin.bookings.index'));
});

it('mengantar pendaftar baru ke dashboard customer', function () {
    // Registrasi selalu menghasilkan akun customer — `role` di luar $fillable.
    $this->post(route('register'), [
        'name' => 'Siti Rahayu',
        'email' => 'siti@contoh.test',
        'phone_wa' => '081234567890',
        'password' => 'katasandi-rahasia',
        'password_confirmation' => 'katasandi-rahasia',
    ])->assertRedirect(route('dashboard', absolute: false));
});

it('menolak staf di dashboard customer', function () {
    $this->actingAs(superAdmin())->get(route('dashboard'))->assertForbidden();
});

it('menolak staf di seluruh rute customer', function () {
    $this->actingAs(serviceAdvisor());

    $this->get(route('customer.vehicles.index'))->assertForbidden();
    $this->get(route('customer.booking.create'))->assertForbidden();
    $this->get(route('customer.history.index'))->assertForbidden();
});

it('tetap membuka dashboard customer untuk customer', function () {
    $this->actingAs(customer())->get(route('dashboard'))->assertOk();
});

it('menolak customer nonaktif di rute customer', function () {
    // Efek samping yang disengaja: `role:customer` ikut memeriksa `is_active`,
    // yang sebelumnya tidak diperiksa sama sekali di grup ini.
    $this->actingAs(customer(['is_active' => false]))
        ->get(route('dashboard'))
        ->assertForbidden();
});
