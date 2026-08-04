<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\HomeRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user instanceof User) {
            // Di luar $fillable dengan sengaja — waktunya ditentukan server,
            // tidak pernah dikirim klien. Dipakai panel admin untuk mengenali
            // akun yang tidak pernah dipakai.
            $user->forceFill(['last_login_at' => now()])->save();
        }

        // Staf diantar ke panelnya, customer ke layarnya sendiri (roadmap
        // 2.2.5). `intended()` tetap dipakai supaya tautan dalam yang memicu
        // login tidak hilang — tujuan per peran hanya jadi bawaan bila tidak
        // ada tujuan tersimpan.
        return redirect()->intended(HomeRoute::for($user));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
