<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Ke mana seseorang diantar setelah masuk (roadmap 2.2.5).
 *
 * Sebelum ini seluruh berkas Auth memanggil `route('dashboard')` apa adanya —
 * bawaan starter kit Laravel yang tidak pernah disesuaikan. Nama rute
 * `dashboard` menunjuk `/dashboard` **milik customer**, sehingga Super Admin
 * dan advisor pun mendarat di layar pelanggan lengkap dengan menunya, dan
 * dashboard admin di `/admin` hanya bisa dicapai dengan mengetiknya sendiri.
 *
 * Tujuannya sekarang ditentukan satu tempat ini saja. Ada lima berkas Auth
 * yang mengantar pengguna setelah suatu aksi (masuk, konfirmasi password,
 * verifikasi email); bila logikanya disalin ke masing-masing, cukup satu yang
 * terlewat untuk membuat cacatnya hidup kembali di satu jalur yang jarang
 * dilewati — persis pola cacat B2 sistem lama.
 */
final class HomeRoute
{
    /**
     * Rute beranda sesuai peran, sebagai path relatif.
     *
     * Relatif, bukan absolut, karena nilainya dipakai sebagai tujuan bawaan
     * `redirect()->intended()` — dan `intended()` membandingkannya dengan
     * URL tujuan yang tersimpan di sesi.
     */
    public static function for(?User $user): string
    {
        return $user?->isStaff()
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);
    }
}
