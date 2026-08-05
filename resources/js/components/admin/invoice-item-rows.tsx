import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatRupiah } from '@/lib/format';
import { type SelectOption } from '@/types';
import { Plus, Trash2 } from 'lucide-react';

/** Satu baris yang sedang disunting. `key` hanya hidup di peramban. */
export interface DraftItem {
    key: string;
    type: string;
    description: string;
    qty: string;
    unit_price: string;
    /** Dituntut `FormDataConvertible` milik Inertia agar bisa ikut di `useForm` — pola yang sama dengan SpecPair. */
    [field: string]: string;
}

interface Props {
    items: DraftItem[];
    onChange: (items: DraftItem[]) => void;
    typeOptions: SelectOption[];
    errors: Record<string, string>;
    disabled?: boolean;
}

export function baris(type = 'part'): DraftItem {
    return { key: crypto.randomUUID(), type, description: '', qty: '1', unit_price: '0' };
}

/**
 * Baris rincian yang bisa disunting.
 *
 * Subtotal per baris yang tampil di sini adalah **pratinjau** — server yang
 * menghitung dan menyimpan (docs/05 §5.6). Menyalin rumusnya ke sini sebagai
 * sumber kebenaran adalah temuan invoice sistem lama (.claude/rules/20); yang
 * dilakukan komponen ini hanyalah memberi tahu advisor ke mana angkanya
 * bergerak sebelum ia menekan simpan.
 */
export default function InvoiceItemRows({ items, onChange, typeOptions, errors, disabled }: Props) {
    const ubah = (index: number, field: 'type' | 'description' | 'qty' | 'unit_price', value: string) => {
        onChange(items.map((item, i) => (i === index ? { ...item, [field]: value } : item)));
    };

    const hapus = (index: number) => onChange(items.filter((_, i) => i !== index));

    return (
        <div className="grid gap-3">
            {items.map((item, index) => (
                <div key={item.key} className="border-line grid gap-3 rounded-xl border p-3 md:grid-cols-12 md:items-start">
                    <div className="md:col-span-2">
                        <Label htmlFor={`type-${item.key}`} className="md:sr-only">
                            Jenis
                        </Label>
                        <Select value={item.type} onValueChange={(value) => ubah(index, 'type', value)} disabled={disabled}>
                            <SelectTrigger id={`type-${item.key}`}>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {typeOptions.map((opsi) => (
                                    <SelectItem key={opsi.value} value={opsi.value}>
                                        {opsi.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors[`items.${index}.type`]} />
                    </div>

                    <div className="md:col-span-5">
                        <Label htmlFor={`desc-${item.key}`} className="md:sr-only">
                            Keterangan
                        </Label>
                        <Input
                            id={`desc-${item.key}`}
                            value={item.description}
                            onChange={(event) => ubah(index, 'description', event.target.value)}
                            placeholder="Ganti oli mesin"
                            disabled={disabled}
                        />
                        <InputError message={errors[`items.${index}.description`]} />
                    </div>

                    <div className="md:col-span-2">
                        <Label htmlFor={`qty-${item.key}`} className="md:sr-only">
                            Jumlah
                        </Label>
                        <Input
                            id={`qty-${item.key}`}
                            type="number"
                            inputMode="decimal"
                            step="0.01"
                            min="0"
                            value={item.qty}
                            onChange={(event) => ubah(index, 'qty', event.target.value)}
                            disabled={disabled}
                        />
                        <InputError message={errors[`items.${index}.qty`]} />
                    </div>

                    <div className="md:col-span-2">
                        <Label htmlFor={`price-${item.key}`} className="md:sr-only">
                            Harga satuan
                        </Label>
                        <Input
                            id={`price-${item.key}`}
                            type="number"
                            inputMode="numeric"
                            step="0.01"
                            min="0"
                            value={item.unit_price}
                            onChange={(event) => ubah(index, 'unit_price', event.target.value)}
                            disabled={disabled}
                        />
                        <InputError message={errors[`items.${index}.unit_price`]} />
                    </div>

                    <div className="flex items-center justify-between gap-2 md:col-span-1 md:justify-end">
                        <span className="text-ink-soft text-sm tabular-nums md:hidden">
                            {formatRupiah(Number(item.qty) * Number(item.unit_price))}
                        </span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label={`Hapus baris ${index + 1}`}
                            onClick={() => hapus(index)}
                            disabled={disabled}
                        >
                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                        </Button>
                    </div>
                </div>
            ))}

            <InputError message={errors.items} />

            <div>
                <Button type="button" variant="outline" onClick={() => onChange([...items, baris()])} disabled={disabled}>
                    <Plus className="h-4 w-4" aria-hidden="true" />
                    Tambah Baris
                </Button>
            </div>
        </div>
    );
}
