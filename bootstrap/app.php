<?php

declare(strict_types=1);

use App\Exceptions\MasterDataInUseException;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Tanpa baris ini perintah di app/Console/Commands tidak ditemukan sama
    // sekali — withRouting(commands: …) hanya memuat routes/console.php.
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware) {
        // Railway menaruh aplikasi di balik reverse proxy. Tanpa ini Laravel
        // menyangka koneksinya http://, sehingga URL aset Vite dan seluruh
        // redirect memakai skema yang salah — lihat docs/12 §12.3.4.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Penjaga pintu masuk grup rute berdasarkan role.
        // CATATAN: ini TIDAK menggantikan Policy — lihat docs/09 §9.3.
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Master data yang masih dirujuk baris lain: pengguna perlu tahu APA
        // yang harus dilakukan ("nonaktifkan saja"), bukan sekadar halaman
        // galat. Aturannya sendiri ditegakkan di Service — lihat
        // App\Exceptions\MasterDataInUseException.
        $exceptions->render(function (MasterDataInUseException $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        });
    })->create();
