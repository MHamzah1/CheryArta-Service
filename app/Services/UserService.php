<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\LastSuperAdminException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Akun staf internal (docs/07 §A11, roadmap 2.2.3–2.2.4). Super Admin saja.
 *
 * Daftarnya terpisah dari A6 dan tidak boleh saling menembus: A6 hanya
 * menyentuh akun `customer`, kelas ini hanya menyentuh akun staf. Satu jalur
 * untuk keduanya akan membuat penonaktifan sesama staf bisa lewat A6 yang
 * pengamannya lebih longgar.
 *
 * **Pengaman "Super Admin aktif terakhir" adalah operasi baca-lalu-tulis.**
 * Dua permintaan bersamaan yang masing-masing menjatuhkan satu dari dua Super
 * Admin terakhir bisa lolos berdua dan mengunci semua orang dari sistem —
 * persis pola cacat kuota booking sistem lama. Karena itu hitungannya
 * dilakukan `lockForUpdate()` di dalam transaksi, bukan dibaca lalu ditulis.
 *
 * Service tidak menyentuh request(), session(), atau auth()
 * (.claude/rules/10-backend-laravel.md) — pelakunya diterima lewat parameter.
 */
class UserService
{
    /** Sama dengan CustomerAccountService: cukup panjang untuk tidak ditebak, cukup pendek untuk dibacakan. */
    private const PANJANG_PASSWORD_SEMENTARA = 10;

    /**
     * Membuat akun staf baru.
     *
     * Password TIDAK diisi admin (docs/07 §A11): server membuatnya acak dan
     * menandai akunnya `must_reset_password`, sehingga pemiliknya menetapkan
     * sendiri saat login pertama. Nilai kembaliannya hanya ada di memori dan
     * ditampilkan satu kali.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, password: string}
     */
    public function create(array $data): array
    {
        $sementara = $this->passwordSementara();

        $user = DB::transaction(function () use ($data, $sementara): User {
            $user = new User;

            // `role`, `password`, dan `must_reset_password` di luar $fillable —
            // ditetapkan server, tidak pernah datang dari request.
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_wa' => $data['phone_wa'],
                'role' => UserRole::from($data['role']),
                'is_active' => $data['is_active'],
                'password' => $sementara,
                'must_reset_password' => true,
            ])->save();

            return $user;
        });

        return ['user' => $user, 'password' => $sementara];
    }

    /**
     * Mengubah data dan peran akun staf.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws LastSuperAdminException
     */
    public function update(User $actor, User $target, array $data): User
    {
        $peranBaru = UserRole::from($data['role']);
        $aktifBaru = (bool) $data['is_active'];

        DB::transaction(function () use ($actor, $target, $data, $peranBaru, $aktifBaru): void {
            $this->pastikanTidakMenjatuhkanDiriSendiri($actor, $target, $peranBaru, $aktifBaru);
            $this->pastikanBukanSuperAdminTerakhir($target, $peranBaru, $aktifBaru);

            $target->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_wa' => $data['phone_wa'],
                'role' => $peranBaru,
                'is_active' => $aktifBaru,
            ])->save();
        });

        return $target->refresh();
    }

    /**
     * Menonaktifkan atau mengaktifkan kembali akun staf.
     *
     * @return bool Keadaan aktif setelah diubah.
     *
     * @throws LastSuperAdminException
     */
    public function toggleActive(User $actor, User $target): bool
    {
        return DB::transaction(function () use ($actor, $target): bool {
            $aktifBaru = ! $target->is_active;

            $this->pastikanTidakMenjatuhkanDiriSendiri($actor, $target, $target->role, $aktifBaru);
            $this->pastikanBukanSuperAdminTerakhir($target, $target->role, $aktifBaru);

            $target->forceFill(['is_active' => $aktifBaru])->save();

            return $aktifBaru;
        });
    }

    /**
     * Mengatur ulang password akun staf menjadi password sementara.
     *
     * Sama seperti alur customer di A6: tanpa notifikasi email (keputusan
     * final #4), password dikembalikan ke pemanggil untuk disampaikan Super
     * Admin lewat WhatsApp. Ia tidak pernah tersimpan dalam bentuk terbaca,
     * dan tidak pernah masuk activity log (.claude/rules/50).
     *
     * @return string Password sementara — hanya ada di memori, tampil sekali.
     */
    public function resetPassword(User $target): string
    {
        $sementara = $this->passwordSementara();

        $target->forceFill([
            'password' => $sementara,
            'must_reset_password' => true,
            // Sesi "ingat saya" yang lama harus mati bersama passwordnya.
            'remember_token' => null,
        ])->save();

        return $sementara;
    }

    /**
     * Super Admin tidak boleh menurunkan peran atau menonaktifkan dirinya
     * sendiri — mengunci diri sendiri dari panel adalah kesalahan yang tidak
     * bisa diperbaiki dari dalam aplikasi.
     *
     * @throws LastSuperAdminException
     */
    private function pastikanTidakMenjatuhkanDiriSendiri(User $actor, User $target, UserRole $peranBaru, bool $aktifBaru): void
    {
        if ($actor->id !== $target->id) {
            return;
        }

        if ($peranBaru !== UserRole::SuperAdmin) {
            throw new LastSuperAdminException('Anda tidak bisa menurunkan peran akun Anda sendiri. Minta Super Admin lain yang melakukannya.');
        }

        if (! $aktifBaru) {
            throw new LastSuperAdminException('Anda tidak bisa menonaktifkan akun Anda sendiri. Minta Super Admin lain yang melakukannya.');
        }
    }

    /**
     * Selalu harus tersisa minimal satu Super Admin yang aktif.
     *
     * Hitungannya memakai `lockForUpdate()` dan HARUS dipanggil dari dalam
     * transaksi: tanpa kunci, dua permintaan bersamaan sama-sama melihat "masih
     * ada dua" lalu keduanya menjatuhkan satu.
     *
     * @throws LastSuperAdminException
     */
    private function pastikanBukanSuperAdminTerakhir(User $target, UserRole $peranBaru, bool $aktifBaru): void
    {
        $tetapSuperAdminAktif = $peranBaru === UserRole::SuperAdmin && $aktifBaru;

        // Perubahan yang tidak mengurangi jumlah SA aktif tidak perlu diperiksa.
        if ($tetapSuperAdminAktif) {
            return;
        }

        $masihSuperAdminAktif = $target->role === UserRole::SuperAdmin && $target->is_active;

        if (! $masihSuperAdminAktif) {
            return;
        }

        $tersisa = User::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('is_active', true)
            ->whereKeyNot($target->getKey())
            ->lockForUpdate()
            ->count();

        if ($tersisa === 0) {
            throw new LastSuperAdminException(
                'Akun ini adalah Super Admin aktif terakhir. Angkat Super Admin lain lebih dulu sebelum mengubahnya.',
            );
        }
    }

    private function passwordSementara(): string
    {
        return Str::password(self::PANJANG_PASSWORD_SEMENTARA, symbols: false);
    }
}
