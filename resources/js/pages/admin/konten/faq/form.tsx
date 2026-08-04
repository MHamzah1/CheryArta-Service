import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { type FaqRow } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface Props {
    faq: FaqRow | null;
}

export default function FaqForm({ faq }: Props) {
    const mengubah = faq !== null;

    const { data, setData, post, put, processing, errors } = useForm({
        question: faq?.question ?? '',
        answer: faq?.answer ?? '',
        category: faq?.category ?? '',
        is_active: faq?.is_active ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (mengubah) {
            put(route('admin.faqs.update', faq.id));
        } else {
            post(route('admin.faqs.store'));
        }
    };

    return (
        <AdminLayout
            title={mengubah ? 'Ubah Pertanyaan' : 'Tambah Pertanyaan'}
            description="Jawaban ditulis sebagai teks biasa dan langsung tampil di halaman FAQ publik."
        >
            <Head title={mengubah ? 'Ubah Pertanyaan' : 'Tambah Pertanyaan'} />

            <form onSubmit={submit} className="border-line bg-surface shadow-card max-w-2xl space-y-6 rounded-2xl border p-6">
                <div className="space-y-2">
                    <Label htmlFor="question">Pertanyaan</Label>
                    <Input
                        id="question"
                        value={data.question}
                        onChange={(e) => setData('question', e.target.value)}
                        autoFocus
                        required
                    />
                    <InputError message={errors.question} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="answer">Jawaban</Label>
                    <Textarea
                        id="answer"
                        value={data.answer}
                        onChange={(e) => setData('answer', e.target.value)}
                        rows={6}
                        required
                    />
                    <InputError message={errors.answer} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="category">Kategori</Label>
                    <Input
                        id="category"
                        value={data.category}
                        onChange={(e) => setData('category', e.target.value)}
                        placeholder="Mis. Booking, Servis, Garansi"
                    />
                    <p className="text-ink-soft text-sm">Opsional. Dipakai mengelompokkan pertanyaan di halaman publik.</p>
                    <InputError message={errors.category} />
                </div>

                <div className="flex items-center gap-3">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(nilai) => setData('is_active', nilai === true)}
                    />
                    <Label htmlFor="is_active">Tampilkan di halaman FAQ</Label>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Button type="submit" disabled={processing}>
                        {mengubah ? 'Simpan Perubahan' : 'Simpan Pertanyaan'}
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href={route('admin.faqs.index')}>Batal</Link>
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
