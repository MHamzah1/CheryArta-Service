import EmptyState from '@/components/empty-state';
import BookingCta from '@/components/public/booking-cta';
import FacilityGallery from '@/components/public/facility-gallery';
import Seo from '@/components/seo';
import PublicLayout from '@/layouts/public-layout';
import { type PublicFacility } from '@/types';
import { Building2 } from 'lucide-react';

interface Props {
    facilities: PublicFacility[];
}

export default function Fasilitas({ facilities }: Props) {
    return (
        <PublicLayout>
            <Seo
                title="Fasilitas Bengkel"
                description="Ruang tunggu premium, area bermain anak, showroom, dan bengkel dengan peralatan modern — fasilitas yang menemani Anda selama kendaraan dikerjakan."
            />

            <section className="from-brand-900 to-brand-700 bg-gradient-to-br">
                <div className="mx-auto max-w-7xl px-4 py-12 md:py-16">
                    <h1 className="text-3xl font-extrabold text-white md:text-4xl">Fasilitas</h1>
                    <p className="text-brand-100 mt-3 max-w-prose">
                        Menunggu servis tidak harus membosankan. Klik foto untuk melihatnya lebih besar.
                    </p>
                </div>
            </section>

            <div className="mx-auto max-w-7xl px-4 py-12 md:py-16">
                {facilities.length === 0 ? (
                    <EmptyState
                        icon={Building2}
                        title="Foto fasilitas belum tersedia"
                        description="Galeri sedang disiapkan. Anda tetap dipersilakan berkunjung — alamat dan jam operasional ada di bagian bawah halaman."
                        action={<BookingCta variant="brand" />}
                    />
                ) : (
                    <FacilityGallery facilities={facilities} />
                )}
            </div>
        </PublicLayout>
    );
}
