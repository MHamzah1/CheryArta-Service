import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatDurasi } from '@/lib/format';
import { type SelectOption } from '@/types';
import { Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEventHandler } from 'react';

export interface ServicePackageFormData {
    code: string;
    name: string;
    category: string;
    description: string;
    applicable_series: string[];
    estimated_duration_minutes: string;
    price: string;
    is_free: boolean;
    is_active: boolean;
    sort_order: string;
    [key: string]: string | string[] | boolean;
}

interface Props {
    data: ServicePackageFormData;
    setData: (key: string, value: string | string[] | boolean) => void;
    errors: Partial<Record<string, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    submitLabel: string;
    categories: SelectOption[];
    seriesOptions: string[];
    /** Kode paket lama dipetakan ke data servis lama — menyuntingnya berisiko. */
    codeLocked?: boolean;
}

export default function ServicePackageForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    submitLabel,
    categories,
    seriesOptions,
    codeLocked = false,
}: Props) {
    const toggleSeries = (code: string, checked: boolean) => {
        setData('applicable_series', checked ? [...data.applicable_series, code] : data.applicable_series.filter((item) => item !== code));
    };

    const durasi = Number(data.estimated_duration_minutes);

    return (
        <form onSubmit={onSubmit} className="grid max-w-3xl gap-6">
            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="code">Kode Paket</Label>
                    <Input
                        id="code"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value)}
                        maxLength={60}
                        placeholder="first_maintenance_1000"
                        disabled={processing}
                        aria-describedby="code-hint"
                    />
                    <p id="code-hint" className="text-ink-soft text-sm">
                        {codeLocked
                            ? 'Kode ini dipakai memetakan data servis lama. Ubah hanya bila Anda yakin.'
                            : 'Huruf, angka, dan garis bawah. Dipakai sebagai penanda tetap paket ini.'}
                    </p>
                    <InputError message={errors.code} />
                </div>

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
            </div>

            <div className="grid gap-2">
                <Label htmlFor="name">Nama Paket</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    maxLength={200}
                    placeholder="First Maintenance (1.000 km/1 Bln)"
                    disabled={processing}
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">Deskripsi</Label>
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    maxLength={2000}
                    placeholder="Opsional — dijelaskan ke pelanggan saat memilih paket."
                    disabled={processing}
                />
                <InputError message={errors.description} />
            </div>

            <fieldset className="grid gap-3">
                <legend className="text-sm font-medium">Berlaku untuk Seri</legend>
                <p className="text-ink-soft -mt-1 text-sm">
                    Biarkan kosong bila paket ini berlaku untuk semua model. Daftar seri diambil dari katalog mobil.
                </p>

                {seriesOptions.length === 0 ? (
                    <p className="text-ink-soft text-sm">
                        Belum ada kode seri di katalog. Isi kode seri pada model mobil lebih dulu bila paket ini khusus satu seri.
                    </p>
                ) : (
                    <div className="grid gap-2 sm:grid-cols-2">
                        {seriesOptions.map((code) => (
                            <div key={code} className="flex items-center gap-3">
                                <Checkbox
                                    id={`series-${code}`}
                                    checked={data.applicable_series.includes(code)}
                                    onCheckedChange={(checked) => toggleSeries(code, checked === true)}
                                    disabled={processing}
                                />
                                <Label htmlFor={`series-${code}`} className="font-normal">
                                    {code}
                                </Label>
                            </div>
                        ))}
                    </div>
                )}

                <InputError message={errors.applicable_series} />
            </fieldset>

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="estimated_duration_minutes">Perkiraan Durasi (menit)</Label>
                    <Input
                        id="estimated_duration_minutes"
                        value={data.estimated_duration_minutes}
                        onChange={(e) => setData('estimated_duration_minutes', e.target.value.replace(/\D/g, ''))}
                        inputMode="numeric"
                        maxLength={3}
                        placeholder="60"
                        disabled={processing}
                        aria-describedby="durasi-hint"
                    />
                    <p id="durasi-hint" className="text-ink-soft text-sm">
                        Antara 15 dan 480 menit{durasi >= 15 ? ` — saat ini ${formatDurasi(durasi)}` : ''}.
                    </p>
                    <InputError message={errors.estimated_duration_minutes} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="sort_order">Urutan Tampil</Label>
                    <Input
                        id="sort_order"
                        value={data.sort_order}
                        onChange={(e) => setData('sort_order', e.target.value.replace(/\D/g, ''))}
                        inputMode="numeric"
                        maxLength={5}
                        placeholder="0"
                        disabled={processing}
                        aria-describedby="sort-hint"
                    />
                    <p id="sort-hint" className="text-ink-soft text-sm">
                        Angka kecil tampil lebih dulu di form booking.
                    </p>
                    <InputError message={errors.sort_order} />
                </div>
            </div>

            <div className="grid gap-4">
                <div className="flex items-start gap-3">
                    <Checkbox
                        id="is_free"
                        checked={data.is_free}
                        onCheckedChange={(checked) => setData('is_free', checked === true)}
                        disabled={processing}
                    />
                    <div className="grid gap-1">
                        <Label htmlFor="is_free">Paket gratis</Label>
                        <p className="text-ink-soft text-sm">Ditampilkan sebagai &ldquo;Gratis&rdquo;, bukan &ldquo;Rp 0&rdquo;.</p>
                    </div>
                </div>

                {/* Harga disembunyikan saat gratis; server tetap memaksanya 0. */}
                {!data.is_free && (
                    <div className="grid max-w-xs gap-2">
                        <Label htmlFor="price">Harga (Rp)</Label>
                        <Input
                            id="price"
                            value={data.price}
                            onChange={(e) => setData('price', e.target.value.replace(/[^\d.]/g, ''))}
                            inputMode="decimal"
                            placeholder="450000"
                            disabled={processing}
                        />
                        <InputError message={errors.price} />
                    </div>
                )}

                <div className="flex items-start gap-3">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked === true)}
                        disabled={processing}
                    />
                    <div className="grid gap-1">
                        <Label htmlFor="is_active">Aktif</Label>
                        <p className="text-ink-soft text-sm">Paket nonaktif tidak muncul sebagai pilihan di form booking.</p>
                    </div>
                </div>
            </div>

            <div className="flex flex-wrap gap-3">
                <Button type="submit" disabled={processing}>
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                    {submitLabel}
                </Button>

                <Button type="button" variant="outline" asChild>
                    <Link href={route('admin.service-packages.index')}>Batal</Link>
                </Button>
            </div>
        </form>
    );
}
