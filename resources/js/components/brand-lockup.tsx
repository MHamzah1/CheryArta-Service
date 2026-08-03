import BrandMark from '@/components/brand-mark';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

interface Props {
    /** Warna emblem & teks. Diwarisi dari induk bila tidak diisi. */
    className?: string;
    /** Ukuran emblem. Rasionya 3:1, jadi lebarnya mengikuti tinggi. */
    size?: 'sm' | 'md' | 'lg';
    /** Sembunyikan nama dealer — untuk header sempit atau tombol ikon. */
    markOnly?: boolean;
}

const UKURAN: Record<NonNullable<Props['size']>, string> = {
    sm: 'h-4',
    md: 'h-5',
    lg: 'h-7',
};

const UKURAN_TEKS: Record<NonNullable<Props['size']>, string> = {
    sm: 'text-base',
    md: 'text-lg',
    lg: 'text-2xl',
};

/**
 * Emblem Chery + nama dealer.
 *
 * Namanya diambil dari shared props (`config/company.php`), bukan ditulis di
 * JSX — aturan 00: alamat, telepon, dan nama perusahaan hanya punya satu
 * sumber. Emblemnya memakai `currentColor`, jadi induknya yang menentukan
 * warna: `text-white` di atas hero, `text-brand-700` di header putih.
 */
export default function BrandLockup({ className, size = 'md', markOnly = false }: Props) {
    const { company } = usePage<SharedData>().props;

    return (
        <span className={cn('inline-flex items-center gap-2.5', className)}>
            <BrandMark className={cn(UKURAN[size], 'w-auto shrink-0')} />

            {markOnly ? (
                <span className="sr-only">{company.name}</span>
            ) : (
                <span className={cn('font-extrabold tracking-tight', UKURAN_TEKS[size])}>{company.name}</span>
            )}
        </span>
    );
}
