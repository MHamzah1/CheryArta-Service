import InputError from '@/components/input-error';
import PlateInput from '@/components/plate-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type CarModelOption } from '@/types';
import { Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEventHandler } from 'react';

export interface VehicleFormData {
    car_model_id: string;
    model_name_manual: string;
    plate_prefix: string;
    plate_number: string;
    plate_suffix: string;
    year: string;
    color: string;
    vin: string;
    is_primary: boolean;
    [key: string]: string | boolean;
}

interface Props {
    data: VehicleFormData;
    setData: (key: string, value: string | boolean) => void;
    errors: Partial<Record<string, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    submitLabel: string;
    carModels: CarModelOption[];
    /** Kendaraan pertama otomatis jadi utama, jadi pilihannya tak perlu tampil. */
    showPrimaryToggle: boolean;
}

/** Nilai sentinel: Select milik Radix tidak menerima value string kosong. */
const BUKAN_CHERY = 'manual';

export default function VehicleForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    submitLabel,
    carModels,
    showPrimaryToggle,
}: Props) {
    const modelDipilih = data.car_model_id === '' ? BUKAN_CHERY : data.car_model_id;

    return (
        <form onSubmit={onSubmit} className="grid max-w-2xl gap-6">
            <div className="grid gap-2">
                <Label htmlFor="car_model_id">Model Kendaraan</Label>
                <Select
                    value={modelDipilih}
                    onValueChange={(value) => setData('car_model_id', value === BUKAN_CHERY ? '' : value)}
                    disabled={processing}
                >
                    <SelectTrigger id="car_model_id">
                        <SelectValue placeholder="Pilih model" />
                    </SelectTrigger>
                    <SelectContent>
                        {carModels.map((model) => (
                            <SelectItem key={model.id} value={String(model.id)}>
                                {model.name}
                            </SelectItem>
                        ))}
                        <SelectItem value={BUKAN_CHERY}>Bukan Chery — ketik sendiri</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.car_model_id} />
            </div>

            {data.car_model_id === '' && (
                <div className="grid gap-2">
                    <Label htmlFor="model_name_manual">Nama Kendaraan</Label>
                    <Input
                        id="model_name_manual"
                        value={data.model_name_manual}
                        onChange={(e) => setData('model_name_manual', e.target.value)}
                        placeholder="Contoh: Honda Brio"
                        disabled={processing}
                    />
                    <InputError message={errors.model_name_manual} />
                </div>
            )}

            <PlateInput
                prefix={data.plate_prefix}
                number={data.plate_number}
                suffix={data.plate_suffix}
                onChange={(segment, value) => setData(segment, value)}
                errors={errors}
                disabled={processing}
            />

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="year">Tahun</Label>
                    <Input
                        id="year"
                        value={data.year}
                        onChange={(e) => setData('year', e.target.value.replace(/\D/g, ''))}
                        maxLength={4}
                        inputMode="numeric"
                        placeholder="2024"
                        disabled={processing}
                    />
                    <InputError message={errors.year} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="color">Warna</Label>
                    <Input
                        id="color"
                        value={data.color}
                        onChange={(e) => setData('color', e.target.value)}
                        placeholder="Putih"
                        disabled={processing}
                    />
                    <InputError message={errors.color} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="vin">Nomor Rangka</Label>
                <Input
                    id="vin"
                    value={data.vin}
                    onChange={(e) => setData('vin', e.target.value.toUpperCase())}
                    maxLength={25}
                    placeholder="Opsional"
                    disabled={processing}
                    aria-describedby="vin-hint"
                    className="uppercase"
                />
                <p id="vin-hint" className="text-ink-soft text-sm">
                    Membantu proses klaim garansi. Boleh dikosongkan.
                </p>
                <InputError message={errors.vin} />
            </div>

            {showPrimaryToggle && (
                <div className="flex items-start gap-3">
                    <Checkbox
                        id="is_primary"
                        checked={data.is_primary}
                        onCheckedChange={(checked) => setData('is_primary', checked === true)}
                        disabled={processing}
                    />
                    <div className="grid gap-1">
                        <Label htmlFor="is_primary">Jadikan kendaraan utama</Label>
                        <p className="text-ink-soft text-sm">Kendaraan ini akan terpilih lebih dulu saat Anda memesan servis.</p>
                    </div>
                </div>
            )}

            <div className="flex flex-wrap gap-3">
                <Button type="submit" disabled={processing}>
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                    {submitLabel}
                </Button>

                <Button type="button" variant="outline" asChild>
                    <Link href={route('customer.vehicles.index')}>Batal</Link>
                </Button>
            </div>
        </form>
    );
}
