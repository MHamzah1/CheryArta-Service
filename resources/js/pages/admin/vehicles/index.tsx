import DataTable, { type Column } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import { formatPlat, formatTanggalSingkat } from '@/lib/format';
import { type AdminVehicleRow, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Car, Search, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface Props {
    vehicles: Paginated<AdminVehicleRow>;
    filters: { cari: string | null };
}

const KOLOM: Column<AdminVehicleRow>[] = [
    {
        key: 'plat',
        header: 'Plat',
        primary: true,
        cell: (row) => (
            <div>
                <p className="text-brand-700 font-bold">{formatPlat(row.plate_full)}</p>
                <p className="text-ink-muted text-xs">{row.display_model}</p>
            </div>
        ),
    },
    { key: 'tahun', header: 'Tahun', cell: (row) => row.year ?? '—' },
    {
        key: 'pemilik',
        header: 'Pemilik',
        cell: (row) => (
            <Link href={route('admin.customers.show', row.owner.id)} className="text-brand-700 underline">
                {row.owner.name}
            </Link>
        ),
    },
    {
        key: 'odometer',
        header: 'Odometer',
        align: 'right',
        cell: (row) => (row.last_odometer !== null ? `${row.last_odometer.toLocaleString('id-ID')} km` : '—'),
    },
    { key: 'servis', header: 'Servis', align: 'right', cell: (row) => row.services_count },
    {
        key: 'terakhir',
        header: 'Servis terakhir',
        cell: (row) => (row.last_service_date ? formatTanggalSingkat(row.last_service_date) : 'Belum pernah'),
    },
];

/**
 * A6 — Kendaraan terdaftar (docs/07 §A6).
 *
 * Daftar baca-saja. Penyuntingan kendaraan tetap milik pemiliknya — advisor
 * yang mengoreksi data orang lain diam-diam adalah perubahan tanpa jejak.
 */
export default function AdminVehicleIndex({ vehicles, filters }: Props) {
    const [cari, setCari] = useState(filters.cari ?? '');

    const kirim = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('admin.vehicles.index'), cari ? { cari } : {}, { preserveState: true, replace: true });
    };

    const bersihkan = () => {
        setCari('');
        router.get(route('admin.vehicles.index'), {}, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout
            title="Kendaraan"
            description="Seluruh unit terdaftar — untuk menjawab &ldquo;unit ini terakhir servis kapan?&rdquo;."
        >
            <Head title="Kendaraan" />

            <div className="grid gap-4">
                <form onSubmit={kirim} className="border-line bg-surface shadow-card grid gap-1.5 rounded-2xl border p-4">
                    <Label htmlFor="cari">Cari kendaraan</Label>
                    <div className="flex flex-wrap gap-2">
                        <Input
                            id="cari"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Plat nomor, model, atau nama pemilik"
                            autoComplete="off"
                            className="max-w-md"
                        />
                        <Button type="submit" variant="outline" aria-label="Cari kendaraan">
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

                {vehicles.data.length === 0 ? (
                    <EmptyState
                        icon={Car}
                        title={filters.cari !== null ? 'Tidak ada kendaraan yang cocok' : 'Belum ada kendaraan terdaftar'}
                        description={
                            filters.cari !== null
                                ? `Tidak ada unit yang cocok dengan "${filters.cari}". Plat boleh diketik dengan spasi maupun tanda hubung.`
                                : 'Kendaraan tercatat saat pelanggan menambahkannya sendiri, atau saat dibuatkan lewat booking walk-in.'
                        }
                    />
                ) : (
                    <>
                        <p className="text-ink-soft text-sm">{vehicles.total} unit terdaftar.</p>

                        <DataTable
                            caption="Daftar kendaraan terdaftar"
                            columns={KOLOM}
                            rows={vehicles.data}
                            rowKey={(row) => row.id}
                        />

                        <Pagination meta={vehicles} />
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
