import DataTable, { type Column } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { formatRupiah, formatTanggalSingkat } from '@/lib/format';
import { type AdminInvoiceRow, type InvoiceFilters, type Paginated, type SelectOption } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FileText, Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface Props {
    invoices: Paginated<AdminInvoiceRow>;
    filters: InvoiceFilters;
    statusOptions: SelectOption[];
}

const KOLOM: Column<AdminInvoiceRow>[] = [
    {
        key: 'number',
        header: 'Nomor',
        primary: true,
        cell: (row) => (
            <div>
                <p className="font-medium">{row.invoice_number ?? 'Draft'}</p>
                <p className="text-ink-muted text-xs">{row.booking_code}</p>
            </div>
        ),
    },
    {
        key: 'issued',
        header: 'Terbit',
        cell: (row) => (row.issued_at ? formatTanggalSingkat(row.issued_at) : '—'),
    },
    {
        key: 'customer',
        header: 'Pelanggan',
        cell: (row) => (
            <div>
                <p>{row.customer_name}</p>
                <p className="text-ink-muted text-xs">{row.vehicle_plate}</p>
            </div>
        ),
    },
    {
        key: 'total',
        header: 'Total',
        align: 'right',
        cell: (row) => <span className="font-medium tabular-nums">{formatRupiah(row.total)}</span>,
    },
    {
        key: 'status',
        header: 'Status',
        cell: (row) => <StatusBadge status={row.status} kind="invoice" />,
    },
];

/**
 * A8 — daftar invoice (docs/07 §A8).
 *
 * Tidak ada tombol "Buat Invoice" di halaman ini: invoice selalu lahir dari
 * sebuah booking yang sudah selesai (keputusan grill Q3), jadi pintu masuknya
 * adalah detail booking. Tombol di sini akan menjanjikan sesuatu yang tidak
 * bisa ditepati tanpa memilih booking lebih dulu.
 */
export default function InvoiceIndex({ invoices, filters, statusOptions }: Props) {
    const [cari, setCari] = useState(filters.cari ?? '');

    const terapkan = (perubahan: Partial<InvoiceFilters>) => {
        router.get(
            route('admin.invoices.index'),
            { ...filters, ...perubahan },
            { preserveState: true, replace: true },
        );
    };

    const cariSubmit = (event: FormEvent) => {
        event.preventDefault();
        terapkan({ cari: cari.trim() === '' ? null : cari.trim() });
    };

    return (
        <AdminLayout
            title="Invoice"
            description="Tagihan untuk booking yang sudah selesai. Invoice baru dibuat dari halaman detail booking."
        >
            <Head title="Invoice" />

            <div className="mb-6 grid gap-3">
                <form onSubmit={cariSubmit} className="flex flex-wrap gap-2" role="search">
                    <Input
                        value={cari}
                        onChange={(event) => setCari(event.target.value)}
                        placeholder="Nomor invoice, kode booking, atau nama pelanggan"
                        aria-label="Cari invoice"
                        className="max-w-sm"
                    />
                    <Button type="submit" variant="outline">
                        <Search className="h-4 w-4" aria-hidden="true" />
                        Cari
                    </Button>
                </form>

                <div className="flex flex-wrap gap-2" role="group" aria-label="Saring menurut status">
                    <Button
                        variant={filters.status === null ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => terapkan({ status: null })}
                    >
                        Semua Status
                    </Button>
                    {statusOptions.map((opsi) => (
                        <Button
                            key={opsi.value}
                            variant={filters.status === opsi.value ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => terapkan({ status: opsi.value })}
                        >
                            {opsi.label}
                        </Button>
                    ))}
                </div>
            </div>

            {invoices.data.length === 0 ? (
                <EmptyState
                    icon={FileText}
                    title="Belum ada invoice yang cocok"
                    description="Invoice terbit otomatis saat booking ditandai selesai. Ubah saringan, atau buka booking yang sudah selesai untuk membuatnya."
                />
            ) : (
                <DataTable
                    columns={KOLOM}
                    rows={invoices.data}
                    rowKey={(row) => row.id}
                    actions={(row) => (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('admin.invoices.show', row.id)}>Buka</Link>
                        </Button>
                    )}
                    caption="Daftar invoice"
                />
            )}

            <Pagination meta={invoices} />
        </AdminLayout>
    );
}
