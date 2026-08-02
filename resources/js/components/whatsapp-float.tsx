import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';

const PESAN_AWAL = 'Halo Chery Arta, saya ingin bertanya tentang layanan servis.';

interface Props {
    /** Pesan awal khusus, mis. menyebut model mobil yang sedang dilihat. */
    message?: string;
}

/**
 * Tombol WhatsApp mengambang di seluruh halaman publik.
 * Mengarah ke nomor resmi bengkel — bukan nomor pelanggan (docs/08 §8.6).
 */
export function WhatsAppFloat({ message = PESAN_AWAL }: Props) {
    const { company } = usePage<SharedData>().props;

    if (!company?.wa_number) return null;

    const url = `https://wa.me/${company.wa_number}?text=${encodeURIComponent(message)}`;

    return (
        <a
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Hubungi Chery Arta lewat WhatsApp"
            className="fixed right-4 bottom-4 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-pop transition hover:scale-105 focus-visible:ring-2 focus-visible:ring-offset-2 md:right-6 md:bottom-6"
        >
            <MessageCircle className="h-7 w-7" aria-hidden="true" />
        </a>
    );
}
