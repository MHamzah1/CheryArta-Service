import ConfirmDialog from '@/components/confirm-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type CarModelImage } from '@/types';
import { ArrowDown, ArrowUp, Check, GripVertical, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Props {
    image: CarModelImage;
    /** Posisi tampil, dipakai untuk menonaktifkan tombol di ujung daftar. */
    index: number;
    total: number;
    onMove: (dari: number, ke: number) => void;
    onPrimary: () => void;
    onDelete: () => void;
    onSaveAlt: (alt: string) => void;
}

/** Satu kartu gambar di galeri katalog. */
export default function GalleryItem({ image, index, total, onMove, onPrimary, onDelete, onSaveAlt }: Props) {
    const [menyuntingAlt, setMenyuntingAlt] = useState(false);
    const [draftAlt, setDraftAlt] = useState(image.alt);

    const simpan = () => {
        onSaveAlt(draftAlt);
        setMenyuntingAlt(false);
    };

    return (
        <div className="grid gap-3">
            <div className="relative">
                <img src={image.url} alt={image.alt} loading="lazy" className="bg-canvas aspect-video w-full rounded-lg object-cover" />

                {image.is_primary && (
                    <Badge className="absolute top-2 left-2">
                        <Star className="mr-1 h-3 w-3" aria-hidden="true" />
                        Utama
                    </Badge>
                )}

                <GripVertical className="text-ink-muted absolute top-2 right-2 h-5 w-5 cursor-grab" aria-hidden="true" />
            </div>

            {menyuntingAlt ? (
                <div className="grid gap-2">
                    <Label htmlFor={`alt-${image.id}`}>Teks alt</Label>
                    <Input
                        id={`alt-${image.id}`}
                        value={draftAlt}
                        maxLength={160}
                        autoFocus
                        onChange={(e) => setDraftAlt(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                simpan();
                            }
                        }}
                    />
                    <div className="flex gap-2">
                        <Button type="button" size="sm" onClick={simpan}>
                            <Check className="h-4 w-4" aria-hidden="true" />
                            Simpan
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => {
                                setDraftAlt(image.alt);
                                setMenyuntingAlt(false);
                            }}
                        >
                            Batal
                        </Button>
                    </div>
                </div>
            ) : (
                <button
                    type="button"
                    onClick={() => setMenyuntingAlt(true)}
                    className="text-ink-soft focus-visible:ring-brand-600 rounded-btn text-left text-sm underline focus-visible:ring-2"
                >
                    {image.alt}
                </button>
            )}

            <div className="flex flex-wrap gap-1">
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={() => onMove(index, index - 1)}
                    disabled={index === 0}
                    aria-label={`Naikkan urutan gambar: ${image.alt}`}
                >
                    <ArrowUp className="h-4 w-4" aria-hidden="true" />
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={() => onMove(index, index + 1)}
                    disabled={index === total - 1}
                    aria-label={`Turunkan urutan gambar: ${image.alt}`}
                >
                    <ArrowDown className="h-4 w-4" aria-hidden="true" />
                </Button>

                {!image.is_primary && (
                    <Button type="button" variant="outline" size="icon" onClick={onPrimary} aria-label={`Jadikan gambar utama: ${image.alt}`}>
                        <Star className="h-4 w-4" aria-hidden="true" />
                    </Button>
                )}

                <ConfirmDialog
                    trigger={
                        <Button type="button" variant="outline" size="icon" aria-label={`Hapus gambar: ${image.alt}`}>
                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                        </Button>
                    }
                    title="Hapus gambar ini?"
                    description="Gambar akan dihapus dari galeri dan dari penyimpanan berkas. Tindakan ini tidak bisa dibatalkan."
                    confirmLabel="Hapus Gambar"
                    onConfirm={onDelete}
                />
            </div>
        </div>
    );
}
