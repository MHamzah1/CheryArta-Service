import PublicLayout from '@/layouts/public-layout';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Award, Clock, ShieldCheck, Wrench } from 'lucide-react';

/*
 * PLACEHOLDER FASE 0.
 * Landing page sesungguhnya (hero, katalog, layanan, fasilitas, testimoni,
 * FAQ, lokasi) dibangun di Fase 2 — lihat docs/06-desain-ui-ux.md §6.5.
 * Halaman ini hanya membuktikan PublicLayout, design token, dan shared props
 * perusahaan sudah berjalan.
 */

const KEUNGGULAN = [
    { icon: Award, title: 'Teknisi Bersertifikat' },
    { icon: Wrench, title: 'Peralatan Modern' },
    { icon: Clock, title: 'Layanan Cepat' },
    { icon: ShieldCheck, title: 'Garansi Kualitas' },
];

export default function Welcome() {
    const { auth, company } = usePage<SharedData>().props;

    return (
        <PublicLayout transparentHeader>
            <Head title="Servis Chery Resmi di Bekasi" />

            <section className="from-brand-900 to-brand-700 relative bg-gradient-to-br">
                <div className="mx-auto max-w-7xl px-4 pt-32 pb-20 md:pt-40 md:pb-28">
                    <div className="grid items-center gap-10 lg:grid-cols-2">
                        <div>
                            <p className="text-gold-400 mb-3 text-sm font-semibold tracking-wide uppercase">
                                Bengkel Resmi Chery
                            </p>
                            <h1 className="text-3xl leading-tight font-extrabold text-white md:text-5xl">
                                {company.tagline}
                            </h1>
                            <p className="text-brand-100 mt-4 max-w-prose text-base md:text-lg">
                                Pengalaman lebih dari 15 tahun di industri otomotif. Pesan jadwal servis Anda secara
                                online, pantau prosesnya, tanpa perlu antre.
                            </p>

                            <div className="mt-8 flex flex-wrap gap-3">
                                <Link
                                    href={auth.user ? '/booking' : '/register'}
                                    className="bg-gold-400 rounded-btn text-ink hover:bg-gold-300 px-6 py-3 text-sm font-semibold transition"
                                >
                                    Booking Servis
                                </Link>
                                <Link
                                    href="/katalog"
                                    className="rounded-btn border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
                                >
                                    Lihat Katalog
                                </Link>
                            </div>
                        </div>

                        <div className="bg-surface/95 rounded-card shadow-pop p-6 backdrop-blur">
                            <p className="text-ink text-sm font-semibold">Jam Operasional</p>
                            <ul className="text-ink-soft mt-3 space-y-2 text-sm">
                                {company.operating_hours_text.map((line) => (
                                    <li
                                        key={line}
                                        className="border-line flex items-center gap-2 border-b pb-2 last:border-0"
                                    >
                                        <Clock className="text-brand-700 h-4 w-4 shrink-0" aria-hidden="true" />
                                        {line}
                                    </li>
                                ))}
                            </ul>
                            <p className="text-ink-muted mt-4 text-xs">{company.address.full}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-16">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {KEUNGGULAN.map((item) => (
                        <div
                            key={item.title}
                            className="bg-surface rounded-card shadow-card flex items-center gap-3 p-5"
                        >
                            <span className="bg-brand-50 text-brand-700 flex h-11 w-11 shrink-0 items-center justify-center rounded-full">
                                <item.icon className="h-5 w-5" aria-hidden="true" />
                            </span>
                            <span className="text-sm font-semibold">{item.title}</span>
                        </div>
                    ))}
                </div>

                <div className="border-line bg-brand-50/60 rounded-card mt-12 border border-dashed p-6 text-center">
                    <p className="text-ink font-semibold">Halaman ini masih kerangka (Fase 0)</p>
                    <p className="text-ink-soft mt-1 text-sm">
                        Katalog mobil, daftar layanan, galeri fasilitas, testimoni, FAQ, dan peta lokasi dibangun pada
                        Fase 2 sesuai roadmap.
                    </p>
                </div>
            </section>
        </PublicLayout>
    );
}
