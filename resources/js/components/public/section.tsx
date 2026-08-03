import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface Props {
    /** Dipakai tautan lompat dari beranda, mis. `#fasilitas`. */
    id?: string;
    eyebrow?: string;
    title: string;
    description?: string;
    /** Tautan "lihat semua" di sisi kanan judul. */
    action?: ReactNode;
    /** Latar abu untuk memisahkan seksi bersebelahan tanpa menambah garis. */
    tone?: 'surface' | 'canvas' | 'brand';
    children: ReactNode;
    className?: string;
}

/**
 * Kerangka satu seksi halaman publik: jarak `py-16` → `py-24`, lebar isi
 * `max-w-7xl`, dan judul yang seragam (aturan 40 §Jarak & Bentuk).
 *
 * Ada supaya jarak antar seksi tidak diketik ulang di setiap halaman dan
 * pelan-pelan berbeda satu sama lain.
 */
export default function Section({
    id,
    eyebrow,
    title,
    description,
    action,
    tone = 'surface',
    children,
    className,
}: Props) {
    const latar = {
        surface: 'bg-surface',
        canvas: 'bg-canvas',
        brand: 'bg-brand-900 text-brand-100',
    }[tone];

    return (
        <section id={id} className={cn('py-16 md:py-24', latar, className)}>
            <div className="mx-auto max-w-7xl px-4">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4 md:mb-12">
                    <div>
                        {eyebrow && (
                            <p
                                className={cn(
                                    'mb-2 text-sm font-semibold tracking-wide uppercase',
                                    tone === 'brand' ? 'text-gold-400' : 'text-brand-700',
                                )}
                            >
                                {eyebrow}
                            </p>
                        )}
                        <h2
                            className={cn(
                                'text-2xl font-bold md:text-4xl',
                                tone === 'brand' ? 'text-white' : 'text-ink',
                            )}
                        >
                            {title}
                        </h2>
                        {description && (
                            <p className={cn('mt-3 max-w-prose', tone === 'brand' ? 'text-brand-100' : 'text-ink-soft')}>
                                {description}
                            </p>
                        )}
                    </div>

                    {action}
                </div>

                {children}
            </div>
        </section>
    );
}
