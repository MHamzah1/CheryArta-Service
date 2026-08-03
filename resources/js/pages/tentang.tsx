import AdvantageGrid from '@/components/public/advantage-grid';
import BookingCta from '@/components/public/booking-cta';
import FacilityGallery from '@/components/public/facility-gallery';
import LocationSection from '@/components/public/location-section';
import SectionRoot from '@/components/public/section';
import TestimonialList from '@/components/public/testimonial-list';
import Seo from '@/components/seo';
import PublicLayout from '@/layouts/public-layout';
import {
    type CompanyAdvantage,
    type PublicFacility,
    type PublicTestimonial,
    type SharedData,
} from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface Props {
    about: string;
    experienceYears: number;
    advantages: CompanyAdvantage[];
    facilities: PublicFacility[];
    testimonials: PublicTestimonial[];
}

export default function Tentang({ about, experienceYears, advantages, facilities, testimonials }: Props) {
    const { company } = usePage<SharedData>().props;

    return (
        <PublicLayout>
            <Seo
                title="Tentang Kami"
                description={about}
            />

            <section className="from-brand-900 to-brand-700 bg-gradient-to-br">
                <div className="mx-auto max-w-7xl px-4 py-12 md:py-16">
                    <h1 className="text-3xl font-extrabold text-white md:text-4xl">Tentang {company.name}</h1>
                    <p className="text-brand-100 mt-4 max-w-prose text-base md:text-lg">{about}</p>
                </div>
            </section>

            <SectionRoot
                eyebrow="Mengapa Kami"
                title={`${experienceYears}+ tahun menangani kendaraan Chery`}
                description="Empat hal yang kami pegang sejak hari pertama, dan tetap kami pegang sampai sekarang."
            >
                <AdvantageGrid advantages={advantages} />
            </SectionRoot>

            {facilities.length > 0 && (
                <SectionRoot
                    eyebrow="Fasilitas"
                    title="Tempat Anda menunggu"
                    tone="canvas"
                    action={
                        <Link
                            href={route('public.facilities')}
                            className="text-brand-700 rounded-btn hover:bg-brand-50 focus-visible:ring-brand-600 inline-flex min-h-11 items-center px-3 py-2 text-sm font-semibold transition focus-visible:ring-2"
                        >
                            Semua fasilitas
                        </Link>
                    }
                >
                    <FacilityGallery facilities={facilities} />
                </SectionRoot>
            )}

            {testimonials.length > 0 && (
                <SectionRoot eyebrow="Testimoni" title="Kata pelanggan kami">
                    <TestimonialList testimonials={testimonials} />
                </SectionRoot>
            )}

            <SectionRoot
                eyebrow="Lokasi & Jam"
                title="Kunjungi bengkel kami"
                description={company.address.full}
                tone="canvas"
            >
                <LocationSection />

                <div className="mt-8 flex justify-center">
                    <BookingCta variant="brand" label="Booking Servis Sekarang" />
                </div>
            </SectionRoot>
        </PublicLayout>
    );
}
