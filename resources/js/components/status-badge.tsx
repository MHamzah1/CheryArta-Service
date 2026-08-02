import { cn } from '@/lib/utils';
import { bookingStatusMeta, invoiceStatusMeta, TONE_CLASS } from '@/lib/status';

interface Props {
    status: string;
    kind?: 'booking' | 'invoice';
    className?: string;
}

/**
 * Lencana status. Selalu WARNA + TEKS — tidak pernah warna saja, agar tetap
 * terbaca oleh pengguna dengan buta warna (docs/06 §6.8).
 */
export function StatusBadge({ status, kind = 'booking', className }: Props) {
    const meta = kind === 'invoice' ? invoiceStatusMeta(status) : bookingStatusMeta(status);

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap',
                TONE_CLASS[meta.tone],
                className,
            )}
        >
            {meta.label}
        </span>
    );
}
