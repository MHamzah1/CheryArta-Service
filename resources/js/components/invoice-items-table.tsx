import { Badge } from '@/components/ui/badge';
import { formatRupiah } from '@/lib/format';
import { type InvoiceItem } from '@/types';

interface Props {
    items: InvoiceItem[];
}

/** "1", "0,5", "2,25" — nol di belakang koma dibuang supaya kolomnya tidak ramai. */
function formatQty(qty: string): string {
    const angka = Number(qty);

    return Number.isNaN(angka) ? qty : angka.toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

/**
 * Rincian invoice dalam bentuk baca-saja.
 *
 * Dipakai halaman pelanggan dan penyunting admin setelah invoice diterbitkan.
 * Versi yang bisa disunting ada di `components/admin/invoice-item-rows.tsx` —
 * sengaja terpisah, karena yang satu tidak boleh punya satu pun kolom isian.
 */
export default function InvoiceItemsTable({ items }: Props) {
    if (items.length === 0) {
        return <p className="text-ink-muted text-sm">Belum ada rincian biaya.</p>;
    }

    return (
        <>
            {/* Layar sedang ke atas — tabel. Dibungkus wadahnya sendiri supaya
                badan halaman tidak ikut menggeser horizontal (.claude/rules/20). */}
            <div className="border-line hidden overflow-x-auto rounded-xl border md:block">
                <table className="w-full text-sm">
                    <caption className="sr-only">Rincian biaya servis</caption>
                    <thead className="border-line text-ink-soft border-b text-left">
                        <tr>
                            <th scope="col" className="px-4 py-3 font-semibold">
                                Keterangan
                            </th>
                            <th scope="col" className="px-4 py-3 font-semibold">
                                Jenis
                            </th>
                            <th scope="col" className="px-4 py-3 text-right font-semibold">
                                Qty
                            </th>
                            <th scope="col" className="px-4 py-3 text-right font-semibold">
                                Harga
                            </th>
                            <th scope="col" className="px-4 py-3 text-right font-semibold">
                                Jumlah
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-line divide-y">
                        {items.map((item) => (
                            <tr key={item.id}>
                                <td className="px-4 py-3">{item.description}</td>
                                <td className="px-4 py-3">
                                    <Badge variant="secondary">{item.type_label}</Badge>
                                </td>
                                <td className="px-4 py-3 text-right tabular-nums">{formatQty(item.qty)}</td>
                                <td className="px-4 py-3 text-right tabular-nums">{formatRupiah(item.unit_price)}</td>
                                <td className="px-4 py-3 text-right font-medium tabular-nums">{formatRupiah(item.subtotal)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Layar kecil — kartu, karena lima kolom tidak muat di 360px. */}
            <ul className="grid gap-3 md:hidden">
                {items.map((item) => (
                    <li key={item.id} className="border-line rounded-xl border p-3">
                        <div className="flex items-start justify-between gap-3">
                            <p className="font-medium">{item.description}</p>
                            <Badge variant="secondary">{item.type_label}</Badge>
                        </div>
                        <p className="text-ink-soft mt-1 text-sm">
                            {formatQty(item.qty)} × {formatRupiah(item.unit_price)}
                        </p>
                        <p className="mt-1 text-sm font-semibold tabular-nums">{formatRupiah(item.subtotal)}</p>
                    </li>
                ))}
            </ul>
        </>
    );
}
