import CancelDialog from '@/components/booking/cancel-dialog';
import RescheduleDialog from '@/components/booking/reschedule-dialog';
import { StatusBadge } from '@/components/status-badge';
import Timeline from '@/components/timeline';
import { Button } from '@/components/ui/button';
import CustomerLayout from '@/layouts/customer-layout';
import { formatDurasi, formatHargaPaket, formatJadwal, formatJamIso, formatPlat, formatRupiah } from '@/lib/format';
import { type BookingDetail, type InvoiceSummary, type SlotRules, type TimelineEntry } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Receipt } from 'lucide-react';

interface Props {
    booking: BookingDetail;
    timeline: TimelineEntry[];
    /** Dihitung server lewat BookingPolicy — bukan disimpulkan dari status di sini. */
    canReschedule: boolean;
    canCancel: boolean;
    cancelReasons: string[];
    slotRules: SlotRules;
    /** Hanya terisi bila invoicenya sudah diterbitkan (keputusan grill Q7). */
    invoice: InvoiceSummary | null;
}

export default function BookingShow({
    booking,
    timeline,
    canReschedule,
    canCancel,
    cancelReasons,
    slotRules,
    invoice,
}: Props) {
    return (
        <CustomerLayout>
            <Head title={`Booking ${booking.booking_code}`} />

            <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                <div className="grid gap-6">
                    <div className="border-line bg-surface shadow-card rounded-2xl border p-5">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p className="text-brand-700 font-mono text-lg font-bold tracking-wide">{booking.booking_code}</p>
                                <p className="mt-1 font-medium">
                                    {booking.vehicle_model} · {formatPlat(booking.vehicle_plate)}
                                </p>
                            </div>

                            <StatusBadge status={booking.status} />
                        </div>

                        <dl className="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-ink-soft">Jadwal</dt>
                                <dd className="font-medium">{formatJadwal(booking.booking_date, booking.booking_time)}</dd>
                            </div>

                            <div>
                                <dt className="text-ink-soft">Perkiraan selesai</dt>
                                <dd className="font-medium">{formatJamIso(booking.estimated_finish_at)}</dd>
                            </div>

                            <div className="sm:col-span-2">
                                <dt className="text-ink-soft">Paket layanan</dt>
                                <dd className="max-w-prose font-medium">{booking.package_name}</dd>
                                <dd className="text-ink-muted">Perkiraan pengerjaan {formatDurasi(booking.package_duration_minutes)}</dd>
                            </div>

                            {booking.odometer !== null && (
                                <div>
                                    <dt className="text-ink-soft">Odometer</dt>
                                    <dd className="font-medium">{booking.odometer.toLocaleString('id-ID')} km</dd>
                                </div>
                            )}

                            <div>
                                <dt className="text-ink-soft">Estimasi biaya</dt>
                                <dd className="font-semibold">{formatHargaPaket(booking.package_price, booking.package_is_free)}</dd>
                            </div>

                            {booking.complaint && (
                                <div className="sm:col-span-2">
                                    <dt className="text-ink-soft">Keluhan</dt>
                                    <dd className="max-w-prose">{booking.complaint}</dd>
                                </div>
                            )}

                            {booking.cancel_reason && (
                                <div className="sm:col-span-2">
                                    <dt className="text-ink-soft">Alasan pembatalan</dt>
                                    <dd className="max-w-prose">{booking.cancel_reason}</dd>
                                </div>
                            )}
                        </dl>

                        {booking.rescheduled_from && (
                            <p className="text-ink-soft border-line mt-4 border-t pt-4 text-sm">
                                Dijadwalkan ulang dari{' '}
                                <Link
                                    href={route('customer.booking.show', booking.rescheduled_from.booking_code)}
                                    className="text-brand-700 underline"
                                >
                                    {booking.rescheduled_from.booking_code}
                                </Link>{' '}
                                ({formatJadwal(booking.rescheduled_from.booking_date, booking.rescheduled_from.booking_time)}).
                            </p>
                        )}

                        {/* Invoice muncul hanya setelah DITERBITKAN — server yang
                            memutuskan (keputusan grill Q7). Draft yang masih
                            disunting advisor tidak pernah sampai ke sini, dan
                            URL-nya pun dijawab 404. */}
                        {invoice && (
                            <div className="border-line mt-5 border-t pt-5">
                                <div className="bg-canvas flex flex-wrap items-center justify-between gap-3 rounded-xl p-3">
                                    <div>
                                        <p className="flex flex-wrap items-center gap-2 font-medium">
                                            <span className="font-mono">{invoice.invoice_number}</span>
                                            <StatusBadge status={invoice.status} kind="invoice" />
                                        </p>
                                        <p className="text-ink-soft text-sm">Total {formatRupiah(invoice.total)}</p>
                                    </div>

                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={route('customer.invoice.show', invoice.id)}>
                                            <Receipt className="h-4 w-4" aria-hidden="true" />
                                            Lihat Invoice
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Tombol hanya muncul bila server mengizinkan; server
                            tetap menolak bila URL-nya ditembak langsung. */}
                        {(canReschedule || canCancel) && (
                            <div className="border-line mt-5 flex flex-wrap gap-3 border-t pt-5">
                                {canReschedule && <RescheduleDialog bookingCode={booking.booking_code} rules={slotRules} />}
                                {canCancel && <CancelDialog bookingCode={booking.booking_code} reasons={cancelReasons} />}
                            </div>
                        )}
                    </div>

                    <div>
                        <Button variant="outline" asChild>
                            <Link href={route('customer.history.index')}>
                                <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                                Kembali ke Riwayat
                            </Link>
                        </Button>
                    </div>
                </div>

                <section aria-labelledby="judul-timeline" className="border-line bg-surface shadow-card rounded-2xl border p-5">
                    <h2 id="judul-timeline" className="mb-4 text-lg font-semibold">
                        Riwayat Status
                    </h2>

                    <Timeline entries={timeline} />
                </section>
            </div>
        </CustomerLayout>
    );
}
