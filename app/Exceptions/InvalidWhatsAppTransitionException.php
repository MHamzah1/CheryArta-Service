<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\WhatsAppMessageStatus;
use RuntimeException;

/**
 * Penandaan pesan yang sudah selesai diurus (docs/04 §4.2).
 *
 * Penyebabnya bukan iseng, melainkan dua advisor yang membuka booking yang
 * sama: yang satu menekan "Tandai Terkirim" setelah yang lain menekan
 * "Lewati". Yang kedua harus melihat penolakan, bukan diam-diam menimpa —
 * jejak yang bisa ditulis ulang bukan jejak.
 *
 * Penanganannya terpusat di bootstrap/app.php, sama seperti
 * InvalidStatusTransitionException.
 */
final class InvalidWhatsAppTransitionException extends RuntimeException
{
    public static function alreadySettled(WhatsAppMessageStatus $current): self
    {
        return new self(sprintf(
            'Pesan ini sudah ditandai "%s" dan tidak bisa diubah lagi. Muat ulang halaman untuk melihat keadaan terkini.',
            $current->label(),
        ));
    }
}
