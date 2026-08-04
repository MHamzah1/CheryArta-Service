import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { type TestimonialRow } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface Props {
    testimonial: TestimonialRow | null;
}

export default function TestimoniForm({ testimonial }: Props) {
    const mengubah = testimonial !== null;

    const { data, setData, post, put, processing, errors } = useForm({
        customer_name: testimonial?.customer_name ?? '',
        car_model: testimonial?.car_model ?? '',
        rating: String(testimonial?.rating ?? 5),
        content: testimonial?.content ?? '',
        is_published: testimonial?.is_published ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (mengubah) {
            put(route('admin.testimonials.update', testimonial.id));
        } else {
            post(route('admin.testimonials.store'));
        }
    };

    return (
        <AdminLayout
            title={mengubah ? 'Ubah Testimoni' : 'Tambah Testimoni'}
            description="Testimoni diketik admin dan langsung tampil di landing page bila diterbitkan."
        >
            <Head title={mengubah ? 'Ubah Testimoni' : 'Tambah Testimoni'} />

            <form onSubmit={submit} className="border-line bg-surface shadow-card max-w-2xl space-y-6 rounded-2xl border p-6">
                <div className="space-y-2">
                    <Label htmlFor="customer_name">Nama Pelanggan</Label>
                    <Input
                        id="customer_name"
                        value={data.customer_name}
                        onChange={(e) => setData('customer_name', e.target.value)}
                        autoFocus
                        required
                    />
                    <InputError message={errors.customer_name} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="car_model">Model Mobil</Label>
                    <Input
                        id="car_model"
                        value={data.car_model}
                        onChange={(e) => setData('car_model', e.target.value)}
                        placeholder="Mis. Tiggo 8 Pro"
                    />
                    <p className="text-ink-soft text-sm">Opsional.</p>
                    <InputError message={errors.car_model} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="rating">Rating</Label>
                    <Select value={data.rating} onValueChange={(nilai) => setData('rating', nilai)}>
                        <SelectTrigger id="rating" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {[5, 4, 3, 2, 1].map((nilai) => (
                                <SelectItem key={nilai} value={String(nilai)}>
                                    {nilai} dari 5
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.rating} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="content">Isi Testimoni</Label>
                    <Textarea
                        id="content"
                        value={data.content}
                        onChange={(e) => setData('content', e.target.value)}
                        rows={5}
                        required
                    />
                    <InputError message={errors.content} />
                </div>

                <div className="flex items-center gap-3">
                    <Checkbox
                        id="is_published"
                        checked={data.is_published}
                        onCheckedChange={(nilai) => setData('is_published', nilai === true)}
                    />
                    <Label htmlFor="is_published">Terbitkan di landing page</Label>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Button type="submit" disabled={processing}>
                        {mengubah ? 'Simpan Perubahan' : 'Simpan Testimoni'}
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href={route('admin.testimonials.index')}>Batal</Link>
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
