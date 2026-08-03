import EmptyState from '@/components/empty-state';
import AdvantageGrid from '@/components/public/advantage-grid';
import BookingCta from '@/components/public/booking-cta';
import BookingSteps from '@/components/public/booking-steps';
import CarModelCard from '@/components/public/car-model-card';
import FacilityGallery from '@/components/public/facility-gallery';
import FaqAccordion from '@/components/public/faq-accordion';
import LocationSection from '@/components/public/location-section';
import SectionRoot from '@/components/public/section';
import ServicePackageCard from '@/components/public/service-package-card';
import SlotPreviewCard from '@/components/public/slot-preview-card';
import TestimonialList from '@/components/public/testimonial-list';
import Seo from '@/components/seo';
import PublicLayout from '@/layouts/public-layout';
import {
    type CarModelCard as CarModelCardData,
    type CompanyAdvantage,
    type PublicFacility,
    type PublicFaq,
    type PublicServicePackage,
    type PublicTestimonial,
    type SharedData,
    type SlotPreview,
} from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, Search } from 'lucide-react';

interface Props {
    advantages: CompanyAdvantage[];
    servicePackages: PublicServicePackage[];
    carModels: CarModelCardData[];
    facilities: PublicFacility[];
    testimonials: PublicTestimonial[];
    faqs: PublicFaq[];
    slotPreview: SlotPreview;
}

/** Tautan "lihat semua" di sisi kanan judul seksi. */
function LihatSemua({ href, children }: { href: string; children: string }) {
    return (
        <Link
            href={href}
            className="text-brand-700 rounded-btn hover:bg-brand-50 focus-visible:ring-brand-600 inline-flex min-h-11 items-center gap-1.5 px-3 py-2 text-sm font-semibold transition focus-visible:ring-2"
        >
            {children}
            <ArrowRight className="h-4 w-4" aria-hidden="true" />
        </Link>
    );
}

export default function Welcome({
    advantages,
    servicePackages,
    carModels,
    facilities,
    testimonials,
    faqs,
    slotPreview,
}: Props) {
    const { company } = usePage<SharedData>().props;

    return (
        <PublicLayout transparentHeader>
            <Seo
                title="Servis Chery Resmi di Bekasi"
                description={`${company.tagline} Booking servis Chery online di ${company.address.city} — pilih jadwal, pantau status pengerjaan, tanpa perlu antre.`}
            />

            {/* --- Hero ------------------------------------------------------ */}
            <section className="from-brand-900 to-brand-700 bg-gradient-to-br">
                <div className="mx-auto max-w-7xl px-4 pt-28 pb-16 md:pt-40 md:pb-24">
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
                                <BookingCta />
                                <Link
                                    href={route('public.catalog.index')}
                                    className="rounded-btn focus-visible:ring-brand-600 inline-flex min-h-11 items-center px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-offset-2"
                                >
                                    Lihat Katalog
                                </Link>
                                <Link
                                    href={route('public.tracking')}
                                    className="rounded-btn focus-visible:ring-brand-600 inline-flex min-h-11 items-center gap-2 border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-offset-2"
                                >
                                    <Search className="h-4 w-4" aria-hidden="true" />
                                    Cek Servis
                                </Link>
                            </div>
                        </div>

                        <SlotPreviewCard preview={slotPreview} />
                    </div>
                </div>
            </section>

            {/* --- 4 keunggulan ---------------------------------------------- */}
            <section className="bg-canvas py-12 md:py-16">
                <div className="mx-auto max-w-7xl px-4">
                    <AdvantageGrid advantages={advantages} />
                </div>
            </section>

            {/* --- Layanan --------------------------------------------------- */}
            <SectionRoot
                id="layanan"
                eyebrow="Layanan Kami"
                title="Perawatan berkala oleh teknisi bersertifikat"
                description="Paket layanan resmi Chery beserta estimasi waktu pengerjaannya. Biaya dan durasi dihitung dari data bengkel, bukan perkiraan."
                action={<LihatSemua href={route('public.services')}>Semua layanan</LihatSemua>}
            >
                {servicePackages.length === 0 ? (
                    <EmptyState
                        title="Paket layanan belum tersedia"
                        description="Daftar layanan sedang disiapkan. Silakan hubungi kami lewat WhatsApp untuk menanyakan jenis perawatan yang Anda butuhkan."
                    />
                ) : (
                    <div className="grid gap-4 md:grid-cols-3">
                        {servicePackages.map((servicePackage) => (
                            <ServicePackageCard key={servicePackage.id} servicePackage={servicePackage} />
                        ))}
                    </div>
                )}
            </SectionRoot>

            {/* --- Katalog ringkas ------------------------------------------- */}
            <SectionRoot
                id="katalog"
                eyebrow="Katalog Mobil"
                title="Model Chery yang kami layani"
                tone="canvas"
                action={<LihatSemua href={route('public.catalog.index')}>Lihat semua</LihatSemua>}
            >
                {carModels.length === 0 ? (
                    <EmptyState
                        title="Katalog belum diisi"
                        description="Daftar model sedang disiapkan. Servis untuk seluruh kendaraan Chery tetap dapat dipesan lewat form booking."
                        action={<BookingCta variant="brand" />}
                    />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {carModels.map((carModel) => (
                            <CarModelCard key={carModel.slug} carModel={carModel} />
                        ))}
                    </div>
                )}
            </SectionRoot>

            {/* --- Cara booking ---------------------------------------------- */}
            <SectionRoot
                id="cara-booking"
                eyebrow="Cara Booking"
                title="Tiga langkah, selesai dalam hitungan menit"
            >
                <BookingSteps />

                <div className="mt-8 flex justify-center">
                    <BookingCta variant="brand" label="Mulai Booking Sekarang" />
                </div>
            </SectionRoot>

            {/* --- Fasilitas -------------------------------------------------- */}
            {facilities.length > 0 && (
                <SectionRoot
                    id="fasilitas"
                    eyebrow="Fasilitas"
                    title="Ruang tunggu yang membuat waktu servis terasa singkat"
                    tone="canvas"
                    action={<LihatSemua href={route('public.facilities')}>Semua fasilitas</LihatSemua>}
                >
                    <FacilityGallery facilities={facilities} />
                </SectionRoot>
            )}

            {/* --- Testimoni --------------------------------------------------- */}
            {testimonials.length > 0 && (
                <SectionRoot id="testimoni" eyebrow="Testimoni" title="Kata pelanggan kami">
                    <TestimonialList testimonials={testimonials} />
                </SectionRoot>
            )}

            {/* --- FAQ --------------------------------------------------------- */}
            {faqs.length > 0 && (
                <SectionRoot
                    id="faq"
                    eyebrow="Pertanyaan Umum"
                    title="Hal yang paling sering ditanyakan"
                    tone="canvas"
                    action={<LihatSemua href={route('public.faq')}>Semua pertanyaan</LihatSemua>}
                >
                    <FaqAccordion faqs={faqs} defaultOpen={0} />
                </SectionRoot>
            )}

            {/* --- Lokasi ------------------------------------------------------ */}
            <SectionRoot
                id="lokasi"
                eyebrow="Lokasi & Jam"
                title="Datang langsung ke bengkel kami"
                description={company.address.full}
            >
                <LocationSection />
            </SectionRoot>
        </PublicLayout>
    );
}
