import { StatusBadge } from '@/components/status-badge';
import CustomerLayout from '@/layouts/customer-layout';
import { formatJadwal } from '@/lib/format';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarPlus, Car, History } from 'lucide-react';

/*
 * PLACEHOLDER FASE 0 — membuktikan CustomerLayout, StatusBadge, dan util
 * format berjalan. Isi sesungguhnya (booking terdekat, daftar kendaraan,
 * riwayat) dibangun di Fase 3.
 */

const AKSI = [
    { title: 'Booking Servis', desc: 'Pesan jadwal servis kendaraan Anda', href: '/booking', icon: CalendarPlus },
    { title: 'Kendaraan Saya', desc: 'Kelola unit yang terdaftar', href: '/kendaraan', icon: Car },
    { title: 'Riwayat Servis', desc: 'Lihat servis yang pernah dilakukan', href: '/riwayat', icon: History },
];

export default function Dashboard() {
    const { auth } = usePage<SharedData>().props;

    return (
        <CustomerLayout title={`Halo, ${auth.user?.name ?? 'Pelanggan'}`} description="Ringkasan aktivitas servis Anda.">
            <Head title="Dashboard" />

            <div className="grid gap-4 md:grid-cols-3">
                {AKSI.map((item) => (
                    <Link
                        key={item.href}
                        href={item.href}
                        className="bg-surface rounded-card shadow-card hover:border-brand-200 border border-transparent p-5 transition"
                    >
                        <span className="bg-brand-50 text-brand-700 mb-3 flex h-11 w-11 items-center justify-center rounded-full">
                            <item.icon className="h-5 w-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">{item.title}</p>
                        <p className="text-ink-soft mt-1 text-sm">{item.desc}</p>
                    </Link>
                ))}
            </div>

            <div className="bg-surface rounded-card shadow-card mt-6 p-5">
                <div className="mb-4 flex items-center justify-between">
                    <p className="font-semibold">Booking Terdekat</p>
                    <StatusBadge status="pending" />
                </div>
                <p className="text-ink-soft text-sm">
                    Contoh format jadwal: {formatJadwal('2026-08-03', '09:00')}
                </p>
                <p className="text-ink-muted border-line mt-4 border-t pt-4 text-xs">
                    Kerangka Fase 0 — data booking sesungguhnya tersedia setelah Fase 3.
                </p>
            </div>
        </CustomerLayout>
    );
}
