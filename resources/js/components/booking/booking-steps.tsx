import { LANGKAH } from '@/components/booking/form-data';
import { cn } from '@/lib/utils';
import { Check } from 'lucide-react';

interface Props {
    /** Langkah aktif, 1–4. */
    current: number;
}

/** Penanda kemajuan form booking 4 langkah (docs/06 §Form Booking). */
export default function BookingSteps({ current }: Props) {
    return (
        <ol className="border-line bg-surface mb-6 flex items-center gap-1 overflow-x-auto rounded-2xl border p-2 sm:gap-2">
            {LANGKAH.map((nama, index) => {
                const nomor = index + 1;
                const selesai = nomor < current;
                const aktif = nomor === current;

                return (
                    <li key={nama} className="flex min-w-0 flex-1 items-center gap-2">
                        <span
                            className={cn(
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                selesai && 'bg-brand-100 text-brand-700',
                                aktif && 'bg-brand-700 text-white',
                                !selesai && !aktif && 'bg-canvas text-ink-muted',
                            )}
                            aria-hidden="true"
                        >
                            {selesai ? <Check className="h-4 w-4" /> : nomor}
                        </span>

                        <span className={cn('truncate text-sm', aktif ? 'text-ink font-semibold' : 'text-ink-soft', !aktif && 'hidden sm:inline')}>
                            {nama}
                        </span>

                        <span className="sr-only">
                            Langkah {nomor} dari {LANGKAH.length}
                            {aktif ? ' — sedang diisi' : selesai ? ' — selesai' : ''}
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}
