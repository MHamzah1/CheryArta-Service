import InputError from '@/components/input-error';
import PhoneInput from '@/components/phone-input';
import LocationSection from '@/components/public/location-section';
import SectionRoot from '@/components/public/section';
import Seo from '@/components/seo';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import PublicLayout from '@/layouts/public-layout';
import { type SharedData } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import { type FormEvent } from 'react';

interface ContactForm {
    name: string;
    email: string;
    phone: string;
    subject: string;
    message: string;
    [key: string]: string;
}

export default function Kontak() {
    const { company } = usePage<SharedData>().props;

    const { data, setData, post, processing, errors, reset } = useForm<ContactForm>({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
    });

    const kirim = (event: FormEvent) => {
        event.preventDefault();

        post(route('public.contact.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const waUrl = `https://wa.me/${company.wa_number}?text=${encodeURIComponent(
        'Halo Chery Arta, saya ingin bertanya tentang layanan servis.',
    )}`;

    return (
        <PublicLayout>
            <Seo
                title="Kontak"
                description={`Hubungi ${company.name} di ${company.address.city}. Alamat, nomor telepon, jam operasional, dan form pesan.`}
            />

            <section className="from-brand-900 to-brand-700 bg-gradient-to-br">
                <div className="mx-auto max-w-7xl px-4 py-12 md:py-16">
                    <h1 className="text-3xl font-extrabold text-white md:text-4xl">Hubungi Kami</h1>
                    <p className="text-brand-100 mt-3 max-w-prose">
                        Untuk keperluan yang mendesak, WhatsApp adalah jalur tercepat — pesan Anda langsung diterima
                        petugas kami pada jam operasional.
                    </p>

                    <a
                        href={waUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="rounded-btn text-ink bg-gold-400 hover:bg-gold-300 focus-visible:ring-brand-600 mt-6 inline-flex min-h-11 items-center gap-2 px-6 py-3 text-sm font-semibold transition focus-visible:ring-2 focus-visible:ring-offset-2"
                    >
                        <MessageCircle className="h-4 w-4" aria-hidden="true" />
                        Chat via WhatsApp
                    </a>
                </div>
            </section>

            <SectionRoot title="Alamat & Jam Operasional" tone="canvas">
                <LocationSection />
            </SectionRoot>

            <SectionRoot
                title="Kirim Pesan"
                description="Isi form ini bila keperluan Anda tidak mendesak. Kami membacanya pada jam kerja dan membalas lewat email atau WhatsApp yang Anda tuliskan."
            >
                <form onSubmit={kirim} className="bg-surface rounded-card shadow-card max-w-2xl space-y-5 p-6">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                name="name"
                                autoComplete="name"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                aria-invalid={Boolean(errors.name)}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                autoComplete="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                aria-invalid={Boolean(errors.email)}
                                required
                            />
                            <InputError message={errors.email} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phone">
                            Nomor WhatsApp <span className="text-ink-muted font-normal">(opsional)</span>
                        </Label>
                        <PhoneInput
                            id="phone"
                            name="phone"
                            value={data.phone}
                            onChange={(value) => setData('phone', value)}
                            aria-invalid={Boolean(errors.phone)}
                        />
                        <InputError message={errors.phone} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="subject">Subjek</Label>
                        <Input
                            id="subject"
                            name="subject"
                            value={data.subject}
                            onChange={(event) => setData('subject', event.target.value)}
                            aria-invalid={Boolean(errors.subject)}
                            required
                        />
                        <InputError message={errors.subject} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="message">Pesan</Label>
                        <Textarea
                            id="message"
                            name="message"
                            rows={6}
                            value={data.message}
                            onChange={(event) => setData('message', event.target.value)}
                            aria-invalid={Boolean(errors.message)}
                            required
                        />
                        <InputError message={errors.message} />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="bg-brand-700 rounded-btn hover:bg-brand-600 focus-visible:ring-brand-600 min-h-11 w-full px-6 py-3 text-sm font-semibold text-white transition focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-60 sm:w-auto"
                    >
                        {processing ? 'Mengirim…' : 'Kirim Pesan'}
                    </button>
                </form>
            </SectionRoot>
        </PublicLayout>
    );
}
