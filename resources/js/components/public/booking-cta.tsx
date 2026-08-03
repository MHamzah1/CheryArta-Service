import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface Props {
    label?: string;
    /** Emas hanya untuk SATU ajakan utama per layar (aturan 40 §Warna). */
    variant?: 'gold' | 'brand' | 'outline';
    className?: string;
}

/**
 * Tombol "Booking Servis" — CTA utama seluruh halaman publik.
 *
 * Tamu diantar ke pendaftaran lebih dulu karena booking menuntut akun
 * (docs/05 §5.2); tujuannya dihitung di sini sekali, bukan di setiap halaman
 * yang memasang tombol ini.
 */
export default function BookingCta({ label = 'Booking Servis', variant = 'gold', className }: Props) {
    const { auth } = usePage<SharedData>().props;

    const gaya = {
        gold: 'bg-gold-400 text-ink hover:bg-gold-300',
        brand: 'bg-brand-700 text-white hover:bg-brand-600',
        outline: 'border border-white/30 text-white hover:bg-white/10',
    }[variant];

    return (
        <Link
            href={auth.user ? route('customer.booking.create') : route('register')}
            className={cn(
                'rounded-btn focus-visible:ring-brand-600 inline-flex min-h-11 items-center justify-center px-6 py-3 text-sm font-semibold transition focus-visible:ring-2 focus-visible:ring-offset-2',
                gaya,
                className,
            )}
        >
            {label}
        </Link>
    );
}
