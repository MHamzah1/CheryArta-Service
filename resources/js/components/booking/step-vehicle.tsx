import EmptyState from '@/components/empty-state';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPlat } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type BookingVehicleOption } from '@/types';
import { Link } from '@inertiajs/react';
import { Car, Plus } from 'lucide-react';

interface Props {
    vehicles: BookingVehicleOption[];
    value: string;
    onChange: (vehicleId: string) => void;
    error?: string;
}

/** Langkah ① — memilih kendaraan yang akan diservis. */
export default function StepVehicle({ vehicles, value, onChange, error }: Props) {
    if (vehicles.length === 0) {
        return (
            <EmptyState
                icon={Car}
                title="Belum ada kendaraan tersimpan"
                description="Tambahkan kendaraan Anda lebih dulu. Datanya akan terpakai lagi setiap kali memesan servis, jadi cukup sekali diisi."
                action={
                    <Button asChild>
                        <Link href={route('customer.vehicles.create')}>
                            <Plus className="h-4 w-4" aria-hidden="true" />
                            Tambah Kendaraan
                        </Link>
                    </Button>
                }
            />
        );
    }

    return (
        <div className="grid gap-4">
            <div role="radiogroup" aria-label="Pilih kendaraan" className="grid gap-3 sm:grid-cols-2">
                {vehicles.map((vehicle) => {
                    const terpilih = String(vehicle.id) === value;

                    return (
                        <button
                            key={vehicle.id}
                            type="button"
                            role="radio"
                            aria-checked={terpilih}
                            onClick={() => onChange(String(vehicle.id))}
                            className={cn(
                                'focus-visible:ring-brand-600 rounded-2xl border p-4 text-left transition focus-visible:ring-2',
                                terpilih ? 'border-brand-700 bg-brand-50' : 'border-line bg-surface hover:border-brand-400',
                            )}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-brand-700 text-lg font-bold">{formatPlat(vehicle.plate_full)}</p>
                                    <p className="mt-1 font-medium">{vehicle.display_model}</p>
                                    {vehicle.year && <p className="text-ink-soft text-sm">Tahun {vehicle.year}</p>}
                                </div>

                                {vehicle.is_primary && <Badge>Utama</Badge>}
                            </div>
                        </button>
                    );
                })}
            </div>

            <InputError message={error} />

            <div>
                <Button variant="outline" size="sm" asChild>
                    <Link href={route('customer.vehicles.create')}>
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Tambah Kendaraan Lain
                    </Link>
                </Button>
            </div>
        </div>
    );
}
