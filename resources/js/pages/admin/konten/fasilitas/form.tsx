import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { type FacilityRow } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface Props {
    facility: FacilityRow | null;
}

export default function FasilitasForm({ facility }: Props) {
    const mengubah = facility !== null;

    const { data, setData, post, processing, errors } = useForm<{
        title: string;
        description: string;
        image: File | null;
        is_active: boolean;
        _method?: string;
    }>({
        title: facility?.title ?? '',
        description: facility?.description ?? '',
        image: null,
        is_active: facility?.is_active ?? true,
        // Unggahan berkas harus lewat POST; Laravel membaca _method untuk
        // memperlakukannya sebagai PUT.
        ...(mengubah ? { _method: 'put' } : {}),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(mengubah ? route('admin.facilities.update', facility.id) : route('admin.facilities.store'));
    };

    return (
        <AdminLayout
            title={mengubah ? 'Ubah Fasilitas' : 'Tambah Fasilitas'}
            description="Fasilitas tampil di landing page sesuai urutan yang Anda susun."
        >
            <Head title={mengubah ? 'Ubah Fasilitas' : 'Tambah Fasilitas'} />

            <form onSubmit={submit} className="border-line bg-surface shadow-card max-w-2xl space-y-6 rounded-2xl border p-6">
                <div className="space-y-2">
                    <Label htmlFor="title">Judul</Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        autoFocus
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="description">Deskripsi</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        rows={3}
                        required
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="image">Gambar</Label>

                    {mengubah && facility.image_url && (
                        <img
                            src={facility.image_url}
                            alt=""
                            className="border-line mb-2 h-32 w-32 rounded-xl border object-cover"
                        />
                    )}

                    <Input
                        id="image"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={(e) => setData('image', e.target.files?.[0] ?? null)}
                    />
                    <p className="text-ink-soft text-sm">
                        {mengubah ? 'Biarkan kosong bila gambarnya tidak diganti.' : 'Format JPG, PNG, atau WebP.'}
                    </p>
                    <InputError message={errors.image} />
                </div>

                <div className="flex items-center gap-3">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(nilai) => setData('is_active', nilai === true)}
                    />
                    <Label htmlFor="is_active">Tampilkan di landing page</Label>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Button type="submit" disabled={processing}>
                        {mengubah ? 'Simpan Perubahan' : 'Simpan Fasilitas'}
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href={route('admin.facilities.index')}>Batal</Link>
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
