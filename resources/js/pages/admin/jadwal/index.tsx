import EmptyState from '@/components/empty-state';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import { formatJam, formatPlat, formatTanggal } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type ScheduleCard, type ScheduleRow } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { BellRing, CalendarOff, ChevronLeft, ChevronRight, Plus } from 'lucide-react';

interface Props {
    date: string;
    previousDate: string;
    nextDate: string;
    isToday: boolean;
    /** Kalimat siap tampil bila bengkel tutup pada tanggal itu. */
    closedReason: string | null;
    quotaPerSlot: number;
    rows: ScheduleRow[];
}

/**
 * A3 — Jadwal harian (docs/07 §A3, roadmap 1.5.5).
 *
 * Satu baris per slot, satu sel per kapasitas. Jumlah selnya mengikuti
 * `quotaPerSlot` dari server — angka 2 tidak ditulis di sini, karena aturan
 * kuota hanya boleh hidup di `config/booking.php` (temuan B2).
 */
export default function JadwalIndex({ date, previousDate, nextDate, isToday, closedReason, quotaPerSlot, rows }: Props) {
    const buka = (tanggal: string) => {
        router.get(route('admin.schedule.index'), { tanggal }, { preserveState: true, replace: true });
    };

    /**
     * Tautannya sudah terbuka lewat <a href> — ini hanya mencatat bahwa itu
     * terjadi. Sengaja bukan "POST dulu, buka jendela belakangan": peramban
     * memblokir jendela yang dibuka di luar tumpukan gestur pengguna.
     */
    const catatPengingat = (booking: ScheduleCard) => {
        if (booking.reminder === null) {
            return;
        }

        router.post(
            route('admin.bookings.whatsapp.store', booking.booking_code),
            { template_key: booking.reminder.template_key },
            { preserveScroll: true, preserveState: true },
        );
    };

    const totalTerisi = rows.reduce((jumlah, row) => jumlah + row.bookings.length, 0);
    const totalKapasitas = rows.length * quotaPerSlot;

    return (
        <AdminLayout
            title="Jadwal Harian"
            description="Okupansi slot per jam — layar yang dibuka saat menerima booking lewat telepon."
            actions={
                <Button asChild>
                    <Link href={route('admin.bookings.create')}>
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Booking Walk-in
                    </Link>
                </Button>
            }
        >
            <Head title={`Jadwal ${formatTanggal(date)}`} />

            <div className="grid gap-4">
                <div className="border-line bg-surface shadow-card flex flex-wrap items-end justify-between gap-4 rounded-2xl border p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="icon" onClick={() => buka(previousDate)} aria-label="Hari sebelumnya">
                            <ChevronLeft className="h-4 w-4" aria-hidden="true" />
                        </Button>

                        <div className="min-w-48 text-center">
                            <p className="font-semibold">{formatTanggal(date)}</p>
                            {isToday && <p className="text-brand-700 text-xs font-medium">Hari ini</p>}
                        </div>

                        <Button variant="outline" size="icon" onClick={() => buka(nextDate)} aria-label="Hari berikutnya">
                            <ChevronRight className="h-4 w-4" aria-hidden="true" />
                        </Button>
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="tanggal">Lompat ke tanggal</Label>
                        <Input id="tanggal" type="date" value={date} onChange={(e) => e.target.value && buka(e.target.value)} />
                    </div>

                    {rows.length > 0 && (
                        <p className="text-ink-soft text-sm">
                            Terisi <span className="text-ink font-semibold">{totalTerisi}</span> dari {totalKapasitas} kapasitas hari ini.
                        </p>
                    )}
                </div>

                {closedReason !== null ? (
                    <EmptyState icon={CalendarOff} title="Bengkel tutup" description={closedReason} />
                ) : (
                    <ul className="grid gap-3">
                        {rows.map((row) => (
                            <li
                                key={row.time}
                                className={cn(
                                    'shadow-card grid gap-3 rounded-2xl border p-4 md:grid-cols-[7rem_1fr] md:items-center',
                                    row.is_full ? 'border-status-cancel-fg/30 bg-status-cancel-bg/40' : 'border-line bg-surface',
                                )}
                            >
                                <div>
                                    <p className="text-lg font-bold">{formatJam(row.time)}</p>
                                    <p className={cn('text-sm', row.is_full ? 'text-status-cancel-fg font-semibold' : 'text-ink-soft')}>
                                        {row.is_full ? 'Penuh' : `Sisa ${row.remaining} dari ${quotaPerSlot}`}
                                    </p>
                                </div>

                                <div className="grid gap-2 sm:grid-cols-2">
                                    {row.bookings.map((booking) => (
                                        <div
                                            key={booking.booking_code}
                                            className="border-line bg-canvas rounded-xl border p-3"
                                        >
                                            {/* Tautan dan tombol pengingat harus BERSEBELAHAN, bukan
                                                bersarang: tombol di dalam <a> adalah HTML tidak sah dan
                                                membuat penekanannya membuka halaman detail. */}
                                            <Link
                                                href={route('admin.bookings.show', booking.booking_code)}
                                                className="hover:text-brand-700 focus-visible:ring-brand-600 block rounded-lg transition focus-visible:ring-2"
                                            >
                                                <div className="flex flex-wrap items-start justify-between gap-2">
                                                    <p className="text-brand-700 font-mono text-sm font-semibold">
                                                        {booking.booking_code}
                                                    </p>
                                                    <StatusBadge status={booking.status} />
                                                </div>
                                                <p className="mt-1 font-medium">{booking.customer_name}</p>
                                                <p className="text-ink-soft text-sm">
                                                    {booking.vehicle_model} · {formatPlat(booking.vehicle_plate)}
                                                </p>
                                                <p className="text-ink-muted text-xs">{booking.package_name}</p>
                                            </Link>

                                            {/* Pengingat H-1 (keputusan grill Q8). Hanya muncul untuk
                                                booking yang sudah dikonfirmasi dan jadwalnya masih di
                                                depan — kelayakannya diputuskan server. */}
                                            {booking.reminder !== null && (
                                                <Button variant="outline" size="sm" className="mt-3 w-full" asChild>
                                                    <a
                                                        href={booking.reminder.url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        onClick={() => catatPengingat(booking)}
                                                    >
                                                        <BellRing className="h-4 w-4" aria-hidden="true" />
                                                        Kirim Pengingat
                                                    </a>
                                                </Button>
                                            )}
                                        </div>
                                    ))}

                                    {Array.from({ length: row.remaining }).map((_, index) => (
                                        <div
                                            key={`kosong-${index}`}
                                            className="border-line text-ink-muted flex items-center justify-center rounded-xl border border-dashed p-3 text-sm"
                                        >
                                            Slot kosong
                                        </div>
                                    ))}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AdminLayout>
    );
}
