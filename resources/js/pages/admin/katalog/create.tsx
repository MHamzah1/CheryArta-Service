import CarModelForm, { type CarModelFormData } from '@/components/admin/car-model-form';
import AdminLayout from '@/layouts/admin-layout';
import { type SelectOption } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface Props {
    categories: SelectOption[];
    fuelTypes: SelectOption[];
    seriesOptions: string[];
}

export default function CarModelCreate({ categories, fuelTypes, seriesOptions }: Props) {
    const { data, setData, post, processing, errors } = useForm<CarModelFormData>({
        name: '',
        slug: '',
        category: '',
        fuel_type: '',
        series_code: '',
        price_start: '',
        short_description: '',
        description: '',
        specs: [],
        brochure: null,
        is_active: true,
        sort_order: '0',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.car-models.store'));
    };

    return (
        <AdminLayout title="Tambah Model" description="Simpan data dasarnya dulu — varian dan galeri bisa diisi setelah modelnya tersimpan.">
            <Head title="Tambah Model" />

            <CarModelForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={submit}
                submitLabel="Simpan Model"
                categories={categories}
                fuelTypes={fuelTypes}
                seriesOptions={seriesOptions}
            />
        </AdminLayout>
    );
}
