import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Clock, ExternalLink, Mail, MapPin, Phone } from 'lucide-react';

/**
 * Lokasi, kontak, dan jam operasional (docs/06 §6.5).
 *
 * Seluruh isinya dari shared props `company` — alamat dan telepon TIDAK BOLEH
 * ditulis langsung di JSX (aturan 00).
 *
 * Peta sengaja berupa tautan ke Google Maps, bukan iframe: menanam iframe
 * pihak ketiga menabrak kebijakan CSP yang dikerjakan di F2.5, dan tautan
 * membuka aplikasi peta ponsel yang justru lebih berguna di jalan.
 */
export default function LocationSection() {
    const { company } = usePage<SharedData>().props;

    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <div className="bg-surface rounded-card shadow-card p-6">
                <h3 className="text-ink text-lg font-semibold">Alamat Bengkel</h3>

                <p className="text-ink-soft mt-3 flex gap-2 text-sm">
                    <MapPin className="text-brand-700 mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                    <span>{company.address.full}</span>
                </p>

                <ul className="mt-4 space-y-3 text-sm">
                    <li className="flex gap-2">
                        <Phone className="text-brand-700 mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                        <span className="text-ink-soft">
                            <a href={`tel:${company.phones.landline.replace(/\D+/g, '')}`} className="hover:underline">
                                {company.phones.landline}
                            </a>
                            <br />
                            <a href={`tel:${company.phones.mobile.replace(/\D+/g, '')}`} className="hover:underline">
                                {company.phones.mobile}
                            </a>
                        </span>
                    </li>
                    <li className="flex gap-2">
                        <Mail className="text-brand-700 mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                        <a href={`mailto:${company.email}`} className="text-ink-soft break-all hover:underline">
                            {company.email}
                        </a>
                    </li>
                </ul>

                {company.social.maps_url && (
                    <a
                        href={company.social.maps_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="bg-brand-700 rounded-btn hover:bg-brand-600 focus-visible:ring-brand-600 mt-6 inline-flex min-h-11 items-center gap-2 px-5 py-3 text-sm font-semibold text-white transition focus-visible:ring-2 focus-visible:ring-offset-2"
                    >
                        Buka di Google Maps
                        <ExternalLink className="h-4 w-4" aria-hidden="true" />
                    </a>
                )}
            </div>

            <div className="bg-surface rounded-card shadow-card p-6">
                <h3 className="text-ink text-lg font-semibold">Jam Operasional</h3>

                <ul className="mt-3 space-y-3 text-sm">
                    {company.operating_hours_text.map((baris) => (
                        <li key={baris} className="border-line text-ink-soft flex gap-2 border-b pb-3 last:border-0">
                            <Clock className="text-brand-700 mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                            <span>{baris}</span>
                        </li>
                    ))}
                </ul>

                <p className="text-ink-muted mt-4 text-sm">
                    Booking servis dibuat minimal satu hari sebelum kedatangan. Pilih jam yang tersedia lewat form
                    booking — sisa kuota tiap jam ditampilkan langsung di sana.
                </p>
            </div>
        </div>
    );
}
