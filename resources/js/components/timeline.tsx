import { formatTanggalJamIso } from '@/lib/format';
import { bookingStatusMeta } from '@/lib/status';
import { cn } from '@/lib/utils';
import { type TimelineEntry } from '@/types';

interface Props {
    entries: TimelineEntry[];
}

/**
 * Riwayat status booking (docs/06 §6.6).
 *
 * Menjawab kebutuhan yang sama sekali tidak terpenuhi sistem lama: pelanggan
 * bisa melihat kapan bookingnya dikonfirmasi dan mulai dikerjakan tanpa
 * menelepon bengkel (US-C5).
 *
 * Label status diambil dari `lib/status.ts` — satu-satunya sumber kebenaran.
 */
export default function Timeline({ entries }: Props) {
    if (entries.length === 0) {
        return <p className="text-ink-soft text-sm">Belum ada perubahan status.</p>;
    }

    return (
        <ol className="grid gap-0">
            {entries.map((entry, index) => {
                const meta = bookingStatusMeta(entry.to_status);
                const terakhir = index === entries.length - 1;

                return (
                    <li key={entry.id} className="grid grid-cols-[auto_1fr] gap-x-3">
                        <div className="flex flex-col items-center">
                            <span
                                className={cn('mt-1.5 h-3 w-3 shrink-0 rounded-full', terakhir ? 'bg-brand-700' : 'bg-brand-200')}
                                aria-hidden="true"
                            />
                            {!terakhir && <span className="bg-line w-px flex-1" aria-hidden="true" />}
                        </div>

                        <div className={cn('pb-5', terakhir && 'pb-0')}>
                            <p className="font-medium">{meta.label}</p>
                            <p className="text-ink-muted text-sm">{formatTanggalJamIso(entry.created_at)}</p>
                            {entry.note && <p className="text-ink-soft mt-1 max-w-prose text-sm">{entry.note}</p>}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
