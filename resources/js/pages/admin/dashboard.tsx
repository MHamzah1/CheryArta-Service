import BookingTrendChart from '@/components/admin/booking-trend-chart';
import KpiCard from '@/components/admin/kpi-card';
import QuickStatusActions from '@/components/admin/quick-status-actions';
import SlotOccupancyPanel from '@/components/admin/slot-occupancy-panel';
import EmptyState from '@/components/empty-state';
import { StatusBadge } from '@/components/status-badge';
import AdminLayout from '@/layouts/admin-layout';
import { formatJam, formatPlat, formatTanggal, formatTanggalSingkat } from '@/lib/format';
import { type DashboardBookingRow, type DashboardKpi, type DashboardOccupancy, type TrendPoint } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, CalendarCheck, CalendarDays, CheckCircle2, Clock, MessageCircle, Wrench } from 'lucide-react';

interface Props {
    today: string;
    kpi: DashboardKpi;
    trend: TrendPoint[];
    trendDays: number;
    todayBookings: DashboardBookingRow[];
    overdue: DashboardBookingRow[];
    occupancy: DashboardOccupancy;
    /** Booking yang pelanggannya belum dikabari (docs/07 §A1). */
    awaitingWhatsApp: number;
}

/**
 * A1 — Dashboard admin (docs/07 §A1, roadmap 2.1.1–2.1.3).
 *
 * Menjawab "apa yang harus dikerjakan hari ini" dalam satu layar. Sebelum ini
 * pertanyaan itu menuntut tiga layar: saring `pending`, saring `in_progress`,
 * lalu buka Jadwal.
 *
 * Seluruh angka datang dari server. Halaman ini tidak menghitung apa pun —
 * termasuk tidak menyimpulkan "hari ini" dari zona waktu peramban (temuan B7).
 */
export default function AdminDashboard({
    today,
    kpi,
    trend,
    trendDays,
    todayBookings,
    overdue,
    occupancy,
    awaitingWhatsApp,
}: Props) {
    // Bagian yang diminta ulang setelah aksi cepat. Menyebutkannya eksplisit
    // membuat perubahan status memperbarui KPI dan okupansi sekaligus, tanpa
    // memuat ulang seluruh halaman.
    const muatUlang = ['kpi', 'todayBookings', 'overdue', 'occupancy', 'awaitingWhatsApp'];

    return (
        <AdminLayout title="Dashboard" description={`Ringkasan operasional — ${formatTanggal(today)}.`}>
            <Head title="Dashboard" />

            <div className="grid gap-6">
                <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <KpiCard
                        label="Booking Hari Ini"
                        value={kpi.booking_hari_ini}
                        icon={CalendarDays}
                        hint="Di luar yang dibatalkan"
                        href={route('admin.bookings.index', { dari: today, sampai: today })}
                    />
                    <KpiCard
                        label="Perlu Konfirmasi"
                        value={kpi.perlu_konfirmasi}
                        icon={Clock}
                        hint="Seluruh tanggal yang belum lewat"
                        href={route('admin.bookings.index', { status: 'pending', urutan: 'terdekat' })}
                        urgent
                    />
                    <KpiCard
                        label="Sedang Dikerjakan"
                        value={kpi.sedang_dikerjakan}
                        icon={Wrench}
                        hint="Termasuk unit yang menginap"
                        href={route('admin.bookings.index', { status: 'in_progress' })}
                    />
                    <KpiCard
                        label="Selesai Bulan Ini"
                        value={kpi.selesai_bulan_ini}
                        icon={CheckCircle2}
                        hint="Bulan kalender berjalan"
                        href={route('admin.bookings.index', { status: 'completed' })}
                    />
                    {/* A7 (roadmap 2.3.5). Menghitung booking yang statusnya
                        berubah belakangan ini tetapi pelanggannya belum
                        menerima pesan — bukan jumlah draft yang pernah dibuat. */}
                    <KpiCard
                        label="Belum Dikabari"
                        value={awaitingWhatsApp}
                        icon={MessageCircle}
                        hint="Perubahan status 7 hari terakhir"
                        href={route('admin.bookings.index', { wa: 'belum' })}
                        urgent
                    />
                </section>

                {overdue.length > 0 && (
                    <section className="border-status-cancel-fg/30 bg-status-cancel-bg/40 rounded-2xl border p-4">
                        <div className="mb-3 flex items-start gap-2">
                            <AlertTriangle className="text-status-cancel-fg mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                            <div>
                                <h2 className="font-semibold">Booking lewat tanggal yang masih “Dikonfirmasi”</h2>
                                <p className="text-ink-soft text-sm">
                                    {overdue.length} booking sudah melewati jadwalnya tetapi statusnya belum diperbarui. Tandai Tidak Hadir
                                    bila pelanggannya memang tidak datang.
                                </p>
                            </div>
                        </div>

                        <ul className="grid gap-2">
                            {overdue.map((booking) => (
                                <li
                                    key={booking.booking_code}
                                    className="border-line bg-surface grid gap-3 rounded-xl border p-3 md:grid-cols-[1fr_auto] md:items-center"
                                >
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Link
                                                href={route('admin.bookings.show', booking.booking_code)}
                                                className="text-brand-700 font-mono text-sm font-semibold hover:underline"
                                            >
                                                {booking.booking_code}
                                            </Link>
                                            <StatusBadge status={booking.status} />
                                            <span className="text-status-cancel-fg text-xs font-medium">
                                                {formatTanggalSingkat(booking.booking_date)} · {formatJam(booking.booking_time)}
                                            </span>
                                        </div>
                                        <p className="mt-1 text-sm font-medium">{booking.customer_name}</p>
                                        <p className="text-ink-soft text-sm">
                                            {booking.vehicle_model} · {formatPlat(booking.vehicle_plate)}
                                        </p>
                                    </div>

                                    <QuickStatusActions
                                        bookingCode={booking.booking_code}
                                        actions={booking.quick_actions}
                                        reloadOnly={muatUlang}
                                    />
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <div className="grid gap-4 lg:grid-cols-[1.6fr_1fr]">
                    <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
                        <h2 className="mb-3 font-semibold">Tren {trendDays} Hari Terakhir</h2>
                        <BookingTrendChart points={trend} days={trendDays} />
                    </section>

                    <SlotOccupancyPanel occupancy={occupancy} />
                </div>

                <section className="grid gap-3">
                    <h2 className="font-semibold">Booking Hari Ini</h2>

                    {todayBookings.length === 0 ? (
                        <EmptyState
                            icon={CalendarCheck}
                            title="Tidak ada booking hari ini"
                            description="Belum ada jadwal servis untuk hari ini. Booking yang dibatalkan tidak ditampilkan di sini."
                        />
                    ) : (
                        <div className="border-line bg-surface shadow-card overflow-x-auto rounded-2xl border">
                            <table className="w-full min-w-[52rem] text-left text-sm">
                                <thead className="border-line text-ink-soft border-b text-xs uppercase">
                                    <tr>
                                        <th scope="col" className="px-4 py-3 font-semibold">Jam</th>
                                        <th scope="col" className="px-4 py-3 font-semibold">Kode</th>
                                        <th scope="col" className="px-4 py-3 font-semibold">Customer</th>
                                        <th scope="col" className="px-4 py-3 font-semibold">Kendaraan</th>
                                        <th scope="col" className="px-4 py-3 font-semibold">Paket</th>
                                        <th scope="col" className="px-4 py-3 font-semibold">Status</th>
                                        <th scope="col" className="px-4 py-3 font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-line divide-y">
                                    {todayBookings.map((booking) => (
                                        <tr key={booking.booking_code}>
                                            <td className="px-4 py-3 font-semibold tabular-nums">{formatJam(booking.booking_time)}</td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={route('admin.bookings.show', booking.booking_code)}
                                                    className="text-brand-700 font-mono text-xs font-semibold hover:underline"
                                                >
                                                    {booking.booking_code}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3">{booking.customer_name}</td>
                                            <td className="px-4 py-3">
                                                <p>{booking.vehicle_model}</p>
                                                <p className="text-ink-muted text-xs">{formatPlat(booking.vehicle_plate)}</p>
                                            </td>
                                            <td className="text-ink-soft px-4 py-3">{booking.package_name}</td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={booking.status} />
                                            </td>
                                            <td className="px-4 py-3">
                                                <QuickStatusActions
                                                    bookingCode={booking.booking_code}
                                                    actions={booking.quick_actions}
                                                    reloadOnly={muatUlang}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </AdminLayout>
    );
}
