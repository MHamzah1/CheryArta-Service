<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array<string, string> */
function dataRegistrasi(array $timpa = []): array
{
    return array_merge([
        'name' => 'Rani Saputri',
        'email' => 'rani@example.test',
        'phone_wa' => '0812-3456-7890',
        'password' => 'kata-sandi-rahasia',
        'password_confirmation' => 'kata-sandi-rahasia',
    ], $timpa);
}

it('menampilkan halaman registrasi', function () {
    $this->get('/register')->assertOk();
});

it('mendaftarkan pengguna baru lalu memasukkannya', function () {
    $response = $this->post('/register', dataRegistrasi());

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

it('menormalisasi nomor WhatsApp sebelum menyimpan', function () {
    $this->post('/register', dataRegistrasi(['phone_wa' => '0812-3456-7890']));

    expect(User::firstWhere('email', 'rani@example.test')?->phone_wa)
        ->toBe('6281234567890');
});

it('menerima nomor WhatsApp berawalan +62', function () {
    $this->post('/register', dataRegistrasi(['phone_wa' => '+62 812 3456 7890']));

    expect(User::firstWhere('email', 'rani@example.test')?->phone_wa)
        ->toBe('6281234567890');
});

it('mewajibkan nomor WhatsApp', function () {
    $this->post('/register', dataRegistrasi(['phone_wa' => '']))
        ->assertSessionHasErrors('phone_wa');

    $this->assertGuest();
});

it('menolak nomor WhatsApp yang bentuknya tidak dikenali', function () {
    $this->post('/register', dataRegistrasi(['phone_wa' => '12345']))
        ->assertSessionHasErrors('phone_wa');
});

it('menolak nomor WhatsApp yang sudah terdaftar meski beda format', function () {
    User::factory()->create(['phone_wa' => '6281234567890']);

    // Tersimpan sebagai 62…, dikirim sebagai 0812… — tanpa normalisasi
    // sebelum validasi, keduanya lolos sebagai dua akun berbeda.
    $this->post('/register', dataRegistrasi(['phone_wa' => '0812-3456-7890']))
        ->assertSessionHasErrors('phone_wa');
});

it('selalu memberi role customer, walau request menyisipkan role lain', function () {
    $this->post('/register', dataRegistrasi(['role' => 'super_admin']));

    expect(User::firstWhere('email', 'rani@example.test')?->role)
        ->toBe(UserRole::Customer);
});

it('membatasi registrasi 5 kali per jam', function () {
    // Payload sengaja tidak sah agar tidak ada akun yang jadi dan pemanggilnya
    // tetap berstatus tamu. Kalau registrasinya berhasil, middleware `guest`
    // yang akan mengalihkan permintaan berikutnya — dan yang teruji bukan lagi
    // pembatas lajunya. Throttle dihitung sebelum validasi, jadi tetap terhitung.
    $tidakSah = dataRegistrasi(['email' => 'bukan-alamat-email']);

    foreach (range(1, 5) as $ignored) {
        $this->post('/register', $tidakSah)->assertStatus(302);
    }

    $this->post('/register', $tidakSah)->assertStatus(429);
});
