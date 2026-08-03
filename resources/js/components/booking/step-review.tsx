import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDurasi, formatHargaPaket, formatJadwal, formatPlat } from '@/lib/format';
import { type BookingVehicleOption, type ServicePackageOption } from '@/types';

interface Props {
    vehicle?: BookingVehicleOption;
    servicePackage?: ServicePackageOption;
    date: string;
    time: string;
    odometer: string;
    complaint: string;
    onOdometerChange: (value: string) => void;
    onComplaintChange: (value: string) => void;
    errors: Partial<Record<string, string>>;
}

/** Langkah ④ — keluhan, odometer, lalu ringkasan sebelum dikirim. */
export default function StepReview({ vehicle, servicePackage, date, time, odometer, complaint, onOdometerChange, onComplaintChange, errors }: Props) {
    return (
        <div className="grid gap-6">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="odometer">Odometer (km)</Label>
                    <Input
                        id="odometer"
                        value={odometer}
                        onChange={(e) => onOdometerChange(e.target.value.replace(/\D/g, ''))}
                        inputMode="numeric"
                        maxLength={6}
                        placeholder={vehicle?.last_odometer ? String(vehicle.last_odometer) : 'Opsional'}
                        aria-describedby="odometer-hint"
                    />
                    <p id="odometer-hint" className="text-ink-soft text-sm">
                        {vehicle?.last_odometer
                            ? `Servis terakhir tercatat di ${vehicle.last_odometer.toLocaleString('id-ID')} km.`
                            : 'Boleh dikosongkan — bisa diisi advisor saat kendaraan masuk.'}
                    </p>
                    <InputError message={errors.odometer} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="complaint">Keluhan</Label>
                <Textarea
                    id="complaint"
                    value={complaint}
                    onChange={(e) => onComplaintChange(e.target.value)}
                    maxLength={1000}
                    placeholder="Contoh: ada bunyi dari rem depan saat mengerem pelan."
                    aria-describedby="complaint-hint"
                />
                <p id="complaint-hint" className="text-ink-soft text-sm">
                    Opsional, tetapi membantu advisor menyiapkan pekerjaan sebelum kendaraan datang.
                </p>
                <InputError message={errors.complaint} />
            </div>

            <div className="border-line bg-canvas rounded-2xl border p-5">
                <h2 className="mb-3 font-semibold">Ringkasan Booking</h2>

                <dl className="grid gap-2 text-sm">
                    <div className="flex flex-wrap justify-between gap-2">
                        <dt className="text-ink-soft">Kendaraan</dt>
                        <dd className="text-right font-medium">{vehicle ? `${vehicle.display_model} · ${formatPlat(vehicle.plate_full)}` : '-'}</dd>
                    </div>

                    <div className="flex flex-wrap justify-between gap-2">
                        <dt className="text-ink-soft">Paket layanan</dt>
                        <dd className="max-w-prose text-right font-medium">{servicePackage?.name ?? '-'}</dd>
                    </div>

                    <div className="flex flex-wrap justify-between gap-2">
                        <dt className="text-ink-soft">Jadwal</dt>
                        <dd className="text-right font-medium">{formatJadwal(date || null, time || null)}</dd>
                    </div>

                    <div className="flex flex-wrap justify-between gap-2">
                        <dt className="text-ink-soft">Perkiraan pengerjaan</dt>
                        <dd className="text-right font-medium">{servicePackage ? formatDurasi(servicePackage.estimated_duration_minutes) : '-'}</dd>
                    </div>

                    <div className="border-line mt-2 flex flex-wrap justify-between gap-2 border-t pt-3">
                        <dt className="text-ink-soft">Estimasi biaya</dt>
                        <dd className="text-right font-semibold">
                            {servicePackage ? formatHargaPaket(servicePackage.price, servicePackage.is_free) : '-'}
                        </dd>
                    </div>
                </dl>

                <p className="text-ink-muted mt-3 max-w-prose text-sm">
                    Estimasi biaya dihitung dari harga paket. Rincian akhir diterbitkan setelah pekerjaan selesai.
                </p>
            </div>
        </div>
    );
}
