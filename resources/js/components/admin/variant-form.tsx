import SpecEditor from '@/components/admin/spec-editor';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type CarModelVariant, type SpecPair } from '@/types';
import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface FormData {
    name: string;
    price: string;
    specs: SpecPair[];
    is_active: boolean;
    sort_order: string;
    [key: string]: string | boolean | SpecPair[];
}

interface Props {
    carModelId: number;
    /** Kosong berarti form penambahan. */
    variant?: CarModelVariant;
    onDone: () => void;
}

/** Objek spesifikasi tersimpan → baris yang bisa disunting. */
function keBaris(specs: Record<string, string> | null | undefined): SpecPair[] {
    return Object.entries(specs ?? {}).map(([key, value]) => ({ key, value }));
}

export default function VariantForm({ carModelId, variant, onDone }: Props) {
    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        name: variant?.name ?? '',
        price: variant?.price === null || variant?.price === undefined ? '' : String(variant.price),
        specs: keBaris(variant?.specs),
        is_active: variant?.is_active ?? true,
        sort_order: String(variant?.sort_order ?? 0),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const opsi = { preserveScroll: true, onSuccess: onDone };

        if (variant) {
            put(route('admin.car-models.variants.update', [carModelId, variant.id]), opsi);
        } else {
            post(route('admin.car-models.variants.store', carModelId), opsi);
        }
    };

    const idAwalan = variant ? `varian-${variant.id}` : 'varian-baru';

    return (
        <form onSubmit={submit} className="border-line bg-canvas grid gap-4 rounded-xl border p-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor={`${idAwalan}-name`}>Nama Varian</Label>
                    <Input
                        id={`${idAwalan}-name`}
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        maxLength={80}
                        placeholder="Premium"
                        disabled={processing}
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor={`${idAwalan}-price`}>Harga (Rp)</Label>
                    <Input
                        id={`${idAwalan}-price`}
                        value={data.price}
                        onChange={(e) => setData('price', e.target.value.replace(/[^\d.]/g, ''))}
                        inputMode="decimal"
                        placeholder="Kosongkan bila belum ada harga"
                        disabled={processing}
                    />
                    <InputError message={errors.price} />
                </div>
            </div>

            <SpecEditor
                idPrefix={idAwalan}
                value={data.specs}
                onChange={(specs) => setData('specs', specs)}
                errors={errors}
                disabled={processing}
                label="Beda Spesifikasi"
                description="Cukup yang berbeda dari model dasar."
            />

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor={`${idAwalan}-sort`}>Urutan Tampil</Label>
                    <Input
                        id={`${idAwalan}-sort`}
                        value={data.sort_order}
                        onChange={(e) => setData('sort_order', e.target.value.replace(/\D/g, ''))}
                        inputMode="numeric"
                        maxLength={5}
                        disabled={processing}
                    />
                    <InputError message={errors.sort_order} />
                </div>

                <div className="flex items-center gap-3 sm:pt-8">
                    <Checkbox
                        id={`${idAwalan}-active`}
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked === true)}
                        disabled={processing}
                    />
                    <Label htmlFor={`${idAwalan}-active`} className="font-normal">
                        Varian aktif
                    </Label>
                </div>
            </div>

            <div className="flex flex-wrap gap-2">
                <Button type="submit" size="sm" disabled={processing}>
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                    {variant ? 'Simpan Varian' : 'Tambah Varian'}
                </Button>

                <Button type="button" variant="outline" size="sm" onClick={onDone} disabled={processing}>
                    Batal
                </Button>
            </div>
        </form>
    );
}
