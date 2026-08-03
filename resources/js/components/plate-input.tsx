import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    prefix: string;
    number: string;
    suffix: string;
    onChange: (segment: 'plate_prefix' | 'plate_number' | 'plate_suffix', value: string) => void;
    errors?: {
        plate_prefix?: string;
        plate_number?: string;
        plate_suffix?: string;
    };
    disabled?: boolean;
}

/**
 * Masukan plat nomor tiga segmen.
 *
 * Kode wilayah menerima 1 ATAU 2 huruf — "B" (Jakarta) sama sahnya dengan
 * "AB" (Yogyakarta). Sistem lama memaksa dua huruf dan karena itu menolak
 * plat Jakarta, yang justru paling banyak (temuan B1).
 *
 * Pembatasan di sini hanya kenyamanan; yang menentukan tetap
 * App\Rules\ValidPlateSegment di server.
 */
export default function PlateInput({ prefix, number, suffix, onChange, errors, disabled }: Props) {
    return (
        <div className="grid gap-2">
            <Label htmlFor="plate_prefix">Plat Nomor</Label>

            <div className="grid grid-cols-[4.5rem_1fr_5rem] items-start gap-2">
                <div>
                    <Input
                        id="plate_prefix"
                        value={prefix}
                        onChange={(e) => onChange('plate_prefix', e.target.value.toUpperCase())}
                        maxLength={2}
                        placeholder="B"
                        autoComplete="off"
                        disabled={disabled}
                        aria-label="Kode wilayah"
                        className="text-center uppercase"
                    />
                </div>

                <div>
                    <Input
                        id="plate_number"
                        value={number}
                        onChange={(e) => onChange('plate_number', e.target.value.replace(/\D/g, ''))}
                        maxLength={4}
                        inputMode="numeric"
                        placeholder="1234"
                        autoComplete="off"
                        disabled={disabled}
                        aria-label="Nomor plat"
                        className="text-center"
                    />
                </div>

                <div>
                    <Input
                        id="plate_suffix"
                        value={suffix}
                        onChange={(e) => onChange('plate_suffix', e.target.value.toUpperCase())}
                        maxLength={3}
                        placeholder="ABC"
                        autoComplete="off"
                        disabled={disabled}
                        aria-label="Huruf akhir plat"
                        className="text-center uppercase"
                    />
                </div>
            </div>

            <p className="text-ink-soft text-sm">Contoh: B 1234 ABC. Kode wilayah boleh 1 atau 2 huruf.</p>

            <InputError message={errors?.plate_prefix} />
            <InputError message={errors?.plate_number} />
            <InputError message={errors?.plate_suffix} />
        </div>
    );
}
