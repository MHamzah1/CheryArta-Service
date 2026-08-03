import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

export interface Column<T> {
    /** Dipakai sebagai key React, bukan sebagai nama field. */
    key: string;
    header: string;
    cell: (row: T) => ReactNode;
    /** Kolom judul kartu di layar kecil. Tepat satu kolom sebaiknya ditandai. */
    primary?: boolean;
    align?: 'left' | 'right';
    /** Sembunyikan kolom ini pada tampilan kartu — mis. thumbnail. */
    hideOnCard?: boolean;
}

interface Props<T> {
    columns: Column<T>[];
    rows: T[];
    rowKey: (row: T) => string | number;
    /** Tombol aksi per baris; ditaruh di kolom terakhir / dasar kartu. */
    actions?: (row: T) => ReactNode;
    /** Keterangan tabel untuk pembaca layar. */
    caption: string;
}

/**
 * Tabel daftar yang berubah menjadi kartu di bawah `md` (docs/06 §6.6).
 *
 * Badan halaman tidak boleh menggeser horizontal, jadi tabelnya dibungkus
 * wadah `overflow-x-auto` sendiri (.claude/rules/20).
 *
 * Paginasi TIDAK diurus di sini — pasangkan dengan `<Pagination />` supaya
 * tabel tetap bisa dipakai untuk daftar yang tidak dipaginasi.
 */
export default function DataTable<T>({ columns, rows, rowKey, actions, caption }: Props<T>) {
    const kolomKartu = columns.filter((column) => !column.hideOnCard);
    const judul = columns.find((column) => column.primary);

    return (
        <>
            {/* Layar sedang ke atas — tabel */}
            <div className="border-line bg-surface shadow-card hidden overflow-x-auto rounded-2xl border md:block">
                <table className="w-full text-sm">
                    <caption className="sr-only">{caption}</caption>
                    <thead className="border-line text-ink-soft border-b text-left">
                        <tr>
                            {columns.map((column) => (
                                <th key={column.key} scope="col" className={cn('px-4 py-3 font-semibold', column.align === 'right' && 'text-right')}>
                                    {column.header}
                                </th>
                            ))}
                            {actions && (
                                <th scope="col" className="px-4 py-3 text-right font-semibold">
                                    Aksi
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody className="divide-line divide-y">
                        {rows.map((row) => (
                            <tr key={rowKey(row)} className="hover:bg-canvas align-middle">
                                {columns.map((column) => (
                                    <td key={column.key} className={cn('px-4 py-3', column.align === 'right' && 'text-right')}>
                                        {column.cell(row)}
                                    </td>
                                ))}
                                {actions && (
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap justify-end gap-2">{actions(row)}</div>
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Layar kecil — kartu */}
            <ul className="grid gap-3 md:hidden">
                {rows.map((row) => (
                    <li key={rowKey(row)} className="border-line bg-surface shadow-card rounded-2xl border p-4">
                        {judul && <p className="mb-3 font-semibold">{judul.cell(row)}</p>}

                        <dl className="grid gap-2 text-sm">
                            {kolomKartu
                                .filter((column) => !column.primary)
                                .map((column) => (
                                    <div key={column.key} className="flex items-start justify-between gap-4">
                                        <dt className="text-ink-soft">{column.header}</dt>
                                        <dd className="text-right">{column.cell(row)}</dd>
                                    </div>
                                ))}
                        </dl>

                        {actions && <div className="border-line mt-4 flex flex-wrap gap-2 border-t pt-3">{actions(row)}</div>}
                    </li>
                ))}
            </ul>
        </>
    );
}
