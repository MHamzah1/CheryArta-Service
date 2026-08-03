import { cn } from '@/lib/utils';
import { type Paginated } from '@/types';
import { Link } from '@inertiajs/react';

interface Props {
    /** Meta paginasi apa adanya dari Laravel — daftar panjang selalu dipaginasi di server. */
    meta: Pick<Paginated<unknown>, 'links' | 'from' | 'to' | 'total' | 'last_page'>;
}

/** Label bawaan Laravel memakai entitas HTML; diterjemahkan di sini. */
function labelIndonesia(label: string): string {
    if (label.includes('Previous')) return 'Sebelumnya';
    if (label.includes('Next')) return 'Berikutnya';

    return label;
}

export default function Pagination({ meta }: Props) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <nav aria-label="Navigasi halaman" className="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p className="text-ink-soft text-sm">
                Menampilkan {meta.from ?? 0}–{meta.to ?? 0} dari {meta.total} data
            </p>

            <ul className="flex flex-wrap gap-1">
                {meta.links.map((link) => (
                    <li key={link.label}>
                        {link.url ? (
                            <Link
                                href={link.url}
                                preserveScroll
                                aria-current={link.active ? 'page' : undefined}
                                className={cn(
                                    'rounded-btn focus-visible:ring-brand-600 inline-flex h-9 min-w-9 items-center justify-center px-3 text-sm font-medium focus-visible:ring-2',
                                    link.active ? 'bg-brand-700 text-white' : 'border-line text-ink-soft hover:bg-canvas border',
                                )}
                            >
                                {labelIndonesia(link.label)}
                            </Link>
                        ) : (
                            <span className="border-line text-ink-muted rounded-btn inline-flex h-9 min-w-9 items-center justify-center border px-3 text-sm">
                                {labelIndonesia(link.label)}
                            </span>
                        )}
                    </li>
                ))}
            </ul>
        </nav>
    );
}
