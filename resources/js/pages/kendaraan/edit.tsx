import VehicleForm, { type VehicleFormData } from '@/components/vehicle/vehicle-form';
import CustomerLayout from '@/layouts/customer-layout';
import { type CarModelOption, type Vehicle } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface Props {
    vehicle: Vehicle;
    carModels: CarModelOption[];
}

export default function VehicleEdit({ vehicle, carModels }: Props) {
    const { data, setData, put, processing, errors } = useForm<VehicleFormData>({
        car_model_id: vehicle.car_model_id === null ? '' : String(vehicle.car_model_id),
        model_name_manual: vehicle.model_name_manual ?? '',
        plate_prefix: vehicle.plate_prefix,
        plate_number: vehicle.plate_number,
        plate_suffix: vehicle.plate_suffix,
        year: vehicle.year === null ? '' : String(vehicle.year),
        color: vehicle.color ?? '',
        vin: vehicle.vin ?? '',
        is_primary: vehicle.is_primary,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('customer.vehicles.update', vehicle.id));
    };

    return (
        <CustomerLayout title="Ubah Kendaraan" description="Perbarui data kendaraan Anda.">
            <Head title="Ubah Kendaraan" />

            <VehicleForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={submit}
                submitLabel="Simpan Perubahan"
                carModels={carModels}
                showPrimaryToggle={!vehicle.is_primary}
            />
        </CustomerLayout>
    );
}
