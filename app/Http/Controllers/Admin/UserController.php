<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffUserFilterRequest;
use App\Http\Requests\Admin\StaffUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A11 — Pengguna Internal (docs/07 §A11, roadmap 2.2.3). Super Admin saja.
 *
 * Sampai layar ini ada, akun staf hanya lahir dari `UserSeeder` (**R9**) dan
 * menambah advisor menuntut `tinker` atau DBeaver. Akibat yang lebih halus:
 * matriks hak akses selama ini teruji terhadap akun contoh, bukan orang
 * sungguhan.
 *
 * Daftarnya **hanya akun staf** dan tidak boleh menembus A6 — penyaringannya
 * dijalankan di database, bukan diambil seluruhnya lalu difilter di PHP
 * (temuan S8).
 */
class UserController extends Controller
{
    public function index(StaffUserFilterRequest $request): Response
    {
        Gate::authorize('viewAnyStaff', User::class);

        $pelaku = $request->user();

        $users = User::query()
            ->whereIn('role', [UserRole::SuperAdmin, UserRole::ServiceAdvisor])
            ->when($request->role(), fn ($q, string $role) => $q->where('role', $role))
            ->when($request->status() === 'aktif', fn ($q) => $q->where('is_active', true))
            ->when($request->status() === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('role')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $u): array => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone_wa' => $u->phone_wa,
                'role' => $u->role->value,
                'role_label' => $u->role->label(),
                'is_active' => $u->is_active,
                'last_login_at' => $u->last_login_at?->toIso8601String(),
                'must_reset_password' => $u->must_reset_password,
                // Boleh/tidak dihitung SERVER dan dikirim sebagai boolean —
                // UI tidak pernah menyimpulkan sendiri (.claude/rules/20).
                // Ini kenyamanan, bukan pengaman: UserService tetap menolak
                // meski tombolnya dipaksakan.
                'can_demote' => $this->bolehDijatuhkan($pelaku, $u),
                'can_deactivate' => $this->bolehDijatuhkan($pelaku, $u),
                'is_self' => $pelaku !== null && $pelaku->id === $u->id,
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => ['role' => $request->role(), 'status' => $request->status()],
            'roleOptions' => [
                ['value' => UserRole::SuperAdmin->value, 'label' => UserRole::SuperAdmin->label()],
                ['value' => UserRole::ServiceAdvisor->value, 'label' => UserRole::ServiceAdvisor->label()],
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('createStaff', User::class);

        return Inertia::render('admin/users/form', ['user' => null]);
    }

    public function store(StaffUserRequest $request, UserService $users): RedirectResponse
    {
        $hasil = $users->create($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$hasil['user']->name} berhasil dibuat.")
            // Ditampilkan SATU KALI lalu hilang bersama flash session. Tidak
            // pernah tersimpan terbaca, dan tidak pernah masuk activity log
            // (keputusan grill #8, .claude/rules/50).
            ->with('temporaryPassword', [
                'name' => $hasil['user']->name,
                'password' => $hasil['password'],
            ]);
    }

    public function edit(User $user): Response
    {
        Gate::authorize('updateStaff', $user);

        return Inertia::render('admin/users/form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_wa' => $user->phone_wa,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    public function update(StaffUserRequest $request, User $user, UserService $users): RedirectResponse
    {
        $pelaku = $request->user();
        abort_if($pelaku === null, 403);

        // Pengaman "diri sendiri" dan "SA aktif terakhir" ditegakkan di dalam
        // UserService, terkunci di dalam transaksi — bukan di sini.
        $users->update($pelaku, $user, $request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    public function toggleActive(Request $request, User $user, UserService $users): RedirectResponse
    {
        Gate::authorize('updateStaff', $user);

        $pelaku = $request->user();
        abort_if($pelaku === null, 403);

        $aktif = $users->toggleActive($pelaku, $user);

        return back()->with(
            'success',
            $aktif ? "Akun {$user->name} diaktifkan kembali." : "Akun {$user->name} dinonaktifkan.",
        );
    }

    public function resetPassword(User $user, UserService $users): RedirectResponse
    {
        Gate::authorize('resetStaffPassword', $user);

        $sementara = $users->resetPassword($user);

        return back()
            ->with('success', "Password akun {$user->name} berhasil diatur ulang.")
            ->with('temporaryPassword', ['name' => $user->name, 'password' => $sementara]);
    }

    /**
     * Apakah akun ini masih bisa diturunkan atau dinonaktifkan.
     *
     * Menjawab dua hal sekaligus karena keduanya jatuh pada aturan yang sama:
     * diri sendiri tidak boleh dijatuhkan, dan Super Admin aktif terakhir
     * tidak boleh dijatuhkan siapa pun.
     *
     * Ini hanya untuk merender tombol. Yang menolak sungguhan tetap
     * UserService — dengan hitungan terkunci, karena jawaban di sini bisa
     * basi begitu Super Admin lain dinonaktifkan di tab sebelah.
     */
    private function bolehDijatuhkan(?User $pelaku, User $target): bool
    {
        if ($pelaku !== null && $pelaku->id === $target->id) {
            return false;
        }

        if ($target->role !== UserRole::SuperAdmin || ! $target->is_active) {
            return true;
        }

        return User::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('is_active', true)
            ->whereKeyNot($target->getKey())
            ->exists();
    }
}
