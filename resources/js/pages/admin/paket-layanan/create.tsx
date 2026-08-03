import ServicePackageForm, { type ServicePackageFormData } from '@/components/admin/service-package-form';
import AdminLayout from '@/layouts/admin-layout';
import { type SelectOption } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface Props {
    categories: SelectOption[];
    seriesOptions: string[];
}

export default function ServicePackageCreate({ categories, seriesOptions }: Props) {
    const { data, setData, post, processing, errors } = useForm<ServicePackageFormData>({
        code: '',
        name: '',
        category: '',
        description: '',
        applicable_series: [],
        estimated_duration_minutes: '60',
        price: '',
        is_free: true,
        is_active: true,
        sort_order: '0',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.service-packages.store'));
    };

    return (
        <AdminLayout title="Tambah Paket Layanan" description="Paket baru langsung tersedia sebagai pilihan di form booking.">
            <Head title="Tambah Paket Layanan" />

            <ServicePackageForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={submit}
                submitLabel="Simpan Paket"
                categories={categories}
                seriesOptions={seriesOptions}
            />
        </AdminLayout>
    );
}
