<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props yang dibagikan ke seluruh halaman.
     *
     * Profil perusahaan sengaja dibagikan di sini supaya halaman React tidak
     * pernah menulis alamat/telepon/jam operasional langsung di JSX
     * (lihat .claude/rules/00-konteks-proyek.md).
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'name' => config('app.name'),

            // Asal URL absolut untuk tag Open Graph (docs/06 §6.9). Diambil
            // dari permintaan yang sedang berjalan, bukan dari window di
            // peramban — meta tag harus sudah benar sebelum JavaScript jalan.
            'appUrl' => fn (): string => rtrim(url('/'), '/'),

            'auth' => [
                'user' => $request->user(),
            ],

            // Lencana "pesan belum dibaca" di sidebar (docs/07 §A10).
            //
            // Closure, jadi kueri ini TIDAK berjalan pada permintaan yang tidak
            // memerlukannya. Hanya untuk Super Admin — merekalah satu-satunya
            // yang boleh membuka layarnya, dan angka pesan pelanggan yang
            // menunggu tidak perlu bocor ke props peran lain.
            'unreadContactMessages' => function () use ($request): ?int {
                $user = $request->user();

                if (! $user instanceof User || ! $user->isSuperAdmin()) {
                    return null;
                }

                return ContactMessage::query()->where('is_read', false)->count();
            },

            // Closure = dievaluasi malas, tidak ikut terkirim pada partial
            // reload yang tidak memintanya.
            'company' => fn (): array => [
                'name' => config('company.name'),
                'tagline' => config('company.tagline'),
                'address' => config('company.address'),
                'phones' => config('company.phones'),
                'email' => config('company.email'),
                'wa_number' => config('company.wa_number'),
                'operating_hours_text' => config('company.operating_hours_text'),
                'social' => config('company.social'),
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                // Password sementara akun staf (docs/07 §A11). Lewat flash
                // dengan sengaja: ia hidup untuk SATU tampilan lalu hilang
                // bersama sesinya, dan tidak pernah tersimpan terbaca di mana
                // pun (keputusan grill #8).
                'temporaryPassword' => fn () => $request->session()->get('temporaryPassword'),
            ],
        ];
    }
}
