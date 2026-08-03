import ConfirmDialog from '@/components/confirm-dialog';
import DataTable, { type Column } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { formatJam, formatPlat, formatTanggalJamIso, formatTanggalSingkat, formatTelepon } from '@/lib/format';
import {
    type AdminBookingRow,
    type CustomerDetail,
    type CustomerStats,
    type CustomerVehicleRow,
    type Paginated,
} from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Car, CalendarPlus, Eye, KeyRound, UserX } from 'lucide-react';
import { type ReactNode } from 'react';

interface Props {
    customer: CustomerDetail;
    vehicles: CustomerVehicleRow[];
    bookings: Paginated<AdminBookingRow>;
    stats: CustomerStats;
    /** Dihitung server lewat UserPolicy — Super Admin saja. */
    canToggleActive: boolean;
    canResetPassword: boolean;
}

const KOLOM_BOOKING: Column<AdminBookingRow>[] = [
    {
        key: 'code',
        header: 'Kode',
        primary: true,
        cell: (row) => <span className="text-brand-700 font-mono text-sm font-semibold">{row.booking_code}</span>,
    },
    {
        key: 'jadwal',
        header: 'Jadwal',
        cell: (row) => `${formatTanggalSingkat(row.booking_date)}, ${formatJam(row.booking_time)}`,
    },
    { key: 'kendaraan', header: 'Kendaraan', cell: (row) => formatPlat(row.vehicle_plate) },
    { key: 'paket', header: 'Paket', cell: (row) => row.package_name },
    { key: 'status', header: 'Status', cell: (row) => <StatusBadge status={row.status} /> },
];

function Kartu({ judul, children, aksi }: { judul: string; children: ReactNode; aksi?: ReactNode }) {
    const id = `judul-${judul.toLowerCase().replace(/\s+/g, '-')}`;

    return (
        <section aria-labelledby={id} className="border-line bg-surface shadow-card rounded-2xl border p-5">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 id={id} className="text-lg font-semibold">
                    {judul}
                </h2>
                {aksi}
            </div>
            {children}
        </section>
    );
}

function Angka({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="border-line bg-surface shadow-card rounded-2xl border p-4">
            <p className="text-ink-soft text-sm">{label}</p>
            <p className="mt-1 text-2xl font-bold">{value}</p>
        </div>
    );
}

export default function AdminCustomerShow({ customer, vehicles, bookings, stats, canToggleActive, canResetPassword }: Props) {
    const ubahStatusAkun = () => {
        router.put(route('admin.customers.toggle-active', customer.id), {}, { preserveScroll: true });
    };

    const resetPassword = () => {
        router.put(route('admin.customers.reset-password', customer.id), {}, { preserveScroll: true });
    };

    return (
        <AdminLayout
            title={customer.name}
            description={`Terdaftar ${formatTanggalJamIso(customer.created_at)}`}
            actions={
                <>
                    <Button variant="outline" asChild>
                        <Link href={route('admin.customers.index')}>
                            <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                            Kembali
                        </Link>
                    </Button>

                    <Button asChild>
                        <Link href={route('admin.bookings.create', { pelanggan: customer.id })}>
                            <CalendarPlus className="h-4 w-4" aria-hidden="true" />
                            Buatkan Booking
                        </Link>
                    </Button>
                </>
            }
        >
            <Head title={`Customer — ${customer.name}`} />

            <div className="grid gap-6">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Angka label="Total booking" value={stats.total_bookings} />
                    <Angka label="Servis selesai" value={stats.completed_bookings} />
                    <Angka label="Sedang berjalan" value={stats.upcoming_bookings} />
                    <Angka
                        label="Servis terakhir"
                        value={
                            <span className="text-base">
                                {stats.last_service_date ? formatTanggalSingkat(stats.last_service_date) : 'Belum pernah'}
                            </span>
                        }
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
                    <Kartu judul="Profil">
                        <dl className="grid gap-3 text-sm">
                            <div>
                                <dt className="text-ink-soft">Email</dt>
                                <dd className="font-medium">{customer.email}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-soft">WhatsApp</dt>
                                <dd className="font-medium">{formatTelepon(customer.phone_wa)}</dd>
                            </div>
                            {customer.address && (
                                <div>
                                    <dt className="text-ink-soft">Alamat</dt>
                                    <dd className="max-w-prose">{customer.address}</dd>
                                </div>
                            )}
                            <div>
                                <dt className="text-ink-soft">Terakhir masuk</dt>
                                <dd className="font-medium">
                                    {customer.last_login_at ? formatTanggalJamIso(customer.last_login_at) : 'Belum pernah masuk'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-soft">Status akun</dt>
                                <dd className="mt-1 flex flex-wrap gap-2">
                                    <Badge variant={customer.is_active ? 'default' : 'secondary'}>
                                        {customer.is_active ? 'Aktif' : 'Nonaktif'}
                                    </Badge>
                                    {customer.must_reset_password && <Badge variant="secondary">Password perlu diatur ulang</Badge>}
                                </dd>
                            </div>
                        </dl>

                        {/* Dua aksi berikut khusus Super Admin (docs/07 §A14).
                            Tombolnya disembunyikan untuk advisor, dan UserPolicy
                            tetap menolak bila rutenya ditembak langsung. */}
                        {(canToggleActive || canResetPassword) && (
                            <div className="border-line mt-5 flex flex-wrap gap-2 border-t pt-5">
                                {canToggleActive && (
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="outline" size="sm">
                                                <UserX className="h-4 w-4" aria-hidden="true" />
                                                {customer.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun'}
                                            </Button>
                                        }
                                        title={customer.is_active ? 'Nonaktifkan akun ini?' : 'Aktifkan kembali akun ini?'}
                                        description={
                                            customer.is_active
                                                ? `${customer.name} tidak akan bisa masuk lagi. Seluruh booking dan riwayat servisnya tetap tersimpan.`
                                                : `${customer.name} bisa masuk kembali dengan password yang sama seperti sebelumnya.`
                                        }
                                        confirmLabel={customer.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun'}
                                        onConfirm={ubahStatusAkun}
                                    />
                                )}

                                {canResetPassword && (
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="outline" size="sm">
                                                <KeyRound className="h-4 w-4" aria-hidden="true" />
                                                Reset Password
                                            </Button>
                                        }
                                        title="Reset password pelanggan ini?"
                                        description="Password lama langsung tidak berlaku. Password sementaranya tampil sekali di layar ini — sampaikan lewat WhatsApp, karena tidak bisa dilihat lagi setelahnya."
                                        confirmLabel="Reset Password"
                                        onConfirm={resetPassword}
                                    />
                                )}
                            </div>
                        )}
                    </Kartu>

                    <Kartu judul="Kendaraan">
                        {vehicles.length === 0 ? (
                            <EmptyState
                                icon={Car}
                                title="Belum ada kendaraan"
                                description="Kendaraan akan tercatat saat pelanggan menambahkannya sendiri, atau saat dibuatkan lewat booking walk-in."
                            />
                        ) : (
                            <ul className="grid gap-3 sm:grid-cols-2">
                                {vehicles.map((vehicle) => (
                                    <li key={vehicle.id} className="border-line bg-canvas rounded-xl border p-3">
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <p className="text-brand-700 font-bold">{formatPlat(vehicle.plate_full)}</p>
                                            {vehicle.is_primary && <Badge variant="secondary">Utama</Badge>}
                                        </div>
                                        <p className="mt-1 font-medium">{vehicle.display_model}</p>
                                        <p className="text-ink-soft text-sm">
                                            {[
                                                vehicle.year,
                                                vehicle.color,
                                                vehicle.last_odometer !== null &&
                                                    `${vehicle.last_odometer.toLocaleString('id-ID')} km`,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ') || 'Tanpa keterangan tambahan'}
                                        </p>
                                        <p className="text-ink-muted text-xs">{vehicle.bookings_count} booking</p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Kartu>
                </div>

                <Kartu judul="Riwayat Booking">
                    {bookings.data.length === 0 ? (
                        <p className="text-ink-soft text-sm">Pelanggan ini belum pernah memesan servis.</p>
                    ) : (
                        <>
                            <DataTable
                                caption={`Riwayat booking ${customer.name}`}
                                columns={KOLOM_BOOKING}
                                rows={bookings.data}
                                rowKey={(row) => row.booking_code}
                                actions={(row) => (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={route('admin.bookings.show', row.booking_code)}>
                                            <Eye className="h-4 w-4" aria-hidden="true" />
                                            Detail
                                        </Link>
                                    </Button>
                                )}
                            />

                            <Pagination meta={bookings} />
                        </>
                    )}
                </Kartu>
            </div>
        </AdminLayout>
    );
}
