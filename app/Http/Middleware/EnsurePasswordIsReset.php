<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun bertanda `must_reset_password` wajib menetapkan password sendiri
 * sebelum bisa memakai apa pun (roadmap 2.2.5c, docs/09 §9.2).
 *
 * Tandanya sudah ditulis dengan benar sejak Tahap 6 — akun customer walk-in
 * dan akun yang direset admin — tetapi belum pernah ditegakkan. Selama itu
 * hanya mengenai akun customer, dampaknya kecil. Begitu A11 lahir dan akun
 * STAF dibuat dengan password sementara, penegakannya menjadi wajib: password
 * yang membuka panel internal tidak boleh berlaku lebih dari satu kali masuk.
 *
 * Tanpa pengecualian di bawah, pemiliknya akan terjebak pada pengalihan tanpa
 * ujung — dialihkan ke halaman ganti password, yang juga ikut dialihkan.
 */
class EnsurePasswordIsReset
{
    /**
     * Rute yang tetap boleh dibuka. Diperiksa dengan pola nama rute, bukan
     * URL, supaya perubahan URL tidak diam-diam membuka kembali jebakannya.
     *
     * @var list<string>
     */
    private const DIKECUALIKAN = [
        'password.*',
        'logout',
        'verification.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->must_reset_password) {
            return $next($request);
        }

        if ($request->routeIs(...self::DIKECUALIKAN)) {
            return $next($request);
        }

        // Permintaan Inertia yang dialihkan tetap ditangani klien dengan benar
        // karena Inertia mengikuti redirect 302 sebagai kunjungan penuh.
        return redirect()
            ->route('password.edit')
            ->with('info', 'Tetapkan password Anda sendiri sebelum melanjutkan.');
    }
}
