import { FlashToaster } from '@/components/flash-toaster';
import { Toaster } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';
import { type NavItem, type SharedData, type UserRole } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, CalendarRange, Car, LogOut, Menu, ScrollText, Users, Wrench, X } from 'lucide-react';
import { useState, type ReactNode } from 'react';

/*
 * Struktur menu mengikuti docs/07-modul-admin.md §A13.
 *
 * BIG FASE 1 hanya merender menu yang modulnya SUDAH ADA: Jadwal, Booking,
 * Customer, Kendaraan, dan dua master data khusus Super Admin. Dashboard,
 * Invoice, Laporan, Konten, dan Sistem sengaja tidak dirender sama sekali —
 * bukan ditampilkan lalu dinonaktifkan. Menu yang mengantar ke halaman 404
 * membuat orang menyangka aplikasinya rusak.
 *
 * Item "Dashboard" pun tidak ada: `/admin` mengalihkan ke `/admin/bookings`
 * sampai A1 dibangun di F2.1 (keputusan R8).
 *
 * `roles` hanya menyembunyikan menu — otorisasi sesungguhnya ada di
 * middleware + Policy di server (.claude/rules/50-keamanan.md).
 */
const SEMUA_STAF: UserRole[] = ['super_admin', 'service_advisor'];
const SUPER_ADMIN: UserRole[] = ['super_admin'];

/**
 * Menu disusun dari NAMA rute, bukan path literal — mengubah URL di
 * routes/web.php tidak boleh menyisakan menu yang mengarah ke tempat lain.
 *
 * `route(name, undefined, false)` mengembalikan bentuk relatif (`/admin/…`);
 * bentuk absolut bawaan Ziggy tidak bisa dibandingkan dengan `usePage().url`
 * untuk menentukan menu mana yang sedang aktif.
 */
function menuAdmin(): { title: string; items: NavItem[] }[] {
    const path = (name: string) => route(name, undefined, false);

    return [
        {
            title: 'Operasional',
            items: [
                { title: 'Jadwal', url: path('admin.schedule.index'), icon: CalendarRange, roles: SEMUA_STAF },
                { title: 'Booking', url: path('admin.bookings.index'), icon: CalendarDays, roles: SEMUA_STAF },
            ],
        },
        {
            title: 'Data',
            items: [
                { title: 'Customer', url: path('admin.customers.index'), icon: Users, roles: SEMUA_STAF },
                { title: 'Kendaraan', url: path('admin.vehicles.index'), icon: Car, roles: SEMUA_STAF },
            ],
        },
        {
            title: 'Master',
            items: [
                { title: 'Katalog Mobil', url: path('admin.car-models.index'), icon: Car, roles: SUPER_ADMIN },
                { title: 'Paket Layanan', url: path('admin.service-packages.index'), icon: Wrench, roles: SUPER_ADMIN },
            ],
        },
    ];
}

interface Props {
    children: ReactNode;
    title?: string;
    description?: string;
    actions?: ReactNode;
}

export default function AdminLayout({ children, title, description, actions }: Props) {
    const { props, url } = usePage<SharedData>();
    const { auth, company } = props;
    const [sidebarTerbuka, setSidebarTerbuka] = useState(false);

    const role = auth.user?.role;
    const aktif = (href: string) => url === href || url.startsWith(`${href}/`) || url.startsWith(`${href}?`);
    const bolehLihat = (item: NavItem) => !item.roles || !role || item.roles.includes(role);

    const sidebar = (
        <nav aria-label="Navigasi admin" className="space-y-6 px-3 py-4">
            {menuAdmin().map((grup, i) => {
                const items = grup.items.filter(bolehLihat);
                if (items.length === 0) return null;

                return (
                    <div key={grup.title || `grup-${i}`}>
                        {grup.title && (
                            <p className="text-sidebar-foreground/60 mb-2 px-3 text-xs font-semibold tracking-wider uppercase">
                                {grup.title}
                            </p>
                        )}
                        <ul className="space-y-1">
                            {items.map((item) => (
                                <li key={item.url}>
                                    <Link
                                        href={item.url}
                                        onClick={() => setSidebarTerbuka(false)}
                                        className={cn(
                                            'rounded-btn flex items-center gap-3 px-3 py-2 text-sm font-medium transition',
                                            aktif(item.url)
                                                ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                                                : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                        )}
                                    >
                                        {item.icon && <item.icon className="h-4 w-4 shrink-0" aria-hidden="true" />}
                                        {item.title}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                );
            })}
        </nav>
    );

    return (
        <div className="bg-canvas text-ink min-h-dvh">
            {/* Sidebar tetap — layar besar */}
            <aside className="bg-sidebar border-sidebar-border fixed inset-y-0 left-0 z-40 hidden w-64 flex-col overflow-y-auto border-r lg:flex">
                <div className="border-sidebar-border flex items-center gap-2 border-b px-5 py-4">
                    <ScrollText className="text-sidebar-primary h-6 w-6" aria-hidden="true" />
                    <div>
                        <p className="text-sm leading-tight font-extrabold text-white">{company.name}</p>
                        <p className="text-sidebar-foreground/70 text-xs">Panel Internal</p>
                    </div>
                </div>
                {sidebar}
            </aside>

            {/* Drawer — layar kecil */}
            {sidebarTerbuka && (
                <>
                    <div
                        className="fixed inset-0 z-40 bg-black/50 lg:hidden"
                        onClick={() => setSidebarTerbuka(false)}
                        aria-hidden="true"
                    />
                    <aside className="bg-sidebar fixed inset-y-0 left-0 z-50 w-64 overflow-y-auto lg:hidden">
                        <div className="border-sidebar-border flex items-center justify-between border-b px-5 py-4">
                            <p className="text-sm font-extrabold text-white">{company.name}</p>
                            <button
                                type="button"
                                onClick={() => setSidebarTerbuka(false)}
                                aria-label="Tutup menu"
                                className="text-sidebar-foreground p-1"
                            >
                                <X className="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>
                        {sidebar}
                    </aside>
                </>
            )}

            <div className="lg:pl-64">
                <header className="bg-surface border-line sticky top-0 z-30 border-b">
                    <div className="flex items-center justify-between gap-4 px-4 py-3">
                        <button
                            type="button"
                            onClick={() => setSidebarTerbuka(true)}
                            aria-label="Buka menu"
                            className="text-ink rounded-btn p-2 lg:hidden"
                        >
                            <Menu className="h-6 w-6" aria-hidden="true" />
                        </button>

                        <div className="ml-auto flex items-center gap-3">
                            <div className="text-right">
                                <p className="text-sm font-semibold">{auth.user?.name}</p>
                                <p className="text-ink-muted text-xs">
                                    {role === 'super_admin' ? 'Super Admin' : 'Service Advisor'}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => router.post('/logout')}
                                className="text-ink-soft rounded-btn hover:text-destructive p-2"
                                aria-label="Keluar"
                            >
                                <LogOut className="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                </header>

                <main className="p-4 md:p-6">
                    {(title || actions) && (
                        <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                {title && <h1 className="text-xl font-bold md:text-2xl">{title}</h1>}
                                {description && <p className="text-ink-soft mt-1 text-sm">{description}</p>}
                            </div>
                            {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
                        </div>
                    )}
                    {children}
                </main>
            </div>

            <FlashToaster />
            <Toaster />
        </div>
    );
}
