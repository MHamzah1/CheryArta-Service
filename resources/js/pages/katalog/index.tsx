import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import BookingCta from '@/components/public/booking-cta';
import CarModelCard from '@/components/public/car-model-card';
import Seo from '@/components/seo';
import PublicLayout from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import {
    type CarModelCard as CarModelCardData,
    type CatalogFilters,
    type Paginated,
    type SelectOption,
} from '@/types';
import { router } from '@inertiajs/react';
import { Car } from 'lucide-react';

interface Props {
    carModels: Paginated<CarModelCardData>;
    categories: SelectOption[];
    fuelTypes: SelectOption[];
    filters: CatalogFilters;
}

interface FilterBarProps {
    label: string;
    options: SelectOption[];
    active: string | null;
    onChange: (value: string | null) => void;
}

/** Satu baris saringan. Pilihan aktif ditandai warna DAN `aria-pressed`. */
function FilterBar({ label, options, active, onChange }: FilterBarProps) {
    const semua = [{ value: '', label: 'Semua' }, ...options];

    return (
        <div className="flex flex-wrap items-center gap-2">
            <span className="text-ink-soft w-full text-sm font-medium sm:w-auto">{label}</span>

            {semua.map((option) => {
                const dipilih = (option.value === '' && active === null) || option.value === active;

                return (
                    <button
                        key={option.value || 'semua'}
                        type="button"
                        aria-pressed={dipilih}
                        onClick={() => onChange(option.value === '' ? null : option.value)}
                        className={cn(
                            'rounded-btn focus-visible:ring-brand-600 min-h-11 border px-4 py-2 text-sm font-medium transition focus-visible:ring-2',
                            dipilih
                                ? 'border-brand-700 bg-brand-700 text-white'
                                : 'border-line bg-surface text-ink-soft hover:border-brand-200 hover:text-brand-700',
                        )}
                    >
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}

export default function KatalogIndex({ carModels, categories, fuelTypes, filters }: Props) {
    const terapkan = (perubahan: Partial<CatalogFilters>) => {
        const berikutnya = { ...filters, ...perubahan };

        // Saringan kosong dibuang dari URL supaya `?kategori=` tidak ikut
        // tersalin saat orang membagikan tautan katalog.
        const params: Record<string, string> = {};
        if (berikutnya.kategori) params.kategori = berikutnya.kategori;
        if (berikutnya.bahan_bakar) params.bahan_bakar = berikutnya.bahan_bakar;

        router.get(route('public.catalog.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const adaSaringan = filters.kategori !== null || filters.bahan_bakar !== null;

    return (
        <PublicLayout>
            <Seo
                title="Katalog Mobil Chery"
                description="Daftar model Chery yang kami layani — SUV, sedan, MPV, hybrid, dan listrik. Lihat varian, spesifikasi, dan pesan jadwal servisnya."
            />

            <section className="from-brand-900 to-brand-700 bg-gradient-to-br">
                <div className="mx-auto max-w-7xl px-4 py-12 md:py-16">
                    <h1 className="text-3xl font-extrabold text-white md:text-4xl">Katalog Mobil</h1>
                    <p className="text-brand-100 mt-3 max-w-prose">
                        Model Chery yang kami layani di bengkel resmi Bekasi. Pilih model untuk melihat varian,
                        spesifikasi, dan galeri fotonya.
                    </p>
                </div>
            </section>

            <div className="mx-auto max-w-7xl px-4 py-10 md:py-16">
                <div className="bg-surface rounded-card shadow-card mb-8 space-y-4 p-5">
                    <FilterBar
                        label="Kategori"
                        options={categories}
                        active={filters.kategori}
                        onChange={(value) => terapkan({ kategori: value })}
                    />
                    <FilterBar
                        label="Bahan bakar"
                        options={fuelTypes}
                        active={filters.bahan_bakar}
                        onChange={(value) => terapkan({ bahan_bakar: value })}
                    />
                </div>

                {carModels.data.length === 0 ? (
                    <EmptyState
                        icon={Car}
                        title={adaSaringan ? 'Tidak ada model yang cocok' : 'Katalog belum diisi'}
                        description={
                            adaSaringan
                                ? 'Coba longgarkan saringan kategori atau bahan bakarnya.'
                                : 'Daftar model sedang disiapkan. Servis untuk seluruh kendaraan Chery tetap dapat dipesan lewat form booking.'
                        }
                        action={
                            adaSaringan ? (
                                <button
                                    type="button"
                                    onClick={() => terapkan({ kategori: null, bahan_bakar: null })}
                                    className="bg-brand-700 rounded-btn hover:bg-brand-600 focus-visible:ring-brand-600 min-h-11 px-5 py-3 text-sm font-semibold text-white transition focus-visible:ring-2 focus-visible:ring-offset-2"
                                >
                                    Hapus Saringan
                                </button>
                            ) : (
                                <BookingCta variant="brand" />
                            )
                        }
                    />
                ) : (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {carModels.data.map((carModel, indeks) => (
                                <CarModelCard key={carModel.slug} carModel={carModel} eager={indeks < 4} />
                            ))}
                        </div>

                        <Pagination meta={carModels} />
                    </>
                )}
            </div>
        </PublicLayout>
    );
}
