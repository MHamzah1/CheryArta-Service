import { formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';

interface Props {
    subtotal: string | number;
    discount: string | number;
    tax: string | number;
    total: string | number;
    /**
     * Menandai bahwa angka ini belum final. Dipakai penyunting saat advisor
     * masih mengetik — lihat catatan tentang pratinjau di bawah.
     */
    preview?: boolean;
    className?: string;
}

/**
 * Ringkasan biaya: subtotal → diskon → pajak → total.
 *
 * Dipakai penyunting admin DAN halaman pelanggan, supaya satu invoice
 * menampilkan angka dengan urutan dan penulisan yang sama di kedua sisi —
 * pelanggan yang membandingkannya dengan PDF tidak boleh menemukan tiga bentuk
 * untuk satu nilai.
 *
 * **Komponen ini tidak menghitung apa pun.** Ia hanya menampilkan. Rumusnya
 * hidup di App\Services\InvoiceService — menghitung total di frontend adalah
 * temuan invoice sistem lama (.claude/rules/20).
 */
export default function InvoiceSummaryCard({ subtotal, discount, tax, total, preview, className }: Props) {
    const adaDiskon = Number(discount) > 0;
    const adaPajak = Number(tax) > 0;

    return (
        <dl className={cn('space-y-2 text-sm', className)}>
            <div className="flex items-center justify-between gap-4">
                <dt className="text-ink-soft">Subtotal</dt>
                <dd className="font-medium tabular-nums">{formatRupiah(subtotal)}</dd>
            </div>

            {adaDiskon && (
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-ink-soft">Diskon</dt>
                    <dd className="font-medium tabular-nums">− {formatRupiah(discount)}</dd>
                </div>
            )}

            {adaPajak && (
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-ink-soft">Pajak</dt>
                    <dd className="font-medium tabular-nums">{formatRupiah(tax)}</dd>
                </div>
            )}

            <div className="border-line flex items-center justify-between gap-4 border-t pt-2">
                <dt className="font-semibold">Total</dt>
                <dd className="text-brand-700 text-lg font-bold tabular-nums">{formatRupiah(total)}</dd>
            </div>

            {preview && (
                <p className="text-ink-muted pt-1 text-xs">
                    Perkiraan. Angka final dihitung server saat Anda menyimpan.
                </p>
            )}
        </dl>
    );
}
