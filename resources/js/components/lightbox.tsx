import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect } from 'react';

export interface LightboxItem {
    /** URL ukuran penuh. */
    url: string;
    /** Wajib bermakna — aturan 20 §Aksesibilitas. */
    alt: string;
    caption?: string | null;
}

interface Props {
    items: LightboxItem[];
    /** Indeks yang sedang dibuka; `null` berarti tertutup. */
    index: number | null;
    onClose: () => void;
    onIndexChange: (index: number) => void;
}

/**
 * Penampil gambar layar penuh untuk galeri fasilitas dan katalog
 * (docs/06 §6.6).
 *
 * Fokus terperangkap dan `Esc` menutup — keduanya bawaan Radix Dialog dan
 * sengaja tidak dimatikan. Panah kiri/kanan berpindah gambar; tombolnya tetap
 * ada dan bisa diklik supaya tidak ada aksi yang hanya bisa lewat papan ketik.
 */
export default function Lightbox({ items, index, onClose, onIndexChange }: Props) {
    const terbuka = index !== null && items.length > 0;

    useEffect(() => {
        if (!terbuka) return;

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'ArrowRight') onIndexChange((index + 1) % items.length);
            if (event.key === 'ArrowLeft') onIndexChange((index - 1 + items.length) % items.length);
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [terbuka, index, items.length, onIndexChange]);

    if (!terbuka) return null;

    const item = items[index];
    const banyak = items.length > 1;

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-4xl gap-3 p-4 sm:p-6">
                <DialogTitle className="text-base">{item.caption ?? item.alt}</DialogTitle>
                <DialogDescription className="sr-only">
                    Gambar {index + 1} dari {items.length}. Gunakan panah kiri dan kanan untuk berpindah.
                </DialogDescription>

                <div className="relative">
                    <img src={item.url} alt={item.alt} className="rounded-card max-h-[70vh] w-full object-contain" />

                    {banyak && (
                        <>
                            <button
                                type="button"
                                onClick={() => onIndexChange((index - 1 + items.length) % items.length)}
                                aria-label="Gambar sebelumnya"
                                className="text-ink shadow-card focus-visible:ring-brand-600 absolute top-1/2 left-2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 transition hover:bg-white focus-visible:ring-2"
                            >
                                <ChevronLeft className="h-5 w-5" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                onClick={() => onIndexChange((index + 1) % items.length)}
                                aria-label="Gambar berikutnya"
                                className="text-ink shadow-card focus-visible:ring-brand-600 absolute top-1/2 right-2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 transition hover:bg-white focus-visible:ring-2"
                            >
                                <ChevronRight className="h-5 w-5" aria-hidden="true" />
                            </button>
                        </>
                    )}
                </div>

                {banyak && (
                    <p className="text-ink-muted text-center text-sm">
                        {index + 1} / {items.length}
                    </p>
                )}
            </DialogContent>
        </Dialog>
    );
}
