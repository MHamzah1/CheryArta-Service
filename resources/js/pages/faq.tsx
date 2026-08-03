import EmptyState from '@/components/empty-state';
import FaqAccordion from '@/components/public/faq-accordion';
import SectionRoot from '@/components/public/section';
import Seo from '@/components/seo';
import PublicLayout from '@/layouts/public-layout';
import { type FaqGroup } from '@/types';
import { Link } from '@inertiajs/react';
import { HelpCircle, MessageCircle } from 'lucide-react';

interface Props {
    groups: FaqGroup[];
}

export default function Faq({ groups }: Props) {
    return (
        <PublicLayout>
            <Seo
                title="Pertanyaan Umum"
                description="Jawaban atas pertanyaan yang paling sering diajukan seputar booking servis, jadwal, biaya, dan proses pengerjaan di Chery Arta."
            />

            <section className="from-brand-900 to-brand-700 bg-gradient-to-br">
                <div className="mx-auto max-w-7xl px-4 py-12 md:py-16">
                    <h1 className="text-3xl font-extrabold text-white md:text-4xl">Pertanyaan Umum</h1>
                    <p className="text-brand-100 mt-3 max-w-prose">
                        Belum menemukan jawabannya? Kirim pertanyaan Anda lewat halaman kontak atau WhatsApp.
                    </p>
                </div>
            </section>

            {groups.length === 0 ? (
                <div className="mx-auto max-w-7xl px-4 py-16">
                    <EmptyState
                        icon={HelpCircle}
                        title="Belum ada pertanyaan umum"
                        description="Daftar pertanyaan sedang disusun. Sampai saat itu, silakan hubungi kami langsung."
                        action={
                            <Link
                                href={route('public.contact.show')}
                                className="bg-brand-700 rounded-btn hover:bg-brand-600 inline-flex min-h-11 items-center px-5 py-3 text-sm font-semibold text-white transition"
                            >
                                Hubungi Kami
                            </Link>
                        }
                    />
                </div>
            ) : (
                groups.map((group, indeks) => (
                    <SectionRoot key={group.label} title={group.label} tone={indeks % 2 === 0 ? 'surface' : 'canvas'}>
                        <div className="max-w-3xl">
                            <FaqAccordion faqs={group.faqs} defaultOpen={indeks === 0 ? 0 : -1} />
                        </div>
                    </SectionRoot>
                ))
            )}

            <SectionRoot
                title="Masih ada yang ingin ditanyakan?"
                description="Tim kami siap membantu pada jam operasional bengkel."
                tone={groups.length % 2 === 0 ? 'surface' : 'canvas'}
            >
                <Link
                    href={route('public.contact.show')}
                    className="bg-brand-700 rounded-btn hover:bg-brand-600 focus-visible:ring-brand-600 inline-flex min-h-11 items-center gap-2 px-6 py-3 text-sm font-semibold text-white transition focus-visible:ring-2 focus-visible:ring-offset-2"
                >
                    <MessageCircle className="h-4 w-4" aria-hidden="true" />
                    Kirim Pertanyaan
                </Link>
            </SectionRoot>
        </PublicLayout>
    );
}
