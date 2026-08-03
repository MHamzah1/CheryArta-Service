import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { formatDurasi, formatHargaPaket } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type BookingVehicleOption, type ServicePackageOption } from '@/types';

interface Props {
    packages: ServicePackageOption[];
    /** Kendaraan yang dipilih di langkah ①, untuk menyorot paket yang cocok. */
    vehicle?: BookingVehicleOption;
    value: string;
    onChange: (packageId: string) => void;
    error?: string;
}

/**
 * Paket berlaku untuk kendaraan ini bila serinya cocok, atau bila paket itu
 * memang berlaku umum (`applicable_series` kosong).
 *
 * Ini KENYAMANAN, bukan pengaman: server hanya mewajibkan paketnya aktif
 * (docs/05 §5.8), sehingga advisor tetap bisa memakai paket lintas seri.
 */
function cocokUntuk(paket: ServicePackageOption, vehicle?: BookingVehicleOption): boolean {
    if (paket.applicable_series.length === 0) return true;
    if (!vehicle?.series_code) return false;

    return paket.applicable_series.includes(vehicle.series_code);
}

/** Langkah ② — memilih paket layanan beserta durasi dan estimasi biayanya. */
export default function StepPackage({ packages, vehicle, value, onChange, error }: Props) {
    const disarankan = packages.filter((paket) => cocokUntuk(paket, vehicle));
    const lainnya = packages.filter((paket) => !cocokUntuk(paket, vehicle));

    const kartu = (paket: ServicePackageOption) => {
        const terpilih = String(paket.id) === value;

        return (
            <button
                key={paket.id}
                type="button"
                role="radio"
                aria-checked={terpilih}
                onClick={() => onChange(String(paket.id))}
                className={cn(
                    'focus-visible:ring-brand-600 rounded-2xl border p-4 text-left transition focus-visible:ring-2',
                    terpilih ? 'border-brand-700 bg-brand-50' : 'border-line bg-surface hover:border-brand-400',
                )}
            >
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <p className="max-w-prose font-medium">{paket.name}</p>
                    <Badge variant={paket.is_free ? 'default' : 'secondary'}>{formatHargaPaket(paket.price, paket.is_free)}</Badge>
                </div>

                {paket.description && <p className="text-ink-soft mt-2 max-w-prose text-sm">{paket.description}</p>}

                <p className="text-ink-muted mt-2 text-sm">Perkiraan pengerjaan {formatDurasi(paket.estimated_duration_minutes)}</p>
            </button>
        );
    };

    return (
        <div className="grid gap-6">
            {disarankan.length > 0 && (
                <div className="grid gap-3">
                    <h2 className="text-sm font-semibold">{vehicle ? `Sesuai untuk ${vehicle.display_model}` : 'Paket Layanan'}</h2>
                    <div role="radiogroup" aria-label="Pilih paket layanan" className="grid gap-3">
                        {disarankan.map(kartu)}
                    </div>
                </div>
            )}

            {lainnya.length > 0 && (
                <div className="grid gap-3">
                    <h2 className="text-ink-soft text-sm font-semibold">Paket lain</h2>
                    <p className="text-ink-muted -mt-2 max-w-prose text-sm">
                        Paket berikut ditujukan untuk seri kendaraan lain. Anda tetap bisa memilihnya bila advisor menyarankan.
                    </p>
                    <div className="grid gap-3">{lainnya.map(kartu)}</div>
                </div>
            )}

            <InputError message={error} />
        </div>
    );
}
