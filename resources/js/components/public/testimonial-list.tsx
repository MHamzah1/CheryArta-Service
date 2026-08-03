import { type PublicTestimonial } from '@/types';
import { Star } from 'lucide-react';

interface Props {
    testimonials: PublicTestimonial[];
}

/** Bintang selalu disertai teks "x dari 5" — warna saja bukan informasi (aturan 40). */
function Rating({ value }: { value: number }) {
    return (
        <p className="flex items-center gap-0.5" aria-label={`Penilaian ${value} dari 5 bintang`}>
            {[1, 2, 3, 4, 5].map((bintang) => (
                <Star
                    key={bintang}
                    aria-hidden="true"
                    className={
                        bintang <= value ? 'fill-gold-400 text-gold-400 h-4 w-4' : 'text-line h-4 w-4 fill-current'
                    }
                />
            ))}
            <span className="text-ink-muted ml-1.5 text-xs font-medium">{value} dari 5</span>
        </p>
    );
}

/**
 * Testimoni yang sudah disetujui admin (`is_published`).
 *
 * Nama yang tampil adalah nama pada tabel `testimonials` — diketik admin,
 * BUKAN `users.name`. Halaman publik tidak pernah menampilkan identitas
 * pelanggan yang terdaftar (docs/09 §9.4).
 */
export default function TestimonialList({ testimonials }: Props) {
    return (
        <ul className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {testimonials.map((testimonial) => (
                <li key={testimonial.id} className="bg-surface rounded-card shadow-card flex flex-col p-6">
                    <Rating value={testimonial.rating} />

                    <blockquote className="text-ink-soft mt-4 flex-1 text-sm">“{testimonial.content}”</blockquote>

                    <footer className="border-line mt-4 border-t pt-4">
                        <p className="text-ink text-sm font-semibold">{testimonial.customer_name}</p>
                        {testimonial.car_model && <p className="text-ink-muted text-xs">{testimonial.car_model}</p>}
                    </footer>
                </li>
            ))}
        </ul>
    );
}
