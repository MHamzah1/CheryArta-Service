import InputError from '@/components/input-error';
import PlateInput from '@/components/plate-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPlat } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type BookingVehicleOption, type CarModelOption } from '@/types';
import { Car, Plus, X } from 'lucide-react';

export interface KendaraanBaru {
    car_model_id: string;
    model_name_manual: string;
    plate_prefix: string;
    plate_number: string;
    plate_suffix: string;
    year: string;
    color: string;
    /** Dituntut `FormDataConvertible` milik Inertia agar bisa ikut di `useForm`. */
    [field: string]: string;
}

interface Props {
    /** Kendaraan milik pelanggan terpilih; kosong bila pelanggannya baru. */
    vehicles: BookingVehicleOption[];
    carModels: CarModelOption[];
    vehicleId: string;
    kendaraanBaru: KendaraanBaru;
    modeBaru: boolean;
    onSelect: (id: string) => void;
    onModeBaruChange: (aktif: boolean) => void;
    onKendaraanBaruChange: (field: keyof KendaraanBaru, value: string) => void;
    errors: Partial<Record<string, string>>;
}

/**
 * Langkah ② walk-in — pilih kendaraan pelanggan, atau daftarkan sekarang juga.
 *
 * Kendaraan yang ditawarkan hanya milik pelanggan yang sudah dipilih; server
 * memeriksa ulang kepemilikannya sebelum menyimpan
 * (StoreWalkInBookingRequest), karena daftar di layar bukan pengaman.
 */
export default function WalkInVehiclePicker({
    vehicles,
    carModels,
    vehicleId,
    kendaraanBaru,
    modeBaru,
    onSelect,
    onModeBaruChange,
    onKendaraanBaruChange,
    errors,
}: Props) {
    const platErrors = {
        plate_prefix: errors['vehicle.plate_prefix'],
        plate_number: errors['vehicle.plate_number'],
        plate_suffix: errors['vehicle.plate_suffix'],
    };

    if (modeBaru) {
        return (
            <div className="border-line bg-surface grid gap-4 rounded-2xl border p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="font-semibold">Kendaraan baru</p>

                    {vehicles.length > 0 && (
                        <Button type="button" variant="ghost" size="sm" onClick={() => onModeBaruChange(false)}>
                            <X className="h-4 w-4" aria-hidden="true" />
                            Pilih dari daftar
                        </Button>
                    )}
                </div>

                <PlateInput
                    prefix={kendaraanBaru.plate_prefix}
                    number={kendaraanBaru.plate_number}
                    suffix={kendaraanBaru.plate_suffix}
                    onChange={(segment, value) => onKendaraanBaruChange(segment as keyof KendaraanBaru, value)}
                    errors={platErrors}
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-1.5">
                        <Label htmlFor="vehicle_car_model">Model Chery</Label>
                        <select
                            id="vehicle_car_model"
                            value={kendaraanBaru.car_model_id}
                            onChange={(e) => onKendaraanBaruChange('car_model_id', e.target.value)}
                            className="border-input bg-background focus-visible:ring-brand-600 h-10 rounded-md border px-3 text-sm focus-visible:ring-2"
                        >
                            <option value="">Bukan Chery / tidak ada di daftar</option>
                            {carModels.map((model) => (
                                <option key={model.id} value={model.id}>
                                    {model.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors['vehicle.car_model_id']} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="vehicle_model_manual">Nama kendaraan</Label>
                        <Input
                            id="vehicle_model_manual"
                            value={kendaraanBaru.model_name_manual}
                            onChange={(e) => onKendaraanBaruChange('model_name_manual', e.target.value)}
                            placeholder="Diisi bila modelnya tidak ada di daftar"
                            disabled={kendaraanBaru.car_model_id !== ''}
                            maxLength={120}
                            autoComplete="off"
                        />
                        <InputError message={errors['vehicle.model_name_manual']} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="vehicle_year">Tahun</Label>
                        <Input
                            id="vehicle_year"
                            type="number"
                            inputMode="numeric"
                            value={kendaraanBaru.year}
                            onChange={(e) => onKendaraanBaruChange('year', e.target.value)}
                            placeholder="2024"
                        />
                        <InputError message={errors['vehicle.year']} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="vehicle_color">Warna</Label>
                        <Input
                            id="vehicle_color"
                            value={kendaraanBaru.color}
                            onChange={(e) => onKendaraanBaruChange('color', e.target.value)}
                            maxLength={40}
                            autoComplete="off"
                        />
                        <InputError message={errors['vehicle.color']} />
                    </div>
                </div>

                <InputError message={errors.vehicle} />
            </div>
        );
    }

    return (
        <div className="grid gap-4">
            {vehicles.length === 0 ? (
                <p className="text-ink-soft text-sm">
                    Pelanggan ini belum punya kendaraan tersimpan. Daftarkan kendaraannya sekarang.
                </p>
            ) : (
                <div role="radiogroup" aria-label="Pilih kendaraan" className="grid gap-3 sm:grid-cols-2">
                    {vehicles.map((vehicle) => {
                        const terpilih = String(vehicle.id) === vehicleId;

                        return (
                            <button
                                key={vehicle.id}
                                type="button"
                                role="radio"
                                aria-checked={terpilih}
                                onClick={() => onSelect(String(vehicle.id))}
                                className={cn(
                                    'focus-visible:ring-brand-600 rounded-2xl border p-4 text-left transition focus-visible:ring-2',
                                    terpilih ? 'border-brand-700 bg-brand-50' : 'border-line bg-surface hover:border-brand-400',
                                )}
                            >
                                <p className="text-brand-700 text-lg font-bold">{formatPlat(vehicle.plate_full)}</p>
                                <p className="mt-1 font-medium">{vehicle.display_model}</p>
                                <p className="text-ink-soft text-sm">
                                    {[vehicle.year, vehicle.last_odometer !== null && `${vehicle.last_odometer.toLocaleString('id-ID')} km`]
                                        .filter(Boolean)
                                        .join(' · ') || 'Tanpa keterangan tambahan'}
                                </p>
                            </button>
                        );
                    })}
                </div>
            )}

            <InputError message={errors.vehicle_id} />

            <div>
                <Button type="button" variant="outline" onClick={() => onModeBaruChange(true)}>
                    {vehicles.length === 0 ? (
                        <Car className="h-4 w-4" aria-hidden="true" />
                    ) : (
                        <Plus className="h-4 w-4" aria-hidden="true" />
                    )}
                    Daftarkan Kendaraan Baru
                </Button>
            </div>
        </div>
    );
}
