<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Teks pesan yang dibaca pelanggan (docs/07 §A7, matriks docs/09 §9.3).
 *
 * Seluruh aturannya ada di SuperAdminOnlyPolicy — Super Admin saja, akun
 * nonaktif ditolak. Advisor memakai templatenya lewat panel di detail booking,
 * tetapi tidak boleh mengubah kalimatnya.
 *
 * `create` dan `delete` tidak pernah dipanggil: himpunan kuncinya milik kode,
 * dan layarnya hanya menyunting (keputusan grill Q5). Keduanya tetap ikut
 * terwarisi supaya tidak ada celah bila kelak ada rute yang lupa dijaga.
 */
class WhatsAppTemplatePolicy extends SuperAdminOnlyPolicy {}
