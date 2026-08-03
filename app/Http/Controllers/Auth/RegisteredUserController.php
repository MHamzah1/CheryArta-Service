<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * `role` tidak pernah dibaca dari request: kolomnya di luar $fillable dan
     * database memberinya default `customer`. Menerimanya dari masukan
     * pengguna berarti siapa pun bisa mendaftar sebagai super admin.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // Nomor sudah ternormalisasi di RegisterRequest agar aturan `unique`
        // menilai bentuk yang benar; accessor phoneWa() pada model
        // menormalkannya sekali lagi untuk jalur lain (seeder, panel admin).
        $user = User::create($request->validated());

        event(new Registered($user));

        Auth::login($user);

        // Aturan 50 #6 — sesi diregenerasi setiap kali sesi terautentikasi
        // baru dimulai, termasuk lewat registrasi.
        $request->session()->regenerate();

        return to_route('dashboard');
    }
}
