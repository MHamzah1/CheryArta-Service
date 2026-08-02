import AdminLayout from '@/layouts/admin-layout';
import { Head } from '@inertiajs/react';
import { CalendarCheck, CheckCircle2, Clock3, Wrench } from 'lucide-react';

/*
 * PLACEHOLDER FASE 0 — membuktikan AdminLayout & navigasi berbasis role.
 * Dashboard sesungguhnya (KPI nyata, grafik tren, okupansi slot, tabel
 * booking hari ini) dibangun di Fase 4 — lihat docs/07-modul-admin.md §A1.
 */

const KPI = [
    { label: 'Booking Hari Ini', value: '—', icon: CalendarCheck },
    { label: 'Perlu Konfirmasi', value: '—', icon: Clock3 },
    { label: 'Sedang Dikerjakan', value: '—', icon: Wrench },
    { label: 'Selesai Bulan Ini', value: '—', icon: CheckCircle2 },
];

export default function AdminDashboard() {
    return (
        <AdminLayout title="Dashboard" description="Ringkasan operasional bengkel hari ini.">
            <Head title="Dashboard Admin" />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {KPI.map((item) => (
                    <div key={item.label} className="bg-surface rounded-card shadow-card p-5">
                        <div className="flex items-start justify-between">
                            <p className="text-ink-soft text-sm font-medium">{item.label}</p>
                            <item.icon className="text-brand-700 h-5 w-5" aria-hidden="true" />
                        </div>
                        <p className="mt-3 text-3xl font-bold">{item.value}</p>
                    </div>
                ))}
            </div>

            <div className="border-line bg-surface rounded-card mt-6 border border-dashed p-6 text-center">
                <p className="font-semibold">Kerangka Fase 0</p>
                <p className="text-ink-soft mx-auto mt-1 max-w-prose text-sm">
                    Grafik tren 30 hari, okupansi slot, tabel booking hari ini, dan aksi cepat ubah status dibangun di
                    Fase 4. Menu di sisi kiri sudah mengikuti struktur pada docs/07-modul-admin.md §A13 dan otomatis
                    menyembunyikan item khusus Super Admin.
                </p>
            </div>
        </AdminLayout>
    );
}
