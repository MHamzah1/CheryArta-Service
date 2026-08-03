import { type ReportLabelCount } from '@/types';

interface Props {
    title: string;
    rows: ReportLabelCount[];
    /** Pembagi persentase; biasanya total booking pada periode itu. */
    total: number;
    emptyText: string;
}

/**
 * Pecahan berlabel dengan bar proporsional (docs/07 §A9).
 *
 * Angka mutlaknya selalu ikut ditampilkan, bukan hanya panjang bar. Bar
 * menjawab "mana yang terbesar"; angkanya menjawab "seberapa" — dan hanya
 * angka yang bisa dibaca ulang lewat pembaca layar.
 */
export default function BreakdownList({ title, rows, total, emptyText }: Props) {
    return (
        <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
            <h3 className="mb-3 font-semibold">{title}</h3>

            {rows.length === 0 ? (
                <p className="text-ink-muted py-4 text-sm">{emptyText}</p>
            ) : (
                <dl className="grid gap-2.5">
                    {rows.map((baris) => {
                        const persen = total > 0 ? Math.round((baris.jumlah / total) * 100) : 0;

                        return (
                            <div key={baris.label} className="grid gap-1">
                                <div className="flex items-baseline justify-between gap-3 text-sm">
                                    <dt className="text-ink-soft truncate">{baris.label}</dt>
                                    <dd className="shrink-0 font-semibold tabular-nums">
                                        {baris.jumlah}
                                        <span className="text-ink-muted ml-1 text-xs font-normal">({persen}%)</span>
                                    </dd>
                                </div>
                                <span className="bg-canvas h-1.5 overflow-hidden rounded-full">
                                    {/* Lebar bar memang nilai dinamis — pengecualian
                                        gaya inline yang diizinkan (.claude/rules/20). */}
                                    <span className="bg-brand-500 block h-full rounded-full" style={{ width: `${persen}%` }} />
                                </span>
                            </div>
                        );
                    })}
                </dl>
            )}
        </section>
    );
}
