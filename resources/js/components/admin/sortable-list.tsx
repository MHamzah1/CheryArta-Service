import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { ChevronDown, ChevronUp, GripVertical } from 'lucide-react';
import { type ReactNode, useEffect, useState } from 'react';

interface Item {
    id: number;
}

interface Props<T extends Item> {
    items: T[];
    /** Rute PUT yang menerima `{ ids: [...] }` dalam urutan baru. */
    reorderUrl: string;
    children: (item: T) => ReactNode;
    /** Dipakai di aria-label tombol naik/turun, mis. "fasilitas Ruang Tunggu". */
    label: (item: T) => string;
}

/**
 * Daftar yang bisa disusun ulang, dipakai fasilitas, FAQ, dan testimoni.
 *
 * Menyeret memakai HTML5 `draggable` tanpa pustaka apa pun — pola yang sama
 * dengan galeri katalog, jadi tidak ada dependensi baru untuk kebutuhan yang
 * sudah pernah dipecahkan.
 *
 * **Tombol naik/turun bukan pelengkap.** Seret-lepas HTML5 tidak bisa
 * dijalankan dengan papan ketik sama sekali, sehingga tanpa tombol itu fitur
 * pengurutan hanya tersedia bagi pengguna tetikus (.claude/rules/20
 * §Aksesibilitas). Keduanya menulis ke endpoint yang sama.
 */
export default function SortableList<T extends Item>({ items, reorderUrl, children, label }: Props<T>) {
    const [urutan, setUrutan] = useState(items);
    const [diseret, setDiseret] = useState<number | null>(null);

    // Props menang setiap kali server mengirim daftar baru — tanpa ini,
    // menambah atau menghapus satu baris tidak terlihat sampai halaman dimuat
    // ulang penuh.
    useEffect(() => setUrutan(items), [items]);

    const simpan = (baru: T[]) => {
        setUrutan(baru);
        router.put(
            reorderUrl,
            { ids: baru.map((item) => item.id) },
            { preserveScroll: true, preserveState: true },
        );
    };

    const pindah = (dari: number, ke: number) => {
        if (ke < 0 || ke >= urutan.length || dari === ke) return;

        const baru = [...urutan];
        const [item] = baru.splice(dari, 1);
        baru.splice(ke, 0, item);
        simpan(baru);
    };

    return (
        <ul className="space-y-2">
            {urutan.map((item, index) => (
                <li
                    key={item.id}
                    draggable
                    onDragStart={() => setDiseret(index)}
                    onDragEnd={() => setDiseret(null)}
                    onDragOver={(event) => event.preventDefault()}
                    onDrop={() => {
                        if (diseret !== null) pindah(diseret, index);
                        setDiseret(null);
                    }}
                    className={cn(
                        'border-line bg-surface shadow-card flex items-start gap-3 rounded-2xl border p-4',
                        diseret === index && 'opacity-50',
                    )}
                >
                    <GripVertical className="text-ink-muted mt-1 h-5 w-5 shrink-0 cursor-grab" aria-hidden="true" />

                    <div className="min-w-0 flex-1">{children(item)}</div>

                    <div className="flex shrink-0 flex-col gap-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="h-8 w-8"
                            disabled={index === 0}
                            aria-label={`Pindahkan ${label(item)} ke atas`}
                            onClick={() => pindah(index, index - 1)}
                        >
                            <ChevronUp className="h-4 w-4" aria-hidden="true" />
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="h-8 w-8"
                            disabled={index === urutan.length - 1}
                            aria-label={`Pindahkan ${label(item)} ke bawah`}
                            onClick={() => pindah(index, index + 1)}
                        >
                            <ChevronDown className="h-4 w-4" aria-hidden="true" />
                        </Button>
                    </div>
                </li>
            ))}
        </ul>
    );
}
