import BookingFilterBar from '@/components/admin/booking-filter-bar';
import DataTable, { type Column } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { formatJam, formatPlat, formatTanggalSingkat } from '@/lib/format';
import { type AdminBookingFilters, type AdminBookingRow, type Paginated, type SelectOption } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Eye, Plus } from 'lucide-react';

interface Props {
    bookings: Paginated<AdminBookingRow>;
    filters: AdminBookingFilters;
    statusOptions: { value: string; label: string }[];
    packageOptions: SelectOption[];
    advisorOptions: SelectOption[];
    today: string;
}

const KOLOM: Column<AdminBookingRow>[] = [
    {
        key: 'code',
        header: 'Kode',
        primary: true,
        cell: (row) => (
            <div>
                <p className="text-brand-700 font-mono text-sm font-semibold">{row.booking_code}</p>
                {row.source === 'walk_in' && <p className="text-ink-muted text-xs">{row.source_label}</p>}
            </div>
        ),
    },
    {
        key: 'jadwal',
        header: 'Jadwal',
        cell: (row) => (
            <div>
                <p className="font-medium">{formatTanggalSingkat(row.booking_date)}</p>
                <p className="text-ink-muted text-xs">{formatJam(row.booking_time)}</p>
            </div>
        ),
    },
    { key: 'customer', header: 'Customer', cell: (row) => row.customer_name },
    {
        key: 'kendaraan',
        header: 'Kendaraan',
        cell: (row) => (
            <div>
                <p className="font-medium">{formatPlat(row.vehicle_plate)}</p>
                <p className="text-ink-muted text-xs">{row.vehicle_model}</p>
            </div>
        ),
    },
    { key: 'paket', header: 'Paket', cell: (row) => row.package_name },
    { key: 'status', header: 'Status', cell: (row) => <StatusBadge status={row.status} /> },
    { key: 'advisor', header: 'Advisor', cell: (row) => row.handled_by_name ?? '—' },
];

export default function AdminBookingIndex({ bookings, filters, statusOptions, packageOptions, advisorOptions, today }: Props) {
    const aksi = (row: AdminBookingRow) => (
        <Button variant="outline" size="sm" asChild>
            <Link href={route('admin.bookings.show', row.booking_code)}>
                <Eye className="h-4 w-4" aria-hidden="true" />
                Detail
            </Link>
        </Button>
    );

    const adaSaringan = Object.entries(filters).some(([kunci, nilai]) => kunci !== 'urutan' && nilai !== null);

    return (
        <AdminLayout
            title="Booking"
            description="Seluruh pesanan servis, dari pemesanan online maupun pelanggan yang datang langsung."
            actions={
                <Button asChild>
                    <Link href={route('admin.bookings.create')}>
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Booking Walk-in
                    </Link>
                </Button>
            }
        >
            <Head title="Booking" />

            <div className="grid gap-4">
                <BookingFilterBar
                    filters={filters}
                    statusOptions={statusOptions}
                    packageOptions={packageOptions}
                    advisorOptions={advisorOptions}
                    today={today}
                />

                {bookings.data.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title={adaSaringan ? 'Tidak ada booking yang cocok' : 'Belum ada booking'}
                        description={
                            adaSaringan
                                ? 'Coba longgarkan saringannya — misalnya kosongkan rentang tanggal atau statusnya.'
                                : 'Booking dari pelanggan akan muncul di sini. Pelanggan yang datang langsung bisa dibuatkan lewat tombol Booking Walk-in.'
                        }
                        action={
                            <Button asChild>
                                <Link href={route('admin.bookings.create')}>
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    Booking Walk-in
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <p className="text-ink-soft text-sm">
                            {bookings.total} booking ditemukan
                            {bookings.last_page > 1 && ` · halaman ${bookings.current_page} dari ${bookings.last_page}`}.
                        </p>

                        <DataTable
                            caption="Daftar booking servis"
                            columns={KOLOM}
                            rows={bookings.data}
                            rowKey={(row) => row.booking_code}
                            actions={aksi}
                        />

                        <Pagination meta={bookings} />
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
