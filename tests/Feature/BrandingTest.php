<?php

declare(strict_types=1);

/**
 * Menjaga agar sisa merek Laravel tidak diam-diam kembali lewat pembaruan
 * starter kit, dan agar aset merek yang dirujuk benar-benar ada.
 *
 * Uji ini menyentuh berkas, bukan HTTP: logonya komponen React, sehingga
 * tidak muncul di HTML awal yang dikembalikan server.
 */
it('tidak menyisakan komponen logo Laravel', function () {
    expect(file_exists(resource_path('js/components/app-logo.tsx')))->toBeFalse()
        ->and(file_exists(resource_path('js/components/app-logo-icon.tsx')))->toBeFalse()
        ->and(file_exists(public_path('logo.svg')))->toBeFalse();
});

it('tidak menyebut Laravel Starter Kit di mana pun dalam antarmuka', function () {
    $berkas = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('js'), FilesystemIterator::SKIP_DOTS),
    );

    $penyebut = [];

    foreach ($berkas as $berkasSatu) {
        if (! $berkasSatu instanceof SplFileInfo || $berkasSatu->getExtension() !== 'tsx') {
            continue;
        }

        $isi = (string) file_get_contents($berkasSatu->getPathname());

        if (str_contains($isi, 'Laravel Starter Kit') || str_contains($isi, 'AppLogoIcon')) {
            $penyebut[] = $berkasSatu->getFilename();
        }
    }

    expect($penyebut)->toBe([]);
});

it('menyediakan berkas emblem dan favicon yang dirujuk', function () {
    expect(file_exists(public_path('images/logo-chery-emblem.svg')))->toBeTrue()
        ->and(file_exists(public_path('favicon.svg')))->toBeTrue();
});

it('memakai currentColor pada emblem agar bisa mengikuti warna header', function () {
    $emblem = (string) file_get_contents(resource_path('js/components/brand-mark.tsx'));

    expect($emblem)->toContain('currentColor');
});

it('menautkan favicon SVG di dokumen', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('rel="icon" href="/favicon.svg"', false);
});

it('mengambil nama dealer dari config, bukan menulisnya di JSX', function () {
    $lockup = (string) file_get_contents(resource_path('js/components/brand-lockup.tsx'));

    expect($lockup)->toContain('company.name')
        ->and($lockup)->not->toContain('Chery Arta');
});
