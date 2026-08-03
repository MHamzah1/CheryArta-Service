import DataTable, { type Column } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import { formatTanggalSingkat, formatTelepon } from '@/lib/format';
import { type CustomerRow, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, Search, Users, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface Props {
    customers: Paginated<CustomerRow>;
    filters: { cari: string | null };
}

const KOLOM: Column<CustomerRow>[] = [
    {
        key: 'nama',
        header: 'Nama',
        primary: true,
        cell: (row) => (
            <div>
                <p className="font-medium">{row.name}</p>
                <p className="text-ink-muted text-xs">{row.email}</p>
            </div>
        ),
    },
    { key: 'wa', header: 'WhatsApp', cell: (row) => formatTelepon(row.phone_wa) },
    { key: 'kendaraan', header: 'Kendaraan', align: 'right', cell: (row) => row.vehicles_count },
    { key: 'booking', header: 'Booking', align: 'right', cell: (row) => row.bookings_count },
    {
        key: 'terakhir',
        header: 'Servis terakhir',
        cell: (row) => (row.last_service_date ? formatTanggalSingkat(row.last_service_date) : 'Belum pernah'),
    },
    {
        key: 'status',
        header: 'Akun',
        cell: (row) => <Badge variant={row.is_active ? 'default' : 'secondary'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
];

export default function AdminCustomerIndex({ customers, filters }: Props) {
    const [cari, setCari] = useState(filters.cari ?? '');

    const kirim = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('admin.customers.index'), cari ? { cari } : {}, { preserveState: true, replace: true });
    };

    const bersihkan = () => {
        setCari('');
        router.get(route('admin.customers.index'), {}, { preserveState: true, replace: true });
    };

    const aksi = (row: CustomerRow) => (
        <Button variant="outline" size="sm" asChild>
            <Link href={route('admin.customers.show', row.id)}>
                <Eye className="h-4 w-4" aria-hidden="true" />
                Detail
            </Link>
        </Button>
    );

    return (
        <AdminLayout title="Customer" description="Seluruh pelanggan terdaftar beserta jumlah kendaraan dan riwayat servisnya.">
            <Head title="Customer" />

            <div className="grid gap-4">
                <form onSubmit={kirim} className="border-line bg-surface shadow-card grid gap-1.5 rounded-2xl border p-4">
                    <Label htmlFor="cari">Cari pelanggan</Label>
                    <div className="flex flex-wrap gap-2">
                        <Input
                            id="cari"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Nama, email, atau nomor WhatsApp"
                            autoComplete="off"
                            className="max-w-md"
                        />
                        <Button type="submit" variant="outline" aria-label="Cari pelanggan">
                            <Search className="h-4 w-4" aria-hidden="true" />
                        </Button>

                        {filters.cari !== null && (
                            <Button type="button" variant="ghost" onClick={bersihkan}>
                                <X className="h-4 w-4" aria-hidden="true" />
                                Bersihkan
                            </Button>
                        )}
                    </div>
                </form>

                {customers.data.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title={filters.cari !== null ? 'Tidak ada pelanggan yang cocok' : 'Belum ada pelanggan terdaftar'}
                        description={
                            filters.cari !== null
                                ? `Tidak ada pelanggan yang cocok dengan "${filters.cari}". Coba potongan nama atau nomor WhatsApp-nya saja.`
                                : 'Pelanggan yang mendaftar sendiri maupun yang dibuatkan lewat booking walk-in akan muncul di sini.'
                        }
                    />
                ) : (
                    <>
                        <p className="text-ink-soft text-sm">{customers.total} pelanggan terdaftar.</p>

                        <DataTable
                            caption="Daftar pelanggan"
                            columns={KOLOM}
                            rows={customers.data}
                            rowKey={(row) => row.id}
                            actions={aksi}
                        />

                        <Pagination meta={customers} />
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
