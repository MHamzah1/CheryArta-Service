<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Pesan dari form kontak publik (docs/07 §A10). Memuat nama, email, dan nomor telepon pengirim, jadi hanya staf yang login boleh melihatnya (docs/09 §9.7).
 *
 * Seluruh aturannya ada di SuperAdminOnlyPolicy — Super Admin saja, akun nonaktif ditolak.
 */
class ContactMessagePolicy extends SuperAdminOnlyPolicy {}
