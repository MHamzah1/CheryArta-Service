import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import CustomerLayout from '@/layouts/customer-layout';
import { formatHargaPaket, formatJadwal, formatPlat } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type BookingSummary, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { CalendarPlus, History } from 'lucide-react';

interface Props {
    bookings: Paginated<BookingSummary>;
    vehicles: { id: number; plate_full: string }[];
    filters: { kendaraan: number | null };
    upcomingCount: number;
}

export default function RiwayatIndex({ bookings, vehicles, filters, upcomingCount }: Props) {
    const saring = (kendaraan: number | null) => {
        router.get(route('customer.history.index'), kendaraan === null ? {} : { kendaraan }, { preserveState: true, replace: true });
    };

    return (
        <CustomerLayout
            title="Riwayat Servis"
            description={upcomingCount > 0 ? `Anda punya ${upcomingCount} booking yang masih berjalan.` : 'Seluruh booking Anda, dari yang terbaru.'}
        >
            <Head title="Riwayat Servis" />

            <div className="grid gap-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    {vehicles.length > 1 && (
                        <div className="flex flex-wrap gap-2" role="group" aria-label="Saring per kendaraan">
                            <Button variant={filters.kendaraan === null ? 'default' : 'outline'} size="sm" onClick={() => saring(null)}>
                                Semua
                            </Button>

                            {vehicles.map((vehicle) => (
                                <Button
                                    key={vehicle.id}
                                    variant={filters.kendaraan === vehicle.id ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => saring(vehicle.id)}
                                >
                                    {formatPlat(vehicle.plate_full)}
                                </Button>
                            ))}
                        </div>
                    )}

                    <Button asChild className={cn(vehicles.length > 1 ? '' : 'ml-auto')}>
                        <Link href={route('customer.booking.create')}>
                            <CalendarPlus className="h-4 w-4" aria-hidden="true" />
                            Booking Servis
                        </Link>
                    </Button>
                </div>

                {bookings.data.length === 0 ? (
                    <EmptyState
                        icon={History}
                        title="Belum ada riwayat servis"
                        description="Booking yang Anda buat akan tercatat di sini, lengkap dengan statusnya."
                        action={
                            <Button asChild>
                                <Link href={route('customer.booking.create')}>
                                    <CalendarPlus className="h-4 w-4" aria-hidden="true" />
                                    Buat Booking Pertama
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <ul className="grid gap-3">
                            {bookings.data.map((booking) => (
                                <li key={booking.booking_code}>
                                    <Link
                                        href={route('customer.booking.show', booking.booking_code)}
                                        className="border-line bg-surface shadow-card hover:border-brand-400 focus-visible:ring-brand-600 block rounded-2xl border p-4 transition focus-visible:ring-2"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="text-brand-700 font-mono text-sm font-bold">{booking.booking_code}</p>
                                                <p className="mt-1 font-medium">{formatJadwal(booking.booking_date, booking.booking_time)}</p>
                                                <p className="text-ink-soft text-sm">
                                                    {booking.vehicle_model} · {formatPlat(booking.vehicle_plate)}
                                                </p>
                                                <p className="text-ink-muted max-w-prose text-sm">{booking.package_name}</p>
                                            </div>

                                            <div className="flex flex-col items-end gap-2">
                                                <StatusBadge status={booking.status} />
                                                <span className="text-ink-soft text-sm">
                                                    {formatHargaPaket(booking.package_price, booking.package_is_free)}
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>

                        <Pagination meta={bookings} />
                    </>
                )}
            </div>
        </CustomerLayout>
    );
}
