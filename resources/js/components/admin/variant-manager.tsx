import VariantForm from '@/components/admin/variant-form';
import ConfirmDialog from '@/components/confirm-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/format';
import { type CarModelVariant } from '@/types';
import { router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Props {
    carModelId: number;
    variants: CarModelVariant[];
}

/** Varian sebagai tabel di dalam halaman model (docs/07 §A4, roadmap 1.3.3). */
export default function VariantManager({ carModelId, variants }: Props) {
    const [sedangDisunting, setSedangDisunting] = useState<number | null>(null);
    const [menambah, setMenambah] = useState(false);

    const hapus = (variant: CarModelVariant) => {
        router.delete(route('admin.car-models.variants.destroy', [carModelId, variant.id]), { preserveScroll: true });
    };

    return (
        <section aria-labelledby="judul-varian" className="border-line bg-surface shadow-card rounded-2xl border p-5">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="judul-varian" className="text-lg font-semibold">
                        Varian
                    </h2>
                    <p className="text-ink-soft text-sm">Pilihan trim yang tersedia untuk model ini.</p>
                </div>

                {!menambah && (
                    <Button type="button" variant="outline" size="sm" onClick={() => setMenambah(true)}>
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Tambah Varian
                    </Button>
                )}
            </div>

            {menambah && (
                <div className="mb-4">
                    <VariantForm carModelId={carModelId} onDone={() => setMenambah(false)} />
                </div>
            )}

            {variants.length === 0 && !menambah ? (
                <p className="text-ink-soft text-sm">Belum ada varian. Model tetap bisa tampil di katalog tanpa varian.</p>
            ) : (
                <ul className="divide-line divide-y">
                    {variants.map((variant) => (
                        <li key={variant.id} className="py-3">
                            {sedangDisunting === variant.id ? (
                                <VariantForm carModelId={carModelId} variant={variant} onDone={() => setSedangDisunting(null)} />
                            ) : (
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p className="flex flex-wrap items-center gap-2 font-medium">
                                            {variant.name}
                                            {!variant.is_active && <Badge variant="secondary">Nonaktif</Badge>}
                                        </p>
                                        <p className="text-ink-soft text-sm">
                                            {variant.price === null ? 'Harga belum diisi' : formatRupiah(variant.price)}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setSedangDisunting(variant.id)}
                                            aria-label={`Ubah varian ${variant.name}`}
                                        >
                                            <Pencil className="h-4 w-4" aria-hidden="true" />
                                            Ubah
                                        </Button>

                                        <ConfirmDialog
                                            trigger={
                                                <Button type="button" variant="outline" size="sm" aria-label={`Hapus varian ${variant.name}`}>
                                                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                                                    Hapus
                                                </Button>
                                            }
                                            title="Hapus varian ini?"
                                            description={`Varian "${variant.name}" akan dihapus dari model ini.`}
                                            confirmLabel="Hapus Varian"
                                            onConfirm={() => hapus(variant)}
                                        />
                                    </div>
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
