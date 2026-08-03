<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Master data yang sudah dirujuk baris lain hanya boleh dinonaktifkan, tidak
 * dihapus (.claude/rules/30-database.md, roadmap 1.3.5).
 *
 * Dilempar dari Service, bukan diperiksa di controller: aturan yang sama
 * berlaku untuk katalog mobil maupun paket layanan, dan menuliskannya dua kali
 * adalah cara cacat B2 sistem lama bermula.
 *
 * Penanganannya terpusat di bootstrap/app.php — pengguna melihat toast
 * penjelasan, bukan halaman 403 yang tidak menjelaskan apa pun.
 */
final class MasterDataInUseException extends RuntimeException {}
