<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

/*
| A11 — Pengguna Internal (docs/07 §A11, roadmap 2.2.3–2.2.4, 2.2.8).
|
| Yang dijaga di sini bukan CRUD-nya, melainkan tiga pengaman yang kalau gagal
| akan mengunci semua orang dari panelnya sendiri — kesalahan yang tidak bisa
| diperbaiki dari dalam aplikasi.
*/

it('membuat akun staf tanpa admin mengetik password', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.users.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@cheryarta.test',
            'phone_wa' => '081234567890',
            'role' => 'service_advisor',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('temporaryPassword');

    $baru = User::query()->where('email', 'budi@cheryarta.test')->sole();

    expect($baru->role)->toBe(UserRole::ServiceAdvisor)
        ->and($baru->must_reset_password)->toBeTrue()
        // Nomor disimpan ternormalisasi, bukan seperti yang diketik.
        ->and($baru->phone_wa)->toBe('6281234567890');
});

it('tidak pernah menyimpan password sementara dalam bentuk terbaca', function () {
    $this->actingAs(superAdmin())->post(route('admin.users.store'), [
        'name' => 'Citra Dewi',
        'email' => 'citra@cheryarta.test',
        'phone_wa' => '081234567891',
        'role' => 'service_advisor',
        'is_active' => true,
    ]);

    $sementara = session('temporaryPassword')['password'];
    $baru = User::query()->where('email', 'citra@cheryarta.test')->sole();

    expect($baru->password)->not->toBe($sementara)
        ->and(password_verify($sementara, $baru->password))->toBeTrue();
});

it('menolak peran customer saat membuat akun staf', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.users.store'), [
            'name' => 'Bukan Staf',
            'email' => 'bukan@cheryarta.test',
            'phone_wa' => '081234567892',
            'role' => 'customer',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('role');

    expect(User::query()->where('email', 'bukan@cheryarta.test')->exists())->toBeFalse();
});

it('tidak menampilkan akun customer di daftar pengguna internal', function () {
    $pelanggan = customer(['name' => 'Pelanggan Biasa']);
    $advisor = serviceAdvisor(['name' => 'Advisor Asli']);

    $this->actingAs(superAdmin())
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->where('users.data', fn ($rows) => collect($rows)->doesntContain('id', $pelanggan->id)
                && collect($rows)->contains('id', $advisor->id)));
});

it('menolak super admin menurunkan peran dirinya sendiri', function () {
    $sa = superAdmin();
    superAdmin(); // supaya bukan yang terakhir — yang diuji murni aturan "diri sendiri"

    $this->actingAs($sa)
        ->put(route('admin.users.update', $sa), [
            'name' => $sa->name,
            'email' => $sa->email,
            'phone_wa' => $sa->phone_wa,
            'role' => 'service_advisor',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('role');

    expect($sa->refresh()->role)->toBe(UserRole::SuperAdmin);
});

it('menolak super admin menonaktifkan dirinya sendiri', function () {
    $sa = superAdmin();
    superAdmin();

    $this->actingAs($sa)
        ->put(route('admin.users.toggle-active', $sa))
        ->assertSessionHasErrors('role');

    expect($sa->refresh()->is_active)->toBeTrue();
});

it('menolak menjatuhkan super admin aktif terakhir', function () {
    $terakhir = superAdmin();
    $pelaku = superAdmin();

    // Pelaku menonaktifkan dirinya lebih dulu lewat jalur lain tidak mungkin,
    // jadi yang diuji: SA lain menonaktifkan SA sehingga tersisa nol yang aktif.
    $pelaku->forceFill(['is_active' => true])->save();

    // Jatuhkan satu dulu supaya `$terakhir` benar-benar menjadi satu-satunya.
    $this->actingAs($terakhir)->put(route('admin.users.toggle-active', $pelaku))->assertRedirect();
    expect($pelaku->refresh()->is_active)->toBeFalse();

    // Kini `$terakhir` sendirian — menonaktifkannya harus ditolak.
    $lain = superAdmin();
    $this->actingAs($lain)
        ->put(route('admin.users.toggle-active', $terakhir))
        ->assertRedirect();

    // `$lain` masih aktif, jadi `$terakhir` boleh jatuh. Sekarang `$lain`
    // sendirian dan tidak boleh dijatuhkan siapa pun.
    expect($terakhir->refresh()->is_active)->toBeFalse();

    $this->actingAs($lain)
        ->put(route('admin.users.toggle-active', $lain))
        ->assertSessionHasErrors('role');

    expect($lain->refresh()->is_active)->toBeTrue();
});

it('menolak mengubah akun customer lewat rute pengguna internal', function () {
    $pelanggan = customer();

    $this->actingAs(superAdmin())
        ->put(route('admin.users.update', $pelanggan), [
            'name' => 'Diubah',
            'email' => $pelanggan->email,
            'phone_wa' => $pelanggan->phone_wa,
            'role' => 'service_advisor',
            'is_active' => true,
        ])
        ->assertForbidden();

    expect($pelanggan->refresh()->role)->toBe(UserRole::Customer);
});

it('menandai akun wajib ganti password setelah direset', function () {
    $advisor = serviceAdvisor();
    $passwordLama = $advisor->password;

    $this->actingAs(superAdmin())
        ->put(route('admin.users.reset-password', $advisor))
        ->assertSessionHas('temporaryPassword');

    expect($advisor->refresh()->password)->not->toBe($passwordLama)
        ->and($advisor->must_reset_password)->toBeTrue();
});

it('menolak advisor membuka seluruh rute pengguna internal', function () {
    $sasaran = serviceAdvisor();

    $this->actingAs(serviceAdvisor())->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs(serviceAdvisor())->get(route('admin.users.create'))->assertForbidden();
    $this->actingAs(serviceAdvisor())->put(route('admin.users.toggle-active', $sasaran))->assertForbidden();
    $this->actingAs(serviceAdvisor())->put(route('admin.users.reset-password', $sasaran))->assertForbidden();
});

it('menjaga UserSeeder tetap menghasilkan akun yang bisa dipakai masuk', function () {
    $this->seed(Database\Seeders\UserSeeder::class);
    $this->seed(Database\Seeders\UserSeeder::class); // idempoten — dijalankan dua kali

    $sa = User::query()->where('role', UserRole::SuperAdmin)->first();
    $advisor = User::query()->where('role', UserRole::ServiceAdvisor)->first();

    expect($sa)->not->toBeNull()
        ->and($advisor)->not->toBeNull()
        ->and($sa->is_active)->toBeTrue()
        // Akun seeder tidak boleh bertanda wajib ganti password — kalau iya,
        // demo ke pemilik bengkel berhenti di layar ganti password.
        ->and($sa->must_reset_password)->toBeFalse();
});
