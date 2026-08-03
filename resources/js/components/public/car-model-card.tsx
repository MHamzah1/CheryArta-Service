import { formatRupiah } from '@/lib/format';
import { type CarModelCard as CarModelCardData } from '@/types';
import { Link } from '@inertiajs/react';
import { Car } from 'lucide-react';

interface Props {
    carModel: CarModelCardData;
    /** Gambar hero halaman tidak boleh malas dimuat; kartu katalog boleh. */
    eager?: boolean;
}

/**
 * Kartu satu model mobil — dipakai beranda, `/katalog`, dan daftar "model
 * lain" di halaman detail. Satu bentuk untuk ketiganya (docs/06 §6.5).
 */
export default function CarModelCard({ carModel, eager = false }: Props) {
    return (
        <Link
            href={route('public.catalog.show', carModel.slug)}
            className="bg-surface rounded-card shadow-card focus-visible:ring-brand-600 group flex flex-col overflow-hidden transition hover:-translate-y-1 focus-visible:ring-2 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0"
        >
            <div className="bg-canvas aspect-[4/3] w-full overflow-hidden">
                {carModel.thumbnail_url ? (
                    <img
                        src={carModel.thumbnail_url}
                        alt={carModel.thumbnail_alt ?? carModel.name}
                        loading={eager ? 'eager' : 'lazy'}
                        className="h-full w-full object-cover transition duration-300 group-hover:scale-105 motion-reduce:group-hover:scale-100"
                    />
                ) : (
                    <div className="text-ink-muted flex h-full w-full items-center justify-center">
                        <Car className="h-10 w-10" aria-hidden="true" />
                    </div>
                )}
            </div>

            <div className="flex flex-1 flex-col p-5">
                <div className="text-ink-muted mb-2 flex flex-wrap gap-2 text-xs font-medium">
                    <span className="bg-brand-50 text-brand-700 rounded-full px-2.5 py-1">{carModel.category_label}</span>
                    <span className="bg-canvas rounded-full px-2.5 py-1">{carModel.fuel_type_label}</span>
                </div>

                <h3 className="text-ink group-hover:text-brand-700 text-lg font-semibold transition">{carModel.name}</h3>

                <p className="text-ink-soft mt-1 line-clamp-2 text-sm">{carModel.short_description}</p>

                <p className="text-brand-700 mt-4 text-sm font-semibold">
                    {carModel.price_start ? `Mulai ${formatRupiah(carModel.price_start)}` : 'Hubungi kami untuk harga'}
                </p>
            </div>
        </Link>
    );
}
