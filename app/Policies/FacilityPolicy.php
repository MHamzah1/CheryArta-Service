<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Fasilitas yang tampil di landing page (docs/07 §A10).
 *
 * Seluruh aturannya ada di SuperAdminOnlyPolicy — Super Admin saja, akun nonaktif ditolak.
 */
class FacilityPolicy extends SuperAdminOnlyPolicy {}
