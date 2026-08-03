import Lightbox, { type LightboxItem } from '@/components/lightbox';
import BookingCta from '@/components/public/booking-cta';
import CarModelCard from '@/components/public/car-model-card';
import SectionRoot from '@/components/public/section';
import ServicePackageCard from '@/components/public/service-package-card';
import Seo from '@/components/seo';
import PublicLayout from '@/layouts/public-layout';
import { formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
    type CarModelCard as CarModelCardData,
    type PublicCarModel,
    type PublicCarModelImage,
    type PublicCarModelVariant,
    type PublicServicePackage,
} from '@/types';
import { Link } from '@inertiajs/react';
import { Car, ChevronRight, Download } from 'lucide-react';
import { useState } from 'react';

interface Props {
    carModel: PublicCarModel;
    images: PublicCarModelImage[];
    variants: PublicCarModelVariant[];
    servicePackages: PublicServicePackage[];
    relatedModels: CarModelCardData[];
}

/** Tabel spesifikasi dari objek `{kunci: nilai}` yang disimpan server. */
function SpecTable({ specs }: { specs: Record<string, string> }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <tbody className="divide-line divide-y">
                    {Object.entries(specs).map(([kunci, nilai]) => (
                        <tr key={kunci}>
                            <th scope="row" className="text-ink-soft w-2/5 py-3 pr-4 text-left font-medium capitalize">
                                {kunci}
                            </th>
                            <td className="text-ink py-3 font-medium">{nilai}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default function KatalogShow({ carModel, images, variants, servicePackages, relatedModels }: Props) {
    const [aktif, setAktif] = useState(0);
    const [lightbox, setLightbox] = useState<number | null>(null);

    const items: LightboxItem[] = images.map((image) => ({
        url: image.url,
        alt: image.alt,
        caption: `${carModel.name} — ${image.alt}`,
    }));

    const utama = images[aktif];

    return (
        // Pesan WhatsApp menyebut model yang sedang dilihat, agar advisor tahu
        // konteksnya tanpa harus bertanya lebih dulu.
        <PublicLayout waMessage={`Halo Chery Arta, saya ingin bertanya tentang ${carModel.name}.`}>
            <Seo
                title={`${carModel.name} — Katalog`}
                description={carModel.short_description}
                image={images[0]?.url ?? null}
                type="article"
            />

            <div className="bg-canvas">
                <nav aria-label="Remah roti" className="mx-auto max-w-7xl px-4 pt-6">
                    <ol className="text-ink-soft flex flex-wrap items-center gap-1 text-sm">
                        <li>
                            <Link href={route('home')} className="hover:text-brand-700">
                                Beranda
                            </Link>
                        </li>
                        <ChevronRight className="h-4 w-4" aria-hidden="true" />
                        <li>
                            <Link href={route('public.catalog.index')} className="hover:text-brand-700">
                                Katalog
                            </Link>
                        </li>
                        <ChevronRight className="h-4 w-4" aria-hidden="true" />
                        <li className="text-ink font-medium" aria-current="page">
                            {carModel.name}
                        </li>
                    </ol>
                </nav>

                <div className="mx-auto grid max-w-7xl gap-8 px-4 py-8 lg:grid-cols-2 lg:py-12">
                    {/* --- Galeri ------------------------------------------- */}
                    <div>
                        <div className="bg-surface rounded-card shadow-card aspect-[4/3] w-full overflow-hidden">
                            {utama ? (
                                <button
                                    type="button"
                                    onClick={() => setLightbox(aktif)}
                                    aria-label={`Perbesar foto ${utama.alt}`}
                                    className="focus-visible:ring-brand-600 h-full w-full focus-visible:ring-2 focus-visible:ring-inset"
                                >
                                    <img
                                        src={utama.url}
                                        alt={utama.alt}
                                        className="h-full w-full object-cover"
                                    />
                                </button>
                            ) : (
                                <div className="text-ink-muted flex h-full w-full items-center justify-center">
                                    <Car className="h-12 w-12" aria-hidden="true" />
                                </div>
                            )}
                        </div>

                        {images.length > 1 && (
                            <ul className="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-5">
                                {images.map((image, indeks) => (
                                    <li key={image.id}>
                                        <button
                                            type="button"
                                            onClick={() => setAktif(indeks)}
                                            aria-label={`Tampilkan ${image.alt}`}
                                            aria-current={indeks === aktif}
                                            className={cn(
                                                'rounded-btn focus-visible:ring-brand-600 aspect-[4/3] w-full overflow-hidden border-2 transition focus-visible:ring-2',
                                                indeks === aktif ? 'border-brand-700' : 'border-transparent',
                                            )}
                                        >
                                            <img
                                                src={image.thumbnail_url}
                                                alt={image.alt}
                                                loading="lazy"
                                                className="h-full w-full object-cover"
                                            />
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    {/* --- Ringkasan ---------------------------------------- */}
                    <div>
                        <div className="text-ink-muted mb-3 flex flex-wrap gap-2 text-xs font-medium">
                            <span className="bg-brand-50 text-brand-700 rounded-full px-2.5 py-1">
                                {carModel.category_label}
                            </span>
                            <span className="bg-surface rounded-full px-2.5 py-1">{carModel.fuel_type_label}</span>
                        </div>

                        <h1 className="text-ink text-3xl font-extrabold md:text-4xl">{carModel.name}</h1>

                        <p className="text-ink-soft mt-3 max-w-prose">{carModel.short_description}</p>

                        <p className="text-brand-700 mt-6 text-2xl font-bold">
                            {carModel.price_start
                                ? `Mulai ${formatRupiah(carModel.price_start)}`
                                : 'Hubungi kami untuk harga'}
                        </p>

                        <div className="mt-6 flex flex-wrap gap-3">
                            <BookingCta label="Booking Servis" />

                            {carModel.brochure_url && (
                                <a
                                    href={carModel.brochure_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="border-line rounded-btn text-ink hover:bg-canvas focus-visible:ring-brand-600 inline-flex min-h-11 items-center gap-2 border px-6 py-3 text-sm font-semibold transition focus-visible:ring-2 focus-visible:ring-offset-2"
                                >
                                    <Download className="h-4 w-4" aria-hidden="true" />
                                    Unduh Brosur
                                </a>
                            )}
                        </div>

                        {carModel.description && (
                            <div className="border-line mt-8 border-t pt-6">
                                <h2 className="text-ink text-lg font-semibold">Tentang {carModel.name}</h2>
                                <p className="text-ink-soft mt-2 max-w-prose whitespace-pre-line">
                                    {carModel.description}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* --- Varian ---------------------------------------------------- */}
            {variants.length > 0 && (
                <SectionRoot eyebrow="Varian" title={`Pilihan varian ${carModel.name}`}>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {variants.map((variant) => (
                            <article key={variant.id} className="bg-surface rounded-card shadow-card p-6">
                                <h3 className="text-ink text-lg font-semibold">{variant.name}</h3>
                                <p className="text-brand-700 mt-1 font-semibold">
                                    {variant.price ? formatRupiah(variant.price) : 'Hubungi kami'}
                                </p>

                                {variant.specs && Object.keys(variant.specs).length > 0 && (
                                    <div className="border-line mt-4 border-t pt-4">
                                        <SpecTable specs={variant.specs} />
                                    </div>
                                )}
                            </article>
                        ))}
                    </div>
                </SectionRoot>
            )}

            {/* --- Spesifikasi ----------------------------------------------- */}
            {carModel.specs && Object.keys(carModel.specs).length > 0 && (
                <SectionRoot eyebrow="Spesifikasi" title="Spesifikasi utama" tone="canvas">
                    <div className="bg-surface rounded-card shadow-card max-w-3xl p-6">
                        <SpecTable specs={carModel.specs} />
                    </div>
                </SectionRoot>
            )}

            {/* --- Layanan --------------------------------------------------- */}
            {servicePackages.length > 0 && (
                <SectionRoot
                    eyebrow="Layanan"
                    title="Paket perawatan yang tersedia"
                    description="Estimasi waktu dan biaya dihitung dari data bengkel. Pilih paketnya saat membuat booking."
                    action={
                        <Link
                            href={route('public.services')}
                            className="text-brand-700 rounded-btn hover:bg-brand-50 focus-visible:ring-brand-600 inline-flex min-h-11 items-center px-3 py-2 text-sm font-semibold transition focus-visible:ring-2"
                        >
                            Semua layanan
                        </Link>
                    }
                >
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {servicePackages.slice(0, 3).map((servicePackage) => (
                            <ServicePackageCard key={servicePackage.id} servicePackage={servicePackage} />
                        ))}
                    </div>
                </SectionRoot>
            )}

            {/* --- Model lain ------------------------------------------------ */}
            {relatedModels.length > 0 && (
                <SectionRoot eyebrow="Model Lain" title="Mungkin Anda juga mencari" tone="canvas">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {relatedModels.map((related) => (
                            <CarModelCard key={related.slug} carModel={related} />
                        ))}
                    </div>
                </SectionRoot>
            )}

            <Lightbox items={items} index={lightbox} onClose={() => setLightbox(null)} onIndexChange={setLightbox} />
        </PublicLayout>
    );
}
