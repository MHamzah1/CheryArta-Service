import ServicePackageForm, { type ServicePackageFormData } from '@/components/admin/service-package-form';
import AdminLayout from '@/layouts/admin-layout';
import { type SelectOption, type ServicePackage } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface Props {
    package: ServicePackage;
    categories: SelectOption[];
    seriesOptions: string[];
}

export default function ServicePackageEdit({ package: paket, categories, seriesOptions }: Props) {
    const { data, setData, put, processing, errors } = useForm<ServicePackageFormData>({
        code: paket.code,
        name: paket.name,
        category: paket.category,
        description: paket.description ?? '',
        applicable_series: paket.applicable_series ?? [],
        estimated_duration_minutes: String(paket.estimated_duration_minutes),
        price: paket.is_free ? '' : String(paket.price),
        is_free: paket.is_free,
        is_active: paket.is_active,
        sort_order: String(paket.sort_order),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.service-packages.update', paket.id));
    };

    return (
        <AdminLayout title="Ubah Paket Layanan" description={paket.name}>
            <Head title={`Ubah ${paket.name}`} />

            <ServicePackageForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={submit}
                submitLabel="Simpan Perubahan"
                categories={categories}
                seriesOptions={seriesOptions}
                codeLocked
            />
        </AdminLayout>
    );
}
