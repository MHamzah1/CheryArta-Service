import VehicleForm, { type VehicleFormData } from '@/components/vehicle/vehicle-form';
import CustomerLayout from '@/layouts/customer-layout';
import { type CarModelOption } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface Props {
    carModels: CarModelOption[];
}

export default function VehicleCreate({ carModels }: Props) {
    const { data, setData, post, processing, errors } = useForm<VehicleFormData>({
        car_model_id: '',
        model_name_manual: '',
        plate_prefix: '',
        plate_number: '',
        plate_suffix: '',
        year: '',
        color: '',
        vin: '',
        is_primary: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('customer.vehicles.store'));
    };

    return (
        <CustomerLayout title="Tambah Kendaraan" description="Simpan data kendaraan agar tidak perlu diketik ulang setiap kali memesan servis.">
            <Head title="Tambah Kendaraan" />

            <VehicleForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={submit}
                submitLabel="Simpan Kendaraan"
                carModels={carModels}
                showPrimaryToggle
            />
        </CustomerLayout>
    );
}
