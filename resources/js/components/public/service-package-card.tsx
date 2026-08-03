import { formatDurasi, formatHargaPaket } from '@/lib/format';
import { type PublicServicePackage } from '@/types';
import { Clock } from 'lucide-react';

interface Props {
    servicePackage: PublicServicePackage;
    /** Menampilkan label kategori; dimatikan di halaman yang sudah mengelompokkannya. */
    showCategory?: boolean;
}

/**
 * Kartu paket layanan. Harga dan durasi datang APA ADANYA dari server —
 * tidak ada perhitungan biaya di frontend (aturan 20 §Dilarang Keras).
 */
export default function ServicePackageCard({ servicePackage, showCategory = true }: Props) {
    return (
        <article className="bg-surface rounded-card shadow-card flex h-full flex-col p-6">
            {showCategory && (
                <p className="text-brand-700 mb-2 text-xs font-semibold tracking-wide uppercase">
                    {servicePackage.category_label}
                </p>
            )}

            <h3 className="text-ink text-lg font-semibold">{servicePackage.name}</h3>

            {servicePackage.description && (
                <p className="text-ink-soft mt-2 flex-1 text-sm">{servicePackage.description}</p>
            )}

            <dl className="border-line mt-4 space-y-2 border-t pt-4 text-sm">
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-ink-soft flex items-center gap-1.5">
                        <Clock className="h-4 w-4" aria-hidden="true" />
                        Estimasi
                    </dt>
                    <dd className="font-medium">{formatDurasi(servicePackage.estimated_duration_minutes)}</dd>
                </div>

                <div className="flex items-center justify-between gap-3">
                    <dt className="text-ink-soft">Biaya</dt>
                    {/* Token status TIDAK dipakai di sini: warnanya milik status
                        booking, dan meminjamnya untuk harga membuat peta warna
                        punya dua arti (aturan 40). */}
                    <dd className={servicePackage.is_free ? 'text-brand-700 font-semibold' : 'text-ink font-semibold'}>
                        {formatHargaPaket(servicePackage.price, servicePackage.is_free)}
                    </dd>
                </div>
            </dl>
        </article>
    );
}
