<?php

declare(strict_types=1);

/*
| Uji asap Fase 0: memastikan satu halaman per layout benar-benar
| ter-render — PublicLayout, CustomerLayout, dan AdminLayout.
|
| Isi halamannya masih kerangka; yang diuji di sini hanya bahwa rute,
| middleware, dan shared props sudah tersambung.
*/

it('menampilkan beranda publik untuk tamu', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('welcome'));
});

it('membagikan profil perusahaan lewat shared props', function () {
    // Halaman TIDAK BOLEH menulis alamat/telepon langsung di JSX.
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('company.name', 'Chery Arta')
            ->where('company.address.full', 'Jl. Jend. Sudirman No.1, Kranji, Kec. Bekasi Bar., Kota Bekasi, Jawa Barat')
            ->where('company.wa_number', config('company.wa_number'))
            ->etc(),
        );
});

it('mengarahkan tamu ke login saat membuka dashboard customer', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('menampilkan dashboard customer untuk pengguna yang login', function () {
    $this->actingAs(customer())
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard'));
});

it('mengarahkan tamu ke login saat membuka panel admin', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('menampilkan panel admin untuk staf', function () {
    $this->actingAs(serviceAdvisor())
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/dashboard'));
});
