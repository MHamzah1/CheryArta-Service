<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the user's password settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/password', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // `must_reset_password` DILEPAS di sini, dan itu bukan sekadar
        // kerapian: sejak roadmap 2.2.5c, EnsurePasswordIsReset mengalihkan
        // pemilik akun bertanda ke halaman ini. Tanpa melepasnya, orang yang
        // baru saja menetapkan passwordnya sendiri tetap dialihkan ke sini —
        // terjebak selamanya di satu halaman.
        //
        // forceFill, bukan update(): kolomnya di luar $fillable dengan sengaja
        // supaya tidak pernah bisa datang dari request.
        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'must_reset_password' => false,
        ])->save();

        return back();
    }
}
