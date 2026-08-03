import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SpecPair } from '@/types';
import { Plus, Trash2 } from 'lucide-react';

interface Props {
    /** Baris spesifikasi; urutannya ikut tersimpan. */
    value: SpecPair[];
    onChange: (specs: SpecPair[]) => void;
    /** Galat dari server, berkunci `specs.0.key` dan seterusnya. */
    errors: Partial<Record<string, string>>;
    disabled?: boolean;
    /** Awalan nama field, supaya varian dan model tidak bertabrakan id-nya. */
    idPrefix: string;
    label?: string;
    description?: string;
}

/**
 * Penyunting spesifikasi kunci–nilai (docs/07 §A4).
 *
 * Dikirim ke server sebagai daftar pasangan, bukan objek: urutan baris ikut
 * tersimpan dan kunci kembar bisa ditolak validasi dengan pesan yang menunjuk
 * baris tepatnya.
 */
export default function SpecEditor({ value, onChange, errors, disabled, idPrefix, label = 'Spesifikasi', description }: Props) {
    const ubah = (index: number, field: keyof SpecPair, isi: string) => {
        onChange(value.map((spec, i) => (i === index ? { ...spec, [field]: isi } : spec)));
    };

    const tambah = () => onChange([...value, { key: '', value: '' }]);
    const hapus = (index: number) => onChange(value.filter((_, i) => i !== index));

    return (
        <fieldset className="grid gap-3">
            <legend className="text-sm font-medium">{label}</legend>
            {description && <p className="text-ink-soft -mt-1 text-sm">{description}</p>}

            {value.length === 0 && <p className="text-ink-soft text-sm">Belum ada spesifikasi.</p>}

            {value.map((spec, index) => (
                <div key={index} className="grid gap-2 sm:grid-cols-[1fr_1.5fr_auto] sm:items-start sm:gap-3">
                    <div className="grid gap-1">
                        <Label htmlFor={`${idPrefix}-spec-key-${index}`} className="sr-only">
                            Nama spesifikasi baris {index + 1}
                        </Label>
                        <Input
                            id={`${idPrefix}-spec-key-${index}`}
                            value={spec.key}
                            onChange={(e) => ubah(index, 'key', e.target.value)}
                            placeholder="Mesin"
                            maxLength={60}
                            disabled={disabled}
                        />
                        <InputError message={errors[`specs.${index}.key`]} />
                    </div>

                    <div className="grid gap-1">
                        <Label htmlFor={`${idPrefix}-spec-value-${index}`} className="sr-only">
                            Nilai spesifikasi baris {index + 1}
                        </Label>
                        <Input
                            id={`${idPrefix}-spec-value-${index}`}
                            value={spec.value}
                            onChange={(e) => ubah(index, 'value', e.target.value)}
                            placeholder="1.6 TGDI"
                            maxLength={160}
                            disabled={disabled}
                        />
                        <InputError message={errors[`specs.${index}.value`]} />
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        onClick={() => hapus(index)}
                        disabled={disabled}
                        aria-label={`Hapus spesifikasi ${spec.key || `baris ${index + 1}`}`}
                    >
                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                    </Button>
                </div>
            ))}

            <div>
                <Button type="button" variant="outline" size="sm" onClick={tambah} disabled={disabled}>
                    <Plus className="h-4 w-4" aria-hidden="true" />
                    Tambah Spesifikasi
                </Button>
            </div>
        </fieldset>
    );
}
