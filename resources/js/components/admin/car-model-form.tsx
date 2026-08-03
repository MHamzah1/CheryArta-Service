import SpecEditor from '@/components/admin/spec-editor';
import ConfirmDialog from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type SelectOption, type SpecPair } from '@/types';
import { Link } from '@inertiajs/react';
import { FileText, LoaderCircle, Trash2 } from 'lucide-react';
import { type FormEventHandler } from 'react';

export interface CarModelFormData {
    name: string;
    slug: string;
    category: string;
    fuel_type: string;
    series_code: string;
    price_start: string;
    short_description: string;
    description: string;
    specs: SpecPair[];
    brochure: File | null;
    is_active: boolean;
    sort_order: string;
    [key: string]: string | boolean | File | null | SpecPair[];
}

interface Props {
    data: CarModelFormData;
    setData: (key: string, value: string | boolean | File | null | SpecPair[]) => void;
    errors: Partial<Record<string, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    submitLabel: string;
    categories: SelectOption[];
    fuelTypes: SelectOption[];
    /** Kode seri yang sudah dipakai — ditawarkan agar penulisannya konsisten. */
    seriesOptions: string[];
    /** Brosur yang tersimpan, bila ada. Hanya muncul di halaman ubah. */
    brochureUrl?: string | null;
    onDeleteBrochure?: () => void;
}

export default function CarModelForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    submitLabel,
    categories,
    fuelTypes,
    seriesOptions,
    brochureUrl,
    onDeleteBrochure,
}: Props) {
    return (
        <form onSubmit={onSubmit} className="grid max-w-3xl gap-6">
            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="name">Nama Model</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        maxLength={120}
                        placeholder="Tiggo 8 Pro"
                        disabled={processing}
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="slug">Slug</Label>
                    <Input
                        id="slug"
                        value={data.slug}
                        onChange={(e) => setData('slug', e.target.value.toLowerCase())}
                        maxLength={140}
                        placeholder="Dibuat otomatis dari nama"
                        disabled={processing}
                        aria-describedby="slug-hint"
                    />
                    <p id="slug-hint" className="text-ink-soft text-sm">
                        Dipakai di alamat halaman katalog publik. Kosongkan untuk membuatnya otomatis.
                    </p>
                    <InputError message={errors.slug} />
                </div>
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="category">Kategori</Label>
                    <Select value={data.category} onValueChange={(value) => setData('category', value)} disabled={processing}>
                        <SelectTrigger id="category">
                            <SelectValue placeholder="Pilih kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            {categories.map((category) => (
                                <SelectItem key={category.value} value={category.value}>
                                    {category.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.category} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="fuel_type">Bahan Bakar</Label>
                    <Select value={data.fuel_type} onValueChange={(value) => setData('fuel_type', value)} disabled={processing}>
                        <SelectTrigger id="fuel_type">
                            <SelectValue placeholder="Pilih bahan bakar" />
                        </SelectTrigger>
                        <SelectContent>
                            {fuelTypes.map((fuel) => (
                                <SelectItem key={fuel.value} value={fuel.value}>
                                    {fuel.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.fuel_type} />
                </div>
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="series_code">Kode Seri</Label>
                    <Input
                        id="series_code"
                        value={data.series_code}
                        onChange={(e) => setData('series_code', e.target.value.toLowerCase())}
                        maxLength={40}
                        placeholder="tiggo_8"
                        list="series-tersedia"
                        disabled={processing}
                        aria-describedby="series-hint"
                    />
                    <datalist id="series-tersedia">
                        {seriesOptions.map((code) => (
                            <option key={code} value={code} />
                        ))}
                    </datalist>
                    <p id="series-hint" className="text-ink-soft text-sm">
                        Menentukan paket Free Maintenance mana yang berlaku untuk model ini. Boleh dikosongkan.
                    </p>
                    <InputError message={errors.series_code} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="price_start">Harga Mulai (Rp)</Label>
                    <Input
                        id="price_start"
                        value={data.price_start}
                        onChange={(e) => setData('price_start', e.target.value.replace(/[^\d.]/g, ''))}
                        inputMode="decimal"
                        placeholder="Kosongkan bila ingin menulis “hubungi kami”"
                        disabled={processing}
                    />
                    <InputError message={errors.price_start} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="short_description">Deskripsi Singkat</Label>
                <Input
                    id="short_description"
                    value={data.short_description}
                    onChange={(e) => setData('short_description', e.target.value)}
                    maxLength={255}
                    placeholder="SUV tujuh penumpang untuk keluarga besar."
                    disabled={processing}
                    aria-describedby="short-hint"
                />
                <p id="short-hint" className="text-ink-soft text-sm">
                    Satu kalimat yang tampil di kartu katalog.
                </p>
                <InputError message={errors.short_description} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">Deskripsi Lengkap</Label>
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    maxLength={5000}
                    className="min-h-32"
                    placeholder="Opsional — tampil di halaman detail model."
                    disabled={processing}
                />
                <InputError message={errors.description} />
            </div>

            <SpecEditor
                idPrefix="model"
                value={data.specs}
                onChange={(specs) => setData('specs', specs)}
                errors={errors}
                disabled={processing}
                description="Ditampilkan apa adanya di halaman katalog, sesuai urutan baris di sini."
            />

            <div className="grid gap-2">
                <Label htmlFor="brochure">Brosur PDF</Label>

                {brochureUrl && (
                    <div className="border-line bg-canvas flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3">
                        <a
                            href={brochureUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-brand-700 focus-visible:ring-brand-600 inline-flex items-center gap-2 text-sm font-medium underline focus-visible:ring-2"
                        >
                            <FileText className="h-4 w-4" aria-hidden="true" />
                            Lihat brosur tersimpan
                        </a>

                        {onDeleteBrochure && (
                            <ConfirmDialog
                                trigger={
                                    <Button type="button" variant="outline" size="sm" aria-label="Hapus brosur tersimpan">
                                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                                        Hapus Brosur
                                    </Button>
                                }
                                title="Hapus brosur ini?"
                                description="Berkas brosur akan dihapus dan tautannya tidak lagi tersedia di halaman katalog."
                                confirmLabel="Hapus Brosur"
                                onConfirm={onDeleteBrochure}
                            />
                        )}
                    </div>
                )}

                <Input
                    id="brochure"
                    type="file"
                    accept="application/pdf"
                    onChange={(e) => setData('brochure', e.target.files?.[0] ?? null)}
                    disabled={processing}
                    aria-describedby="brochure-hint"
                />
                <p id="brochure-hint" className="text-ink-soft text-sm">
                    Format PDF, paling besar 5 MB. {brochureUrl ? 'Mengunggah berkas baru akan menggantikan yang lama.' : ''}
                </p>
                <InputError message={errors.brochure} />
            </div>

            <div className="grid gap-4">
                <div className="grid max-w-xs gap-2">
                    <Label htmlFor="sort_order">Urutan Tampil</Label>
                    <Input
                        id="sort_order"
                        value={data.sort_order}
                        onChange={(e) => setData('sort_order', e.target.value.replace(/\D/g, ''))}
                        inputMode="numeric"
                        maxLength={5}
                        placeholder="0"
                        disabled={processing}
                    />
                    <InputError message={errors.sort_order} />
                </div>

                <div className="flex items-start gap-3">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked === true)}
                        disabled={processing}
                    />
                    <div className="grid gap-1">
                        <Label htmlFor="is_active">Aktif</Label>
                        <p className="text-ink-soft text-sm">Model nonaktif tidak tampil di katalog publik.</p>
                    </div>
                </div>
            </div>

            <div className="flex flex-wrap gap-3">
                <Button type="submit" disabled={processing}>
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                    {submitLabel}
                </Button>

                <Button type="button" variant="outline" asChild>
                    <Link href={route('admin.car-models.index')}>Batal</Link>
                </Button>
            </div>
        </form>
    );
}
