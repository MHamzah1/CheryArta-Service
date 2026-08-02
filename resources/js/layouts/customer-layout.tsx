import { FlashToaster } from '@/components/flash-toaster';
import { Toaster } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { CalendarPlus, Car, History, LayoutDashboard, LogOut, Menu, User, X } from 'lucide-react';
import { useState, type ReactNode } from 'react';

/*
 * CATATAN FASE 0: path literal; diganti route() saat Fase 3 membuat rutenya.
 */
const NAV = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { title: 'Booking Servis', href: '/booking', icon: CalendarPlus },
    { title: 'Riwayat', href: '/riwayat', icon: History },
    { title: 'Kendaraan', href: '/kendaraan', icon: Car },
];

interface Props {
    children: ReactNode;
    title?: string;
    description?: string;
}

export default function CustomerLayout({ children, title, description }: Props) {
    const { props, url } = usePage<SharedData>();
    const { auth, company } = props;
    const [menuTerbuka, setMenuTerbuka] = useState(false);

    const aktif = (href: string) => url === href || url.startsWith(`${href}/`);

    return (
        <div className="bg-canvas text-ink flex min-h-dvh flex-col">
            <header className="bg-surface border-line sticky top-0 z-40 border-b">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                    <Link href="/" className="text-brand-700 text-lg font-extrabold">
                        {company.name}
                    </Link>

                    <nav aria-label="Navigasi akun" className="hidden items-center gap-1 md:flex">
                        {NAV.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'rounded-btn flex items-center gap-2 px-3 py-2 text-sm font-medium transition',
                                    aktif(item.href)
                                        ? 'bg-brand-50 text-brand-700'
                                        : 'text-ink-soft hover:bg-brand-50 hover:text-brand-700',
                                )}
                            >
                                <item.icon className="h-4 w-4" aria-hidden="true" />
                                {item.title}
                            </Link>
                        ))}
                    </nav>

                    <div className="hidden items-center gap-2 md:flex">
                        <Link
                            href="/settings/profile"
                            className="text-ink-soft rounded-btn hover:bg-brand-50 hover:text-brand-700 flex items-center gap-2 px-3 py-2 text-sm"
                        >
                            <User className="h-4 w-4" aria-hidden="true" />
                            {auth.user?.name}
                        </Link>
                        <button
                            type="button"
                            onClick={() => router.post('/logout')}
                            className="text-ink-soft rounded-btn hover:text-destructive p-2"
                            aria-label="Keluar"
                        >
                            <LogOut className="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>

                    <button
                        type="button"
                        onClick={() => setMenuTerbuka((v) => !v)}
                        aria-label={menuTerbuka ? 'Tutup menu' : 'Buka menu'}
                        aria-expanded={menuTerbuka}
                        className="text-ink rounded-btn p-2 md:hidden"
                    >
                        {menuTerbuka ? <X className="h-6 w-6" aria-hidden="true" /> : <Menu className="h-6 w-6" aria-hidden="true" />}
                    </button>
                </div>

                {menuTerbuka && (
                    <nav aria-label="Navigasi akun seluler" className="border-line border-t px-4 py-2 md:hidden">
                        {NAV.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={() => setMenuTerbuka(false)}
                                className={cn(
                                    'border-line flex items-center gap-3 border-b py-3 text-base font-medium',
                                    aktif(item.href) ? 'text-brand-700' : 'text-ink',
                                )}
                            >
                                <item.icon className="h-5 w-5" aria-hidden="true" />
                                {item.title}
                            </Link>
                        ))}
                        <button
                            type="button"
                            onClick={() => router.post('/logout')}
                            className="text-destructive flex w-full items-center gap-3 py-3 text-base font-medium"
                        >
                            <LogOut className="h-5 w-5" aria-hidden="true" />
                            Keluar
                        </button>
                    </nav>
                )}
            </header>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
                {(title || description) && (
                    <div className="mb-6">
                        {title && <h1 className="text-2xl font-bold md:text-3xl">{title}</h1>}
                        {description && <p className="text-ink-soft mt-1 text-sm">{description}</p>}
                    </div>
                )}
                {children}
            </main>

            <FlashToaster />
            <Toaster />
        </div>
    );
}
