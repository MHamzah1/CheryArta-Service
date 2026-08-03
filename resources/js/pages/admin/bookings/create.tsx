import WalkInCustomerPicker, { type CustomerBaru } from '@/components/admin/walk-in-customer-picker';
import WalkInVehiclePicker, { type KendaraanBaru } from '@/components/admin/walk-in-vehicle-picker';
import SlotPicker from '@/components/booking/slot-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { formatDurasi, formatHargaPaket, formatTanggal } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
    type CarModelOption,
    type ServicePackageOption,
    type SlotRules,
    type WalkInCustomerResult,
    type WalkInSelectedCustomer,
} from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useEffect, useState, type FormEvent, type ReactNode } from 'react';

interface Props {
    servicePackages: ServicePackageOption[];
    carModels: CarModelOption[];
    slotRules: SlotRules;
    filters: { cari: string | null };
    customerResults: WalkInCustomerResult[];
    selectedCustomer: WalkInSelectedCustomer | null;
}

const CUSTOMER_KOSONG: CustomerBaru = { name: '', email: '', phone_wa: '', address: '' };

const KENDARAAN_KOSONG: KendaraanBaru = {
    car_model_id: '',
    model_name_manual: '',
    plate_prefix: '',
    plate_number: '',
    plate_suffix: '',
    year: '',
    color: '',
};

function Langkah({ nomor, judul, keterangan, children }: { nomor: number; judul: string; keterangan?: string; children: ReactNode }) {
    const id = `langkah-${nomor}`;

    return (
        <section aria-labelledby={id} className="border-line bg-surface shadow-card rounded-2xl border p-5">
            <div className="mb-4">
                <h2 id={id} className="text-lg font-semibold">
                    <span className="bg-brand-700 mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full text-sm text-white">
                        {nomor}
                    </span>
                    {judul}
                </h2>
                {keterangan && <p className="text-ink-soft mt-1 text-sm">{keterangan}</p>}
            </div>
            {children}
        </section>
    );
}

/**
 * Booking walk-in (docs/07 §A2, roadmap 1.5.4).
 *
 * Aturan H-1 dilewati untuk walk-in sehingga hari ini boleh dipilih — batas
 * kalendernya datang dari `slotRules` yang dihitung server dengan sumber
 * `walk_in`. Kuota per slot TETAP berlaku, dan itulah sebabnya jam penuh tetap
 * tampil abu-abu di sini.
 */
export default function WalkInBookingCreate({ servicePackages, carModels, slotRules, filters, customerResults, selectedCustomer }: Props) {
    const [kendaraanBaruMode, setKendaraanBaruMode] = useState(false);

    const { data, setData, post, processing, errors, transform } = useForm({
        user_id: null as number | null,
        customer: CUSTOMER_KOSONG,
        vehicle_id: '',
        vehicle: KENDARAAN_KOSONG,
        service_package_id: '',
        booking_date: '',
        booking_time: '',
        odometer: '',
        complaint: '',
    });

    // Pelanggan terpilih datang dari server lewat kunjungan parsial, jadi
    // `user_id` mengikutinya alih-alih disimpan terpisah dan berisiko berbeda.
    useEffect(() => {
        setData((sebelumnya) => ({
            ...sebelumnya,
            user_id: selectedCustomer?.id ?? null,
            vehicle_id: '',
        }));

        setKendaraanBaruMode(selectedCustomer !== null && selectedCustomer.vehicles.length === 0);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedCustomer?.id]);

    const paket = servicePackages.find((item) => String(item.id) === data.service_package_id);

    /*
     * Server memakai `required_without` berpasangan: mengirim `customer` yang
     * masih kosong bersama `user_id` akan memicu galat "nama wajib diisi" untuk
     * pelanggan yang justru sudah dipilih. Karena itu bagian yang tidak dipakai
     * dibuang dari muatan, bukan dikirim kosong.
     */
    transform((form) => ({
        ...(form.user_id !== null ? { user_id: form.user_id } : { customer: form.customer }),
        ...(kendaraanBaruMode ? { vehicle: form.vehicle } : { vehicle_id: form.vehicle_id }),
        service_package_id: form.service_package_id,
        booking_date: form.booking_date,
        booking_time: form.booking_time,
        odometer: form.odometer,
        complaint: form.complaint,
    }));

    const kirim = (event: FormEvent) => {
        event.preventDefault();
        post(route('admin.bookings.store'));
    };

    return (
        <AdminLayout
            title="Booking Walk-in"
            description="Untuk pelanggan yang datang langsung atau memesan lewat telepon. Booking langsung berstatus Dikonfirmasi."
            actions={
                <Button variant="outline" asChild>
                    <Link href={route('admin.bookings.index')}>
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Kembali
                    </Link>
                </Button>
            }
        >
            <Head title="Booking Walk-in" />

            <form onSubmit={kirim} className="grid max-w-4xl gap-6">
                <Langkah nomor={1} judul="Pelanggan" keterangan="Cari yang sudah terdaftar, atau buatkan akun baru.">
                    <WalkInCustomerPicker
                        keyword={filters.cari}
                        results={customerResults}
                        selected={selectedCustomer}
                        customerBaru={data.customer}
                        onSelect={(id) => setData('user_id', id)}
                        onCustomerBaruChange={(field, value) => setData('customer', { ...data.customer, [field]: value })}
                        errors={errors as Partial<Record<string, string>>}
                    />
                </Langkah>

                <Langkah nomor={2} judul="Kendaraan">
                    <WalkInVehiclePicker
                        vehicles={selectedCustomer?.vehicles ?? []}
                        carModels={carModels}
                        vehicleId={data.vehicle_id}
                        kendaraanBaru={data.vehicle}
                        modeBaru={kendaraanBaruMode || selectedCustomer === null}
                        onSelect={(id) => setData('vehicle_id', id)}
                        onModeBaruChange={setKendaraanBaruMode}
                        onKendaraanBaruChange={(field, value) => setData('vehicle', { ...data.vehicle, [field]: value })}
                        errors={errors as Partial<Record<string, string>>}
                    />
                </Langkah>

                <Langkah nomor={3} judul="Paket Layanan">
                    <div className="grid gap-3 sm:grid-cols-2">
                        {servicePackages.map((item) => {
                            const terpilih = String(item.id) === data.service_package_id;

                            return (
                                <button
                                    key={item.id}
                                    type="button"
                                    role="radio"
                                    aria-checked={terpilih}
                                    onClick={() => setData('service_package_id', String(item.id))}
                                    className={cn(
                                        'focus-visible:ring-brand-600 rounded-2xl border p-4 text-left transition focus-visible:ring-2',
                                        terpilih ? 'border-brand-700 bg-brand-50' : 'border-line bg-surface hover:border-brand-400',
                                    )}
                                >
                                    <p className="font-medium">{item.name}</p>
                                    <p className="text-ink-soft mt-1 text-sm">
                                        {formatDurasi(item.estimated_duration_minutes)} ·{' '}
                                        {formatHargaPaket(item.price, item.is_free)}
                                    </p>
                                </button>
                            );
                        })}
                    </div>

                    <InputError className="mt-2" message={errors.service_package_id} />
                </Langkah>

                <Langkah
                    nomor={4}
                    judul="Jadwal"
                    keterangan={`Walk-in boleh dijadwalkan hari ini. Paling cepat ${formatTanggal(slotRules.earliest_date)}.`}
                >
                    <div className="grid gap-6">
                        <div className="grid max-w-xs gap-2">
                            <Label htmlFor="booking_date">Tanggal Servis</Label>
                            <Input
                                id="booking_date"
                                type="date"
                                value={data.booking_date}
                                min={slotRules.earliest_date}
                                max={slotRules.latest_date}
                                onChange={(e) => {
                                    setData((sebelumnya) => ({ ...sebelumnya, booking_date: e.target.value, booking_time: '' }));
                                }}
                            />
                            <InputError message={errors.booking_date} />
                        </div>

                        <div className="grid gap-2">
                            <p className="text-sm font-medium">Jam Servis</p>
                            {data.booking_date && <p className="text-ink-soft -mt-1 text-sm">{formatTanggal(data.booking_date)}</p>}

                            <SlotPicker
                                date={data.booking_date}
                                value={data.booking_time}
                                onChange={(time) => setData('booking_time', time)}
                                source={slotRules.source}
                            />

                            <InputError message={errors.booking_time} />
                        </div>
                    </div>
                </Langkah>

                <Langkah nomor={5} judul="Keterangan" keterangan="Keduanya opsional, tetapi sangat membantu teknisi.">
                    <div className="grid gap-4">
                        <div className="grid max-w-xs gap-1.5">
                            <Label htmlFor="odometer">Odometer (km)</Label>
                            <Input
                                id="odometer"
                                type="number"
                                inputMode="numeric"
                                min={0}
                                value={data.odometer}
                                onChange={(e) => setData('odometer', e.target.value)}
                            />
                            <InputError message={errors.odometer} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="complaint">Keluhan</Label>
                            <Textarea
                                id="complaint"
                                rows={3}
                                maxLength={1000}
                                value={data.complaint}
                                onChange={(e) => setData('complaint', e.target.value)}
                                placeholder="Contoh: Ada bunyi dari rem depan saat pengereman."
                            />
                            <InputError message={errors.complaint} />
                        </div>
                    </div>
                </Langkah>

                <div className="flex flex-wrap items-center gap-3">
                    <Button type="submit" disabled={processing}>
                        Buat Booking
                    </Button>

                    {paket && (
                        <p className="text-ink-soft text-sm">
                            Estimasi biaya {formatHargaPaket(paket.price, paket.is_free)} · perkiraan pengerjaan{' '}
                            {formatDurasi(paket.estimated_duration_minutes)}.
                        </p>
                    )}
                </div>
            </form>
        </AdminLayout>
    );
}
