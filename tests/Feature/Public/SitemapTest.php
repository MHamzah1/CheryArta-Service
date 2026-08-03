<?php

declare(strict_types=1);

use App\Models\CarModel;

/*
| sitemap.xml & robots.txt (roadmap 1.6.5).
*/

it('menerbitkan sitemap berisi halaman publik', function () {
    $this->get(route('public.sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<urlset', false)
        ->assertSee(route('public.catalog.index'), false)
        ->assertSee(route('public.services'), false)
        ->assertSee(route('public.faq'), false);
});

it('memasukkan model katalog aktif ke dalam sitemap', function () {
    CarModel::factory()->create(['slug' => 'tiggo-8-pro']);

    $this->get(route('public.sitemap'))
        ->assertSee(route('public.catalog.show', 'tiggo-8-pro'), false);
});

it('tidak memasukkan model nonaktif ke dalam sitemap', function () {
    CarModel::factory()->inactive()->create(['slug' => 'model-ditarik']);

    $this->get(route('public.sitemap'))
        ->assertDontSee('model-ditarik', false);
});

it('tidak memasukkan rute panel ke dalam sitemap', function () {
    $isi = $this->get(route('public.sitemap'))->getContent();

    expect($isi)->not->toContain('/admin');
    expect($isi)->not->toContain('/dashboard');
});

it('memblokir panel internal di robots.txt', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Disallow: /admin');
    expect($robots)->toContain('Disallow: /dashboard');
});
