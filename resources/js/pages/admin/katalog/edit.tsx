import CarModelForm, { type CarModelFormData } from '@/components/admin/car-model-form';
import GalleryManager from '@/components/admin/gallery-manager';
import VariantManager from '@/components/admin/variant-manager';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { type CarModelDetail, type CarModelImage, type CarModelVariant, type SelectOption, type SpecPair } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface Props {
    carModel: CarModelDetail;
    variants: CarModelVariant[];
    images: CarModelImage[];
    categories: SelectOption[];
    fuelTypes: SelectOption[];
    seriesOptions: string[];
}

/** Objek spesifikasi tersimpan → baris yang bisa disunting. */
function keBaris(specs: Record<string, string> | null): SpecPair[] {
    return Object.entries(specs ?? {}).map(([key, value]) => ({ key, value }));
}

export default function CarModelEdit({ carModel, variants, images, categories, fuelTypes, seriesOptions }: Props) {
    const { data, setData, post, processing, errors } = useForm<CarModelFormData>({
        name: carModel.name,
        slug: carModel.slug,
        category: carModel.category,
        fuel_type: carModel.fuel_type,
        series_code: carModel.series_code ?? '',
        price_start: carModel.price_start === null ? '' : String(carModel.price_start),
        short_description: carModel.short_description,
        description: carModel.description ?? '',
        specs: keBaris(carModel.specs),
        brochure: null,
        is_active: carModel.is_active,
        sort_order: String(carModel.sort_order),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        // Brosur membuat pengirimannya multipart, dan multipart tidak mengenal
        // PUT — Laravel membacanya lewat _method (pola baku Inertia).
        post(route('admin.car-models.update', carModel.id), {
            headers: { 'X-HTTP-Method-Override': 'PUT' },
            forceFormData: true,
        });
    };

    return (
        <AdminLayout
            title={carModel.name}
            description="Data model, varian, dan galeri katalog."
            actions={
                <Button variant="outline" asChild>
                    <Link href={route('admin.car-models.index')}>
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Kembali ke Katalog
                    </Link>
                </Button>
            }
        >
            <Head title={`Ubah ${carModel.name}`} />

            <div className="grid gap-8">
                <section aria-labelledby="judul-data" className="border-line bg-surface shadow-card rounded-2xl border p-5">
                    <h2 id="judul-data" className="mb-4 text-lg font-semibold">
                        Data Model
                    </h2>

                    <CarModelForm
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        submitLabel="Simpan Perubahan"
                        categories={categories}
                        fuelTypes={fuelTypes}
                        seriesOptions={seriesOptions}
                        brochureUrl={carModel.brochure_url}
                        onDeleteBrochure={() => router.delete(route('admin.car-models.brochure.destroy', carModel.id), { preserveScroll: true })}
                    />
                </section>

                <VariantManager carModelId={carModel.id} variants={variants} />

                <GalleryManager carModelId={carModel.id} images={images} />
            </div>
        </AdminLayout>
    );
}
