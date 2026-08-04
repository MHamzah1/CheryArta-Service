<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Perubahan akun staf yang akan mengunci orang dari panelnya sendiri
 * (docs/07 §A11, roadmap 2.2.4).
 *
 * Mencakup tiga hal yang aturannya sama: Super Admin menurunkan perannya
 * sendiri, menonaktifkan dirinya sendiri, dan menjatuhkan Super Admin aktif
 * terakhir. Ketiganya berakhir sama — tidak ada lagi yang bisa masuk untuk
 * memperbaikinya dari dalam aplikasi.
 *
 * Dilempar dari UserService, bukan diperiksa di controller: aturannya harus
 * berlaku di mana pun akun staf diubah, termasuk dari perintah artisan kelak.
 *
 * Pesannya sengaja menjelaskan JALAN KELUARnya ("angkat Super Admin lain lebih
 * dulu"), bukan sekadar menyatakan tindakan ditolak (.claude/rules/40).
 */
final class LastSuperAdminException extends RuntimeException {}
