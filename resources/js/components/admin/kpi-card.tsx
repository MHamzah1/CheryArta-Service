import { Link } from '@inertiajs/react';
import { type LucideIcon } from 'lucide-react';

interface Props {
    label: string;
    value: number;
    icon: LucideIcon;
    /** Tujuan saat kartunya ditekan — daftar booking dengan saringan terisi. */
    href: string;
    /** Keterangan singkat di bawah angka; menjelaskan apa yang dihitung. */
    hint: string;
    /** Menonjolkan kartu yang menuntut tindakan, mis. "Perlu Konfirmasi". */
    urgent?: boolean;
}

/**
 * Kartu KPI dashboard (docs/07 §A1).
 *
 * Selalu berupa tautan: angka yang tidak bisa ditelusuri hanya memindahkan
 * pertanyaan. Menekan "Perlu Konfirmasi 3" harus mengantar ke tiga booking
 * itu, bukan membuat advisor menyaring ulang dari awal.
 */
export default function KpiCard({ label, value, icon: Icon, href, hint, urgent = false }: Props) {
    return (
        <Link
            href={href}
            className="border-line bg-surface shadow-card hover:border-brand-400 focus-visible:ring-brand-600 rounded-2xl border p-4 transition focus-visible:ring-2 focus-visible:outline-none"
        >
            <div className="flex items-start justify-between gap-3">
                <p className="text-ink-soft text-sm font-medium">{label}</p>
                <span
                    className={
                        urgent && value > 0
                            ? 'bg-status-pending-bg text-status-pending-fg flex h-9 w-9 items-center justify-center rounded-full'
                            : 'bg-brand-50 text-brand-700 flex h-9 w-9 items-center justify-center rounded-full'
                    }
                >
                    <Icon className="h-4 w-4" aria-hidden="true" />
                </span>
            </div>

            <p className="mt-2 text-3xl font-bold tabular-nums">{value}</p>
            <p className="text-ink-muted mt-1 text-xs">{hint}</p>
        </Link>
    );
}
