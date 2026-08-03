import EmptyState from '@/components/empty-state';
import BookingCta from '@/components/public/booking-cta';
import BookingSteps from '@/components/public/booking-steps';
import FaqAccordion from '@/components/public/faq-accordion';
import SectionRoot from '@/components/public/section';
import ServicePackageCard from '@/components/public/service-package-card';
import Seo from '@/components/seo';
import PublicLayout from '@/layouts/public-layout';
import { type PublicFaq, type ServicePackageGroup } from '@/types';
import { Link } from '@inertiajs/react';
import { Wrench } from 'lucide-react';

interface Props {
    groups: ServicePackageGroup[];
    faqs: PublicFaq[];
}

export default function Layanan({ groups, faqs }: Props) {
    const kosong = groups.length === 0;

    return (
        <PublicLayout>
            <Seo
                title="Paket Layanan Servis"
                description="Paket perawatan resmi Chery: perawatan pertama, servis berkala gratis, dan layanan lainnya — lengkap dengan estimasi waktu dan biayanya."
            />

            <section className="from-brand-900 to-brand-700 bg-gradient-to-br">
                <div className="mx-auto max-w-7xl px-4 py-12 md:py-16">
                    <h1 className="text-3xl font-extrabold text-white md:text-4xl">Paket Layanan</h1>
                    <p className="text-brand-100 mt-3 max-w-prose">
                        Estimasi waktu dan biaya di bawah dihitung dari data bengkel. Biaya akhir dapat berubah bila
                        ditemukan pekerjaan tambahan — perubahannya selalu dikonfirmasi lebih dulu kepada Anda.
                    </p>
                    <div className="mt-6">
                        <BookingCta />
                    </div>
                </div>
            </section>

            {kosong ? (
                <div className="mx-auto max-w-7xl px-4 py-16">
                    <EmptyState
                        icon={Wrench}
                        title="Paket layanan belum tersedia"
                        description="Daftar layanan sedang disiapkan. Silakan hubungi kami lewat WhatsApp untuk menanyakan jenis perawatan yang Anda butuhkan."
                        action={
                            <Link
                                href={route('public.contact.show')}
                                className="bg-brand-700 rounded-btn hover:bg-brand-600 inline-flex min-h-11 items-center px-5 py-3 text-sm font-semibold text-white transition"
                            >
                                Hubungi Kami
                            </Link>
                        }
                    />
                </div>
            ) : (
                groups.map((group, indeks) => (
                    <SectionRoot
                        key={group.label}
                        title={group.label}
                        tone={indeks % 2 === 0 ? 'surface' : 'canvas'}
                    >
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {group.packages.map((servicePackage) => (
                                <ServicePackageCard
                                    key={servicePackage.id}
                                    servicePackage={servicePackage}
                                    showCategory={false}
                                />
                            ))}
                        </div>
                    </SectionRoot>
                ))
            )}

            <SectionRoot
                eyebrow="Cara Booking"
                title="Cara memesan jadwal servis"
                tone={groups.length % 2 === 0 ? 'surface' : 'canvas'}
            >
                <BookingSteps />

                <div className="mt-8 flex justify-center">
                    <BookingCta variant="brand" label="Mulai Booking Sekarang" />
                </div>
            </SectionRoot>

            {faqs.length > 0 && (
                <SectionRoot
                    eyebrow="Pertanyaan Umum"
                    title="Sebelum Anda memesan"
                    tone="canvas"
                    action={
                        <Link
                            href={route('public.faq')}
                            className="text-brand-700 rounded-btn hover:bg-brand-50 focus-visible:ring-brand-600 inline-flex min-h-11 items-center px-3 py-2 text-sm font-semibold transition focus-visible:ring-2"
                        >
                            Semua pertanyaan
                        </Link>
                    }
                >
                    <FaqAccordion faqs={faqs} defaultOpen={0} />
                </SectionRoot>
            )}
        </PublicLayout>
    );
}
