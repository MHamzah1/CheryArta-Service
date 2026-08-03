import { type PublicFaq } from '@/types';
import { ChevronDown } from 'lucide-react';
import { useId, useState } from 'react';

interface Props {
    faqs: PublicFaq[];
    /** Indeks yang terbuka saat halaman dimuat; -1 berarti semuanya tertutup. */
    defaultOpen?: number;
}

/**
 * Daftar pertanyaan umum yang bisa dibuka-tutup (docs/06 §6.5).
 *
 * Ditulis tangan alih-alih memakai komponen accordion: pasangan
 * `aria-expanded` + `aria-controls` + region sudah cukup, dan tidak ada
 * kebutuhan animasi atau penguncian fokus yang menuntut Radix di sini.
 * Jawaban dirender sebagai teks — tidak pernah sebagai HTML mentah (temuan S5).
 */
export default function FaqAccordion({ faqs, defaultOpen = -1 }: Props) {
    const [terbuka, setTerbuka] = useState(defaultOpen);
    const idDasar = useId();

    return (
        <ul className="divide-line border-line bg-surface rounded-card divide-y border">
            {faqs.map((faq, indeks) => {
                const isOpen = terbuka === indeks;
                const idJawaban = `${idDasar}-jawaban-${faq.id}`;

                return (
                    <li key={faq.id}>
                        <h3>
                            <button
                                type="button"
                                onClick={() => setTerbuka(isOpen ? -1 : indeks)}
                                aria-expanded={isOpen}
                                aria-controls={idJawaban}
                                className="hover:bg-brand-50/60 focus-visible:ring-brand-600 flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition focus-visible:ring-2 focus-visible:ring-inset"
                            >
                                <span className="text-ink text-base font-medium">{faq.question}</span>
                                <ChevronDown
                                    aria-hidden="true"
                                    className={`text-ink-muted h-5 w-5 shrink-0 transition-transform duration-200 motion-reduce:transition-none ${
                                        isOpen ? 'rotate-180' : ''
                                    }`}
                                />
                            </button>
                        </h3>

                        <div id={idJawaban} hidden={!isOpen} className="text-ink-soft px-5 pb-5 text-sm">
                            {faq.answer}
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}
