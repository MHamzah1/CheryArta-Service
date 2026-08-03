import Lightbox, { type LightboxItem } from '@/components/lightbox';
import { type PublicFacility } from '@/types';
import { ImageOff } from 'lucide-react';
import { useState } from 'react';

interface Props {
    facilities: PublicFacility[];
}

/**
 * Galeri fasilitas dengan lightbox (docs/10 F1.6.3).
 *
 * Fasilitas yang fotonya belum diunggah tetap tampil sebagai kartu teks —
 * seeder memang belum membawa gambar (lihat FacilitySeeder), dan kartu yang
 * hilang membuat daftar terlihat kurang tanpa penjelasan. Kartu tanpa foto
 * bukan tombol, karena tidak ada yang bisa dibuka.
 */
export default function FacilityGallery({ facilities }: Props) {
    const [terbuka, setTerbuka] = useState<number | null>(null);

    const berfoto = facilities.filter((facility): facility is PublicFacility & { image_url: string } =>
        Boolean(facility.image_url),
    );

    const items: LightboxItem[] = berfoto.map((facility) => ({
        url: facility.image_url,
        alt: facility.title,
        caption: facility.title,
    }));

    return (
        <>
            <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {facilities.map((facility) => {
                    const indeks = berfoto.findIndex((item) => item.id === facility.id);

                    const isi = (
                        <>
                            <div className="bg-canvas aspect-[4/3] w-full overflow-hidden">
                                {facility.thumbnail_url ? (
                                    <img
                                        src={facility.thumbnail_url}
                                        alt={facility.title}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition duration-300 group-hover:scale-105 motion-reduce:group-hover:scale-100"
                                    />
                                ) : (
                                    <div className="text-ink-muted flex h-full w-full items-center justify-center">
                                        <ImageOff className="h-8 w-8" aria-hidden="true" />
                                    </div>
                                )}
                            </div>

                            <div className="p-4 text-left">
                                <h3 className="text-ink text-base font-semibold">{facility.title}</h3>
                                <p className="text-ink-soft mt-1 text-sm">{facility.description}</p>
                            </div>
                        </>
                    );

                    return (
                        <li key={facility.id} className="bg-surface rounded-card shadow-card overflow-hidden">
                            {indeks >= 0 ? (
                                <button
                                    type="button"
                                    onClick={() => setTerbuka(indeks)}
                                    aria-label={`Perbesar foto ${facility.title}`}
                                    className="focus-visible:ring-brand-600 group block w-full focus-visible:ring-2 focus-visible:ring-inset"
                                >
                                    {isi}
                                </button>
                            ) : (
                                <div className="group block w-full">{isi}</div>
                            )}
                        </li>
                    );
                })}
            </ul>

            <Lightbox items={items} index={terbuka} onClose={() => setTerbuka(null)} onIndexChange={setTerbuka} />
        </>
    );
}
