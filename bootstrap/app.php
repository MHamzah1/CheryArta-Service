<?php

declare(strict_types=1);

use App\Exceptions\InvalidInvoiceTransitionException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\InvalidWhatsAppTransitionException;
use App\Exceptions\InvoiceUnavailableException;
use App\Exceptions\LastSuperAdminException;
use App\Exceptions\MasterDataInUseException;
use App\Exceptions\SlotUnavailableException;
use App\Exceptions\WhatsAppDraftUnavailableException;
use App\Http\Middleware\EnsurePasswordIsReset;
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
            // Akun yang passwordnya dibuatkan admin wajib menetapkan sendiri
            // sebelum memakai apa pun (roadmap 2.2.5c). Ditaruh di grup `web`,
            // bukan pada satu grup rute, karena tandanya berlaku untuk staf
            // MAUPUN customer — dan lubangnya justru ada di rute yang lupa
            // didaftarkan.
            EnsurePasswordIsReset::class,
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

        // Slot direbut orang lain di antara memuat halaman dan menekan tombol.
        // Ditampilkan inline di bawah kolom jam — sama seperti galat validasi
        // lain — bukan sebagai halaman galat (.claude/rules/40).
        $exceptions->render(function (SlotUnavailableException $e, Request $request) {
            $errors = [SlotUnavailableException::FIELD => [$e->getMessage()]];

            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage(), 'errors' => $errors], 422)
                : back()->withInput()->withErrors($errors);
        });

        // Transisi status yang tidak ada di state machine (docs/05 §5.3).
        // Sengaja 422 dan bukan 403: yang ditolak adalah perpindahannya, bukan
        // hak si advisor — ia memang boleh mengubah status booking ini.
        $exceptions->render(function (InvalidStatusTransitionException $e, Request $request) {
            $errors = [InvalidStatusTransitionException::FIELD => [$e->getMessage()]];

            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage(), 'errors' => $errors], 422)
                : back()->withErrors($errors);
        });

        // Perubahan akun staf yang akan mengunci orang dari panelnya sendiri
        // (docs/07 §A11). Sengaja 422 dan bukan 403: Super Admin memang berhak
        // mengubah akun staf — yang ditolak adalah akibat perubahannya.
        // Ditampilkan inline supaya alasannya terbaca di dekat kolomnya.
        $exceptions->render(function (LastSuperAdminException $e, Request $request) {
            $errors = ['role' => [$e->getMessage()]];

            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage(), 'errors' => $errors], 422)
                : back()->withInput()->withErrors($errors);
        });

        // Pesan diminta untuk keadaan yang tidak punya draft: pelanggan tanpa
        // nomor WhatsApp, atau template yang dinonaktifkan di antara halaman
        // dimuat dan tombol ditekan. Tidak ada kolom form yang bisa disorot,
        // jadi penjelasannya tampil sebagai toast — pola yang sama dengan
        // MasterDataInUseException.
        $exceptions->render(function (WhatsAppDraftUnavailableException $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        });

        // Penandaan pesan yang sudah selesai diurus (docs/04 §4.2). Sengaja 422
        // dan bukan 403: advisornya memang berhak menandai pesan booking ini —
        // yang ditolak adalah perpindahan yang sudah tidak ada lagi.
        $exceptions->render(function (InvalidWhatsAppTransitionException $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        });

        // Transisi status invoice yang tidak ada di state machine (docs/05 §5.6).
        // Alasan 422-nya sama dengan InvalidStatusTransitionException di atas:
        // yang ditolak perpindahannya, bukan hak si advisor.
        $exceptions->render(function (InvalidInvoiceTransitionException $e, Request $request) {
            $errors = [InvalidInvoiceTransitionException::FIELD => [$e->getMessage()]];

            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage(), 'errors' => $errors], 422)
                : back()->withErrors($errors);
        });

        // Invoice tidak bisa dibuat untuk booking ini: belum selesai, atau
        // sudah punya invoice yang masih berlaku (keputusan grill Q1 & Q3).
        // Sejak `booking_id` tidak lagi unik, inilah satu-satunya yang
        // menangkap tabrakannya — dan pesannya harus menyebut invoice yang
        // sudah ada, bukan sekadar "gagal".
        $exceptions->render(function (InvoiceUnavailableException $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        });
    })->create();
