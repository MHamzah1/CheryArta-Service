<?php

declare(strict_types=1);

/*
| Railway menaruh aplikasi di balik reverse proxy. Tanpa trustProxies, Laravel
| menyangka koneksinya http:// sehingga URL aset Vite dan seluruh redirect
| menunjuk ke skema yang salah — halaman tampil tanpa CSS dan peramban
| mengeluhkan mixed content.
|
| Lihat docs/12-panduan-instalasi-deploy.md §12.3.4.
*/

it('membaca skema https dari header proxy', function () {
    $this->get('/', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'cheryarta.up.railway.app',
    ])->assertOk();

    expect(request()->isSecure())->toBeTrue();
});

it('membangun URL absolut memakai https saat berada di balik proxy', function () {
    $this->get('/', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'cheryarta.up.railway.app',
    ])->assertOk();

    expect(url('/'))->toStartWith('https://cheryarta.up.railway.app');
});
