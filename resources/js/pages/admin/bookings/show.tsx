import BookingStatusPanel from '@/components/admin/booking-status-panel';
import WhatsAppPanel from '@/components/admin/whatsapp-panel';
import ConfirmDialog from '@/components/confirm-dialog';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import {
    formatDurasi,
    formatHargaPaket,
    formatJadwal,
    formatJamIso,
    formatPlat,
    formatTanggalJamIso,
    formatTelepon,
} from '@/lib/format';
import { bookingStatusMeta } from '@/lib/status';
import { type AdminBookingDetail, type AdminTimelineEntry, type StatusTransitionOption, type WhatsAppDraft } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { type ReactNode } from 'react';

interface Props {
    booking: AdminBookingDetail;
    timeline: AdminTimelineEntry[];
    /** Transisi yang sah dari status sekarang — disusun server. */
    statusOptions: StatusTransitionOption[];
    canUpdateStatus: boolean;
    canDelete: boolean;
    whatsapp: WhatsAppDraft | null;
}

function Baris({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="grid gap-0.5">
            <dt className="text-ink-soft text-sm">{label}</dt>
            <dd className="font-medium">{children}</dd>
        </div>
    );
}

function Kartu({ judul, children }: { judul: string; children: ReactNode }) {
    const id = `judul-${judul.toLowerCase().replace(/\s+/g, '-')}`;

    return (
        <section aria-labelledby={id} className="border-line bg-surface shadow-card rounded-2xl border p-5">
            <h2 id={id} className="mb-4 text-lg font-semibold">
                {judul}
            </h2>
            {children}
        </section>
    );
}

export default function AdminBookingShow({ booking, timeline, statusOptions, canUpdateStatus, canDelete, whatsapp }: Props) {
    const hapus = () => {
        router.delete(route('admin.bookings.destroy', booking.booking_code));
    };

    return (
        <AdminLayout
            title={booking.booking_code}
            description={`${booking.source_label} · dibuat ${formatTanggalJamIso(booking.created_at)}`}
            actions={
                <>
                    <Button variant="outline" asChild>
                        <Link href={route('admin.bookings.index')}>
                            <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                            Kembali
                        </Link>
                    </Button>

                    {/* Hanya Super Admin (docs/07 §A14). Tombolnya disembunyikan
                        untuk advisor, tetapi yang menolak tetap server. */}
                    {canDelete && (
                        <ConfirmDialog
                            trigger={
                                <Button variant="outline" aria-label={`Hapus booking ${booking.booking_code}`}>
                                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                                    Hapus
                                </Button>
                            }
                            title="Hapus booking ini dari daftar?"
                            description={`Booking ${booking.booking_code} akan hilang dari daftar. Untuk membatalkan servis, gunakan perubahan status "Dibatalkan" agar alasannya tercatat dan pelanggan bisa melihatnya.`}
                            confirmLabel="Hapus Booking"
                            onConfirm={hapus}
                        />
                    )}
                </>
            }
        >
            <Head title={`Booking ${booking.booking_code}`} />

            <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                <div className="grid gap-6">
                    <Kartu judul="Data Booking">
                        <div className="mb-4 flex flex-wrap items-center gap-3">
                            <StatusBadge status={booking.status} />
                            <span className="text-ink-soft text-sm">
                                Ditangani {booking.handled_by_name ?? 'belum ditentukan'}
                            </span>
                        </div>

                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Baris label="Customer">
                                <Link href={route('admin.customers.show', booking.customer.id)} className="text-brand-700 underline">
                                    {booking.customer.name}
                                </Link>
                                {!booking.customer.is_active && <span className="text-danger-600 ml-2 text-sm">(akun nonaktif)</span>}
                            </Baris>

                            <Baris label="WhatsApp">{formatTelepon(booking.customer.phone_wa)}</Baris>

                            <Baris label="Kendaraan">
                                {booking.vehicle.display_model} · {formatPlat(booking.vehicle.plate_full)}
                                {booking.vehicle.year !== null && (
                                    <span className="text-ink-muted"> · {booking.vehicle.year}</span>
                                )}
                            </Baris>

                            <Baris label="Odometer">
                                {booking.odometer !== null ? `${booking.odometer.toLocaleString('id-ID')} km` : '—'}
                            </Baris>

                            <Baris label="Jadwal">{formatJadwal(booking.booking_date, booking.booking_time)}</Baris>

                            <Baris label="Perkiraan selesai">{formatJamIso(booking.estimated_finish_at)}</Baris>

                            <div className="sm:col-span-2">
                                <Baris label="Paket layanan">
                                    {booking.package_name}
                                    <span className="text-ink-muted font-normal">
                                        {' '}
                                        · {formatDurasi(booking.package_duration_minutes)} ·{' '}
                                        {formatHargaPaket(booking.package_price, booking.package_is_free)}
                                    </span>
                                </Baris>
                            </div>

                            {booking.complaint && (
                                <div className="sm:col-span-2">
                                    <Baris label="Keluhan">
                                        <span className="max-w-prose font-normal">{booking.complaint}</span>
                                    </Baris>
                                </div>
                            )}

                            {booking.cancel_reason && (
                                <div className="sm:col-span-2">
                                    <Baris label="Alasan pembatalan">
                                        <span className="max-w-prose font-normal">{booking.cancel_reason}</span>
                                    </Baris>
                                </div>
                            )}

                            {booking.admin_note && (
                                <div className="sm:col-span-2">
                                    <Baris label="Catatan internal">
                                        <span className="max-w-prose font-normal">{booking.admin_note}</span>
                                    </Baris>
                                </div>
                            )}
                        </dl>

                        {booking.rescheduled_from && (
                            <p className="text-ink-soft border-line mt-4 border-t pt-4 text-sm">
                                Dijadwalkan ulang dari{' '}
                                <Link
                                    href={route('admin.bookings.show', booking.rescheduled_from.booking_code)}
                                    className="text-brand-700 underline"
                                >
                                    {booking.rescheduled_from.booking_code}
                                </Link>{' '}
                                ({formatJadwal(booking.rescheduled_from.booking_date, booking.rescheduled_from.booking_time)}).
                            </p>
                        )}
                    </Kartu>

                    <Kartu judul="Notifikasi WhatsApp">
                        <WhatsAppPanel draft={whatsapp} customerName={booking.customer.name} />
                    </Kartu>
                </div>

                <div className="grid gap-6">
                    {/* Panel tetap dirender untuk booking yang sudah berakhir;
                        isinya yang menjelaskan bahwa tidak ada transisi
                        tersisa. `canUpdateStatus` menjawab "siapa", daftar
                        `statusOptions` menjawab "boleh ke mana". */}
                    {canUpdateStatus && (
                        <Kartu judul="Ubah Status">
                            <BookingStatusPanel booking={booking} options={statusOptions} />
                        </Kartu>
                    )}

                    <Kartu judul="Riwayat Status">
                        {timeline.length === 0 ? (
                            <p className="text-ink-soft text-sm">Belum ada perubahan status.</p>
                        ) : (
                            <ol className="grid gap-4">
                                {timeline.map((entry) => (
                                    <li key={entry.id} className="border-line border-l-2 pl-4">
                                        <p className="font-medium">{bookingStatusMeta(entry.to_status).label}</p>
                                        <p className="text-ink-muted text-sm">
                                            {formatTanggalJamIso(entry.created_at)}
                                            {entry.actor_name && ` · ${entry.actor_name}`}
                                        </p>
                                        {entry.note && <p className="text-ink-soft mt-1 max-w-prose text-sm">{entry.note}</p>}
                                    </li>
                                ))}
                            </ol>
                        )}
                    </Kartu>
                </div>
            </div>
        </AdminLayout>
    );
}
