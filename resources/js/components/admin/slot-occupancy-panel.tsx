import { formatJam } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type DashboardOccupancy } from '@/types';
import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarOff } from 'lucide-react';

interface Props {
    occupancy: DashboardOccupancy;
}

/**
 * Okupansi slot hari ini — versi RINGKAS BACA-SAJA (keputusan grill #6).
 *
 * Halaman Jadwal (A3) tetap layar kerjanya: di sanalah booking per slot
 * terlihat dan bisa dibuka. Panel ini hanya menjawab "jam berapa yang masih
 * kosong" lalu menautkannya. Menduplikasi A3 di sini berarti dua tempat yang
 * harus diperbarui setiap kali aturan slot berubah.
 *
 * `quota_per_slot` datang dari server — angka 2 tidak boleh ditulis di React
 * (temuan B2).
 */
export default function SlotOccupancyPanel({ occupancy }: Props) {
    const { quota_per_slot: kuota, closed_reason: alasanTutup, slots } = occupancy;

    return (
        <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
            <div className="mb-3 flex items-center justify-between gap-3">
                <h2 className="font-semibold">Okupansi Hari Ini</h2>
                <Link
                    href={route('admin.schedule.index')}
                    className="text-brand-700 rounded-btn focus-visible:ring-brand-600 inline-flex items-center gap-1 text-sm font-medium hover:underline focus-visible:ring-2"
                >
                    Buka Jadwal
                    <ArrowRight className="h-3.5 w-3.5" aria-hidden="true" />
                </Link>
            </div>

            {alasanTutup !== null ? (
                <div className="text-ink-soft flex items-center gap-2 py-6 text-sm">
                    <CalendarOff className="h-4 w-4 shrink-0" aria-hidden="true" />
                    {alasanTutup}
                </div>
            ) : (
                <ul className="grid gap-1.5">
                    {slots.map((slot) => {
                        const terisi = kuota - slot.remaining;
                        const persen = kuota > 0 ? (terisi / kuota) * 100 : 0;

                        return (
                            <li
                                key={slot.time}
                                className="grid grid-cols-[3.5rem_1fr_auto] items-center gap-3 text-sm"
                                // Slot penuh ditandai warna DAN teks — lencana
                                // yang hanya berwarna tidak terbaca semua orang.
                                aria-disabled={slot.is_full}
                            >
                                <span className="text-ink-soft tabular-nums">{formatJam(slot.time)}</span>

                                <span className="bg-canvas h-2 overflow-hidden rounded-full">
                                    <span
                                        className={cn('block h-full rounded-full', slot.is_full ? 'bg-status-cancel-fg' : 'bg-brand-500')}
                                        // Lebar bar memang nilai dinamis — satu-satunya
                                        // pengecualian gaya inline (.claude/rules/20).
                                        style={{ width: `${persen}%` }}
                                    />
                                </span>

                                <span className={cn('tabular-nums', slot.is_full ? 'text-status-cancel-fg font-semibold' : 'text-ink-soft')}>
                                    {slot.is_full ? 'Penuh' : `${terisi}/${kuota}`}
                                </span>
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
