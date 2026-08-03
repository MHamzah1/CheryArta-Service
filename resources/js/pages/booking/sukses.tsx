import { Button } from '@/components/ui/button';
import CustomerLayout from '@/layouts/customer-layout';
import { formatHargaPaket, formatJadwal, formatJamIso, formatPlat } from '@/lib/format';
import { type BookingDetail, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarCheck, CircleCheckBig, History, MessageCircle } from 'lucide-react';

interface Props {
    booking: BookingDetail;
}

export default function BookingSukses({ booking }: Props) {
    const { company } = usePage<SharedData>().props;

    const pesanWa = `Halo Chery Arta, saya baru membuat booking servis dengan kode ${booking.booking_code}.`;
    const waUrl = company?.wa_number ? `https://wa.me/${company.wa_number}?text=${encodeURIComponent(pesanWa)}` : null;

    return (
        <CustomerLayout>
            <Head title="Booking Berhasil" />

            <div className="mx-auto grid max-w-2xl gap-6">
                <div className="border-line bg-surface shadow-card rounded-2xl border p-6 text-center">
                    <div className="bg-status-done-bg text-status-done-fg mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full">
                        <CircleCheckBig className="h-7 w-7" aria-hidden="true" />
                    </div>

                    <h1 className="text-xl font-bold md:text-2xl">Booking Anda sudah masuk</h1>
                    <p className="text-ink-soft mx-auto mt-2 max-w-prose text-sm">
                        Advisor kami akan mengonfirmasi jadwal ini. Simpan kode booking di bawah — kode itu bisa dipakai untuk melacak status servis
                        tanpa login.
                    </p>

                    <p className="text-brand-700 mt-5 font-mono text-2xl font-bold tracking-wider">{booking.booking_code}</p>
                </div>

                <div className="border-line bg-surface shadow-card rounded-2xl border p-5">
                    <h2 className="mb-3 font-semibold">Ringkasan</h2>

                    <dl className="grid gap-2 text-sm">
                        <div className="flex flex-wrap justify-between gap-2">
                            <dt className="text-ink-soft">Kendaraan</dt>
                            <dd className="text-right font-medium">
                                {booking.vehicle_model} · {formatPlat(booking.vehicle_plate)}
                            </dd>
                        </div>

                        <div className="flex flex-wrap justify-between gap-2">
                            <dt className="text-ink-soft">Paket layanan</dt>
                            <dd className="max-w-prose text-right font-medium">{booking.package_name}</dd>
                        </div>

                        <div className="flex flex-wrap justify-between gap-2">
                            <dt className="text-ink-soft">Jadwal</dt>
                            <dd className="text-right font-medium">{formatJadwal(booking.booking_date, booking.booking_time)}</dd>
                        </div>

                        <div className="flex flex-wrap justify-between gap-2">
                            <dt className="text-ink-soft">Perkiraan selesai</dt>
                            <dd className="text-right font-medium">{formatJamIso(booking.estimated_finish_at)}</dd>
                        </div>

                        <div className="border-line mt-2 flex flex-wrap justify-between gap-2 border-t pt-3">
                            <dt className="text-ink-soft">Estimasi biaya</dt>
                            <dd className="text-right font-semibold">{formatHargaPaket(booking.package_price, booking.package_is_free)}</dd>
                        </div>
                    </dl>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Button asChild>
                        <Link href={route('customer.booking.show', booking.booking_code)}>
                            <CalendarCheck className="h-4 w-4" aria-hidden="true" />
                            Lihat Detail Booking
                        </Link>
                    </Button>

                    <Button variant="outline" asChild>
                        <Link href={route('customer.history.index')}>
                            <History className="h-4 w-4" aria-hidden="true" />
                            Riwayat Servis
                        </Link>
                    </Button>

                    {waUrl && (
                        <Button variant="outline" asChild>
                            <a href={waUrl} target="_blank" rel="noopener noreferrer">
                                <MessageCircle className="h-4 w-4" aria-hidden="true" />
                                Hubungi Bengkel
                            </a>
                        </Button>
                    )}
                </div>
            </div>
        </CustomerLayout>
    );
}
