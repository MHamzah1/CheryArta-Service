import { type CompanyAdvantage } from '@/types';
import { Award, Clock, ShieldCheck, Sparkles, Wrench, type LucideIcon } from 'lucide-react';

interface Props {
    advantages: CompanyAdvantage[];
}

/**
 * Peta nama ikon di `config/company.php` → komponen Lucide.
 *
 * Konfigurasi hanya menyimpan namanya; komponennya dipilih di sini supaya
 * daftar keunggulan bisa diubah tanpa menyentuh kode, dan nama yang belum
 * dikenal jatuh ke ikon cadangan alih-alih membuat halaman kosong.
 */
const IKON: Record<string, LucideIcon> = {
    award: Award,
    wrench: Wrench,
    clock: Clock,
    'shield-check': ShieldCheck,
};

/** 4 keunggulan dari sistem lama (docs/00 §Data perusahaan). */
export default function AdvantageGrid({ advantages }: Props) {
    return (
        <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {advantages.map((advantage) => {
                const Icon = IKON[advantage.icon] ?? Sparkles;

                return (
                    <li
                        key={advantage.title}
                        className="bg-surface rounded-card shadow-card flex items-center gap-3 p-5"
                    >
                        <span className="bg-brand-50 text-brand-700 flex h-11 w-11 shrink-0 items-center justify-center rounded-full">
                            <Icon className="h-5 w-5" aria-hidden="true" />
                        </span>
                        <span className="text-sm font-semibold">{advantage.title}</span>
                    </li>
                );
            })}
        </ul>
    );
}
