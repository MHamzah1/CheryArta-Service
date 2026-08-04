<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Testimoni pelanggan yang tampil di landing page (docs/07 §A10). Diketik admin, tanpa alur moderasi (keputusan grill #5).
 *
 * Seluruh aturannya ada di SuperAdminOnlyPolicy — Super Admin saja, akun nonaktif ditolak.
 */
class TestimonialPolicy extends SuperAdminOnlyPolicy {}
