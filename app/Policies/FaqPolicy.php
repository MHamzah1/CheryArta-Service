<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Pertanyaan yang tampil di halaman FAQ publik (docs/07 §A10).
 *
 * Seluruh aturannya ada di SuperAdminOnlyPolicy — Super Admin saja, akun nonaktif ditolak.
 */
class FaqPolicy extends SuperAdminOnlyPolicy {}
