import BookingSteps from '@/components/booking/booking-steps';
import { FIELD_PER_LANGKAH, LANGKAH, type BookingFormData } from '@/components/booking/form-data';
import StepPackage from '@/components/booking/step-package';
import StepReview from '@/components/booking/step-review';
import StepSchedule from '@/components/booking/step-schedule';
import StepVehicle from '@/components/booking/step-vehicle';
import { Button } from '@/components/ui/button';
import CustomerLayout from '@/layouts/customer-layout';
import { type BookingVehicleOption, type ServicePackageOption, type SlotRules } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, LoaderCircle } from 'lucide-react';
import { useState, type FormEventHandler } from 'react';

interface Props {
    vehicles: BookingVehicleOption[];
    servicePackages: ServicePackageOption[];
    slotRules: SlotRules;
}

export default function BookingCreate({ vehicles, servicePackages, slotRules }: Props) {
    const [langkah, setLangkah] = useState(1);

    const { data, setData, post, processing, errors } = useForm<BookingFormData>({
        // Kendaraan utama dipilih lebih dulu: pelanggan dengan satu mobil tidak
        // perlu menyentuh langkah pertama sama sekali.
        vehicle_id: String(vehicles.find((v) => v.is_primary)?.id ?? vehicles[0]?.id ?? ''),
        service_package_id: '',
        booking_date: '',
        booking_time: '',
        odometer: '',
        complaint: '',
    });

    const kendaraan = vehicles.find((v) => String(v.id) === data.vehicle_id);
    const paket = servicePackages.find((p) => String(p.id) === data.service_package_id);

    const bolehLanjut =
        (langkah === 1 && data.vehicle_id !== '') ||
        (langkah === 2 && data.service_package_id !== '') ||
        (langkah === 3 && data.booking_date !== '' && data.booking_time !== '') ||
        langkah === 4;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('customer.booking.store'), {
            // Server bisa menolak kolom yang diisi di langkah sebelumnya —
            // slot yang direbut orang lain, misalnya. Melompat ke langkah yang
            // bermasalah mencegah galat tersembunyi di layar yang tidak dibuka.
            onError: (galat) => {
                const bermasalah = Object.keys(galat);
                const tujuan = Object.entries(FIELD_PER_LANGKAH).find(([, fields]) => fields.some((field) => bermasalah.includes(String(field))));

                if (tujuan) setLangkah(Number(tujuan[0]));
            },
        });
    };

    return (
        <CustomerLayout title="Booking Servis" description="Empat langkah singkat. Jadwal yang penuh terlihat sebelum Anda memilih.">
            <Head title="Booking Servis" />

            <BookingSteps current={langkah} />

            <form onSubmit={submit} className="grid gap-6">
                <div className="border-line bg-surface shadow-card rounded-2xl border p-5">
                    <h2 className="mb-4 text-lg font-semibold">
                        Langkah {langkah} — {LANGKAH[langkah - 1]}
                    </h2>

                    {langkah === 1 && (
                        <StepVehicle
                            vehicles={vehicles}
                            value={data.vehicle_id}
                            onChange={(id) => setData('vehicle_id', id)}
                            error={errors.vehicle_id}
                        />
                    )}

                    {langkah === 2 && (
                        <StepPackage
                            packages={servicePackages}
                            vehicle={kendaraan}
                            value={data.service_package_id}
                            onChange={(id) => setData('service_package_id', id)}
                            error={errors.service_package_id}
                        />
                    )}

                    {langkah === 3 && (
                        <StepSchedule
                            rules={slotRules}
                            date={data.booking_date}
                            time={data.booking_time}
                            onDateChange={(tanggal) => setData('booking_date', tanggal)}
                            onTimeChange={(jam) => setData('booking_time', jam)}
                            errors={errors}
                        />
                    )}

                    {langkah === 4 && (
                        <StepReview
                            vehicle={kendaraan}
                            servicePackage={paket}
                            date={data.booking_date}
                            time={data.booking_time}
                            odometer={data.odometer}
                            complaint={data.complaint}
                            onOdometerChange={(nilai) => setData('odometer', nilai)}
                            onComplaintChange={(nilai) => setData('complaint', nilai)}
                            errors={errors}
                        />
                    )}
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setLangkah((n) => Math.max(1, n - 1))}
                        disabled={langkah === 1 || processing}
                    >
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Kembali
                    </Button>

                    {langkah < 4 ? (
                        <Button type="button" onClick={() => setLangkah((n) => n + 1)} disabled={!bolehLanjut}>
                            Lanjut
                            <ArrowRight className="h-4 w-4" aria-hidden="true" />
                        </Button>
                    ) : (
                        <Button type="submit" disabled={processing || vehicles.length === 0}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            Buat Booking
                        </Button>
                    )}
                </div>
            </form>
        </CustomerLayout>
    );
}
