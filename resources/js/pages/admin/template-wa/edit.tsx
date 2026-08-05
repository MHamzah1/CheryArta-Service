import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { type WhatsAppTemplateDetail } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { useRef, type FormEventHandler } from 'react';

interface Props {
    template: WhatsAppTemplateDetail;
    /** Nama placeholder yang sah — datang dari config('whatsapp.allowed_placeholders'). */
    placeholders: string[];
    /** Nilai contoh untuk pratinjau; karangan, bukan booking sungguhan. */
    sampleValues: Record<string, string>;
    maxLength: number;
}

/**
 * A7 — menyunting satu template pesan (docs/07 §A7).
 *
 * Pratinjaunya dirender di sini memakai nilai contoh yang DIKIRIM SERVER.
 * Penggantian teksnya memang disalin ke klien, tetapi hanya untuk pratinjau —
 * pesan yang sesungguhnya tetap disusun server saat dikirim, dan `rendered_message`
 * di log tidak pernah datang dari sini (keputusan grill Q2).
 */
export default function WhatsAppTemplateEdit({ template, placeholders, sampleValues, maxLength }: Props) {
    const bodyRef = useRef<HTMLTextAreaElement>(null);

    const { data, setData, put, processing, errors } = useForm({
        name: template.name,
        body: template.body,
        is_active: template.is_active,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.whatsapp-templates.update', template.id));
    };

    /** Menyisipkan di posisi kursor, bukan menempel di akhir. */
    const sisipkan = (nama: string) => {
        const token = `{{${nama}}}`;
        const el = bodyRef.current;

        if (el === null) {
            setData('body', data.body + token);

            return;
        }

        const awal = el.selectionStart;
        const akhir = el.selectionEnd;

        setData('body', data.body.slice(0, awal) + token + data.body.slice(akhir));

        window.setTimeout(() => {
            el.focus();
            el.setSelectionRange(awal + token.length, awal + token.length);
        }, 0);
    };

    const pratinjau = Object.entries(sampleValues).reduce(
        (teks, [token, nilai]) => teks.split(token).join(nilai),
        data.body,
    );

    const sisa = maxLength - data.body.length;

    return (
        <AdminLayout title={`Ubah Template — ${template.name}`} description={template.trigger}>
            <Head title={`Ubah Template ${template.name}`} />

            <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                <form onSubmit={submit} className="border-line bg-surface shadow-card space-y-6 rounded-2xl border p-6">
                    <div className="space-y-2">
                        <Label htmlFor="name">Nama Template</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                        <p className="text-ink-soft text-sm">
                            Label yang Anda lihat di panel admin. Kode pemicunya tetap <code>{template.key}</code> dan
                            tidak bisa diubah.
                        </p>
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="body">Isi Pesan</Label>
                        <Textarea
                            id="body"
                            ref={bodyRef}
                            value={data.body}
                            onChange={(e) => setData('body', e.target.value)}
                            rows={8}
                            required
                        />
                        <p className={sisa < 0 ? 'text-danger-600 text-sm font-medium' : 'text-ink-soft text-sm'}>
                            {sisa < 0
                                ? `Kelebihan ${Math.abs(sisa)} karakter dari batas ${maxLength}.`
                                : `Sisa ${sisa} dari ${maxLength} karakter.`}
                        </p>
                        <InputError message={errors.body} />
                    </div>

                    <div className="space-y-2">
                        <p className="text-sm font-medium">Sisipkan placeholder</p>
                        <div className="flex flex-wrap gap-2">
                            {placeholders.map((nama) => (
                                <Button
                                    key={nama}
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => sisipkan(nama)}
                                >
                                    {`{{${nama}}}`}
                                </Button>
                            ))}
                        </div>
                        <p className="text-ink-soft max-w-prose text-sm">
                            Hanya nama di atas yang dikenali. Selain itu akan ditolak saat menyimpan, supaya tidak ada
                            tulisan mentah yang terkirim ke pelanggan.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(nilai) => setData('is_active', nilai === true)}
                        />
                        <Label htmlFor="is_active">Tawarkan pesan ini di detail booking</Label>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Button type="submit" disabled={processing}>
                            Simpan Perubahan
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('admin.whatsapp-templates.index')}>Batal</Link>
                        </Button>
                    </div>
                </form>

                <section
                    aria-labelledby="judul-pratinjau"
                    className="border-line bg-surface shadow-card h-fit rounded-2xl border p-5"
                >
                    <h2 id="judul-pratinjau" className="mb-3 text-lg font-semibold">
                        Pratinjau
                    </h2>

                    <p className="border-line bg-canvas max-w-prose rounded-xl border p-3 text-sm whitespace-pre-line">
                        {pratinjau}
                    </p>

                    <p className="text-ink-muted mt-3 max-w-prose text-sm">
                        Memakai data contoh, bukan pelanggan sungguhan. Tanda bintang di WhatsApp menghasilkan huruf
                        tebal — <span className="font-medium">*seperti ini*</span>.
                    </p>
                </section>
            </div>
        </AdminLayout>
    );
}
