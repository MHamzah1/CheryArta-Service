<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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
            ],
        ];
    }
}
