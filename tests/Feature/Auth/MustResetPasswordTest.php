<?php

declare(strict_types=1);

/*
| Penegakan must_reset_password (roadmap 2.2.5c, docs/09 §9.2).
|
| Tandanya sudah ditulis dengan benar sejak Tahap 6 tetapi belum pernah
| ditegakkan. Selama itu hanya mengenai akun customer, dampaknya kecil. Begitu
| A11 membuat akun STAF dengan password sementara, penegakannya menjadi wajib:
| password yang membuka panel internal tidak boleh berlaku lebih dari satu
| kali masuk.
*/

it('mengalihkan staf bertanda ke halaman ganti password', function () {
    $advisor = serviceAdvisor(['must_reset_password' => true]);

    $this->actingAs($advisor)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('password.edit'));
});

it('mengalihkan customer bertanda ke halaman ganti password', function () {
    // Akun walk-in dan akun hasil reset admin sama-sama bertanda ini.
    $this->actingAs(customer(['must_reset_password' => true]))
        ->get(route('dashboard'))
        ->assertRedirect(route('password.edit'));
});

it('tetap mengizinkan halaman ganti password itu sendiri', function () {
    // Tanpa pengecualian ini pemiliknya terjebak pada pengalihan tanpa ujung.
    $this->actingAs(serviceAdvisor(['must_reset_password' => true]))
        ->get(route('password.edit'))
        ->assertOk();
});

it('tetap mengizinkan logout', function () {
    $this->actingAs(serviceAdvisor(['must_reset_password' => true]))
        ->post(route('logout'))
        ->assertRedirect('/');
});

it('melepas tanda setelah password ditetapkan sendiri', function () {
    $advisor = serviceAdvisor(['must_reset_password' => true, 'password' => 'password-lama']);

    $this->actingAs($advisor)
        ->put(route('password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru-saya',
            'password_confirmation' => 'password-baru-saya',
        ])
        ->assertSessionHasNoErrors();

    expect($advisor->refresh()->must_reset_password)->toBeFalse();

    // Dan panel bisa dibuka lagi.
    $this->actingAs($advisor)->get(route('admin.dashboard'))->assertOk();
});

it('tidak mengganggu akun yang tidak bertanda', function () {
    $this->actingAs(serviceAdvisor())->get(route('admin.dashboard'))->assertOk();
    $this->actingAs(customer())->get(route('dashboard'))->assertOk();
});

it('mengunci akun staf baru sampai ia menetapkan password sendiri', function () {
    // Alur penuh: Super Admin membuat akun, pemiliknya masuk, dan tidak bisa
    // ke mana pun sebelum menetapkan password.
    $this->actingAs(superAdmin())->post(route('admin.users.store'), [
        'name' => 'Advisor Baru',
        'email' => 'advisor.baru@cheryarta.test',
        'phone_wa' => '081298765432',
        'role' => 'service_advisor',
        'is_active' => true,
    ]);

    $sementara = session('temporaryPassword')['password'];

    $this->post(route('logout'));

    $this->post(route('login'), [
        'email' => 'advisor.baru@cheryarta.test',
        'password' => $sementara,
    ])->assertRedirect(route('admin.dashboard', absolute: false));

    // Tujuannya memang dashboard admin, tetapi middleware menahannya di sana.
    $this->get(route('admin.bookings.index'))->assertRedirect(route('password.edit'));
});
