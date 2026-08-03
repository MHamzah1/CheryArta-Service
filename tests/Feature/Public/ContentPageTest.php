<?php

declare(strict_types=1);

use App\Enums\ServicePackageCategory;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\ServicePackage;

/*
| Halaman layanan, fasilitas, tentang, dan FAQ (roadmap 1.6.3).
*/

it('membuka halaman layanan dan mengelompokkan paket per kategori', function () {
    ServicePackage::factory()->create([
        'name' => 'Perawatan 1.000 KM',
        'category' => ServicePackageCategory::FirstMaintenanceIce,
        'sort_order' => 0,
    ]);
    ServicePackage::factory()->paid()->create([
        'name' => 'Ganti Kampas Rem',
        'category' => ServicePackageCategory::Other,
        'sort_order' => 1,
    ]);

    $this->get(route('public.services'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('layanan')
            ->has('groups', 2)
            ->where('groups.0.label', 'Perawatan Pertama')
            ->where('groups.0.packages.0.name', 'Perawatan 1.000 KM')
            ->where('groups.1.label', 'Layanan Lain'));
});

it('tidak menampilkan paket nonaktif di halaman layanan', function () {
    ServicePackage::factory()->create(['name' => 'Servis Aktif']);
    ServicePackage::factory()->create(['name' => 'Servis Lama', 'is_active' => false]);

    $this->get(route('public.services'))
        ->assertInertia(fn ($page) => $page
            ->has('groups', 1)
            ->has('groups.0.packages', 1)
            ->where('groups.0.packages.0.name', 'Servis Aktif'));
});

it('membuka halaman fasilitas dengan urutan sesuai sort_order', function () {
    Facility::factory()->create(['title' => 'Workshop Modern', 'sort_order' => 1]);
    Facility::factory()->create(['title' => 'Ruang Tunggu Premium', 'sort_order' => 0]);

    $this->get(route('public.facilities'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('fasilitas')
            ->has('facilities', 2)
            ->where('facilities.0.title', 'Ruang Tunggu Premium'));
});

it('membuka halaman tentang dengan profil dari config perusahaan', function () {
    $this->get(route('public.about'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tentang')
            ->where('experienceYears', config('company.experience_years'))
            ->where('about', config('company.about'))
            ->has('advantages', 4));
});

it('mengelompokkan faq per kategori dan menaruh yang tanpa kategori di Umum', function () {
    Faq::factory()->create(['question' => 'Kapan harus booking?', 'category' => 'booking', 'sort_order' => 0]);
    Faq::factory()->create(['question' => 'Apa saja layanannya?', 'category' => null, 'sort_order' => 1]);

    $this->get(route('public.faq'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('faq')
            ->has('groups', 2)
            ->where('groups.0.label', 'Booking')
            ->where('groups.1.label', 'Umum'));
});

it('tidak menampilkan faq nonaktif', function () {
    Faq::factory()->create(['question' => 'Pertanyaan aktif?']);
    Faq::factory()->inactive()->create(['question' => 'Pertanyaan lama?']);

    $this->get(route('public.faq'))
        ->assertInertia(fn ($page) => $page
            ->has('groups', 1)
            ->has('groups.0.faqs', 1)
            ->where('groups.0.faqs.0.question', 'Pertanyaan aktif?'));
});

it('membuka seluruh halaman publik tanpa login', function (string $rute) {
    $this->get(route($rute))->assertOk();
})->with([
    'home',
    'public.catalog.index',
    'public.services',
    'public.facilities',
    'public.about',
    'public.faq',
    'public.contact.show',
]);
