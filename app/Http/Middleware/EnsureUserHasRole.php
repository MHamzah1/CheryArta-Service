<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Melindungi grup rute berdasarkan role.
 *
 * CATATAN: middleware ini TIDAK menggantikan Policy. Ia hanya menjaga pintu
 * masuk grup rute; keputusan per objek (booking milik siapa, invoice boleh
 * di-void oleh siapa) tetap wajib lewat Policy.
 * Lihat docs/09-keamanan-hak-akses.md §9.3.
 *
 * Pemakaian: ->middleware('role:super_admin')
 *            ->middleware('role:super_admin,service_advisor')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Akun Anda tidak memiliki akses ke halaman ini.');
        }

        $allowed = array_map(
            fn (string $role) => UserRole::from($role),
            $roles,
        );

        if (! in_array($user->role, $allowed, true)) {
            abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
        }

        return $next($request);
    }
}
