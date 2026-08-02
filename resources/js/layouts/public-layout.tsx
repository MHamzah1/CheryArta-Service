import { FlashToaster } from '@/components/flash-toaster';
import { Toaster } from '@/components/ui/sonner';
import { WhatsAppFloat } from '@/components/whatsapp-float';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Clock, Mail, MapPin, Menu, Phone, X } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';

/*
 * CATATAN FASE 0: tautan navigasi masih memakai path literal karena rutenya
 * baru dibuat di Fase 2 (landing page). Setelah rute bernama tersedia,
 * ganti menjadi route('public.katalog.index') dst.
 * Lihat docs/10-roadmap-implementasi.md.
 */
const NAV = [
    { title: 'Beranda', href: '/' },
    { title: 'Katalog', href: '/katalog' },
    { title: 'Layanan', href: '/layanan' },
    { title: 'Fasilitas', href: '/fasilitas' },
    { title: 'Tentang', href: '/tentang' },
    { title: 'Kontak', href: '/kontak' },
    { title: 'Cek Servis', href: '/cek-service' },
];

interface Props {
    children: ReactNode;
    /** Header transparan di atas hero, menjadi solid saat digulir. */
    transparentHeader?: boolean;
}

export default function PublicLayout({ children, transparentHeader = false }: Props) {
    const { auth, company } = usePage<SharedData>().props;
    const [menuTerbuka, setMenuTerbuka] = useState(false);
    const [tergulir, setTergulir] = useState(false);

    useEffect(() => {
        const onScroll = () => setTergulir(window.scrollY > 24);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const headerSolid = !transparentHeader || tergulir || menuTerbuka;

    return (
        <div className="bg-canvas text-ink flex min-h-dvh flex-col">
            <header
                className={cn(
                    'fixed inset-x-0 top-0 z-40 transition-all duration-300',
                    headerSolid ? 'bg-surface/95 border-line border-b shadow-sm backdrop-blur' : 'bg-transparent',
                )}
            >
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 md:py-4">
                    <Link
                        href="/"
                        className={cn(
                            'text-xl font-extrabold tracking-tight',
                            headerSolid ? 'text-brand-700' : 'text-white',
                        )}
                    >
                        {company.name}
                    </Link>

                    <nav aria-label="Navigasi utama" className="hidden items-center gap-1 lg:flex">
                        {NAV.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'rounded-btn px-3 py-2 text-sm font-medium transition',
                                    headerSolid
                                        ? 'text-ink-soft hover:bg-brand-50 hover:text-brand-700'
                                        : 'text-white/90 hover:bg-white/10 hover:text-white',
                                )}
                            >
                                {item.title}
                            </Link>
                        ))}
                    </nav>

                    <div className="hidden items-center gap-2 lg:flex">
                        {auth.user ? (
                            <Link
                                href="/dashboard"
                                className="bg-brand-700 rounded-btn hover:bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className={cn(
                                        'rounded-btn px-4 py-2 text-sm font-medium transition',
                                        headerSolid ? 'text-ink-soft hover:text-brand-700' : 'text-white/90 hover:text-white',
                                    )}
                                >
                                    Masuk
                                </Link>
                                <Link
                                    href="/register"
                                    className="bg-gold-400 rounded-btn text-ink hover:bg-gold-300 px-4 py-2 text-sm font-semibold transition"
                                >
                                    Daftar
                                </Link>
                            </>
                        )}
                    </div>

                    <button
                        type="button"
                        onClick={() => setMenuTerbuka((v) => !v)}
                        aria-label={menuTerbuka ? 'Tutup menu' : 'Buka menu'}
                        aria-expanded={menuTerbuka}
                        className={cn(
                            'rounded-btn p-2 lg:hidden',
                            headerSolid ? 'text-ink' : 'text-white',
                        )}
                    >
                        {menuTerbuka ? <X className="h-6 w-6" aria-hidden="true" /> : <Menu className="h-6 w-6" aria-hidden="true" />}
                    </button>
                </div>

                {menuTerbuka && (
                    <div className="bg-surface border-line border-t lg:hidden">
                        <nav aria-label="Navigasi seluler" className="mx-auto max-w-7xl px-4 py-2">
                            {NAV.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    onClick={() => setMenuTerbuka(false)}
                                    className="text-ink border-line hover:bg-brand-50 hover:text-brand-700 block border-b py-3 text-base font-medium last:border-0"
                                >
                                    {item.title}
                                </Link>
                            ))}
                            <div className="flex gap-2 py-4">
                                {auth.user ? (
                                    <Link
                                        href="/dashboard"
                                        className="bg-brand-700 rounded-btn flex-1 px-4 py-3 text-center text-sm font-semibold text-white"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href="/login"
                                            className="border-line rounded-btn text-ink flex-1 border px-4 py-3 text-center text-sm font-medium"
                                        >
                                            Masuk
                                        </Link>
                                        <Link
                                            href="/register"
                                            className="bg-gold-400 rounded-btn text-ink flex-1 px-4 py-3 text-center text-sm font-semibold"
                                        >
                                            Daftar
                                        </Link>
                                    </>
                                )}
                            </div>
                        </nav>
                    </div>
                )}
            </header>

            <main className={cn('flex-1', transparentHeader ? '' : 'pt-16 md:pt-[72px]')}>{children}</main>

            <footer className="bg-brand-900 text-brand-100">
                <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p className="text-lg font-extrabold text-white">{company.name}</p>
                        <p className="mt-2 text-sm">{company.tagline}</p>
                    </div>

                    <div>
                        <p className="mb-3 text-sm font-semibold text-white">Navigasi</p>
                        <ul className="space-y-2 text-sm">
                            {NAV.slice(1, 5).map((item) => (
                                <li key={item.href}>
                                    <Link href={item.href} className="transition hover:text-white">
                                        {item.title}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div>
                        <p className="mb-3 text-sm font-semibold text-white">Kontak</p>
                        <ul className="space-y-2 text-sm">
                            <li className="flex gap-2">
                                <MapPin className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                                <span>{company.address.full}</span>
                            </li>
                            <li className="flex gap-2">
                                <Phone className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                                <span>
                                    {company.phones.landline}
                                    <br />
                                    {company.phones.mobile}
                                </span>
                            </li>
                            <li className="flex gap-2">
                                <Mail className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                                <span className="break-all">{company.email}</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <p className="mb-3 text-sm font-semibold text-white">Jam Operasional</p>
                        <ul className="space-y-2 text-sm">
                            {company.operating_hours_text.map((line) => (
                                <li key={line} className="flex gap-2">
                                    <Clock className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                                    <span>{line}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                <div className="border-brand-700 border-t py-4 text-center text-xs">
                    &copy; {new Date().getFullYear()} {company.name}. Seluruh hak cipta dilindungi.
                </div>
            </footer>

            <WhatsAppFloat />
            <FlashToaster />
            <Toaster />
        </div>
    );
}
