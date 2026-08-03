import ConfirmDialog from '@/components/confirm-dialog';
import DataTable, { type Column } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { formatDurasi, formatHargaPaket } from '@/lib/format';
import { type Paginated, type ServicePackageRow } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Wrench } from 'lucide-react';

interface Props {
    packages: Paginated<ServicePackageRow>;
}

const KOLOM: Column<ServicePackageRow>[] = [
    {
        key: 'name',
        header: 'Paket',
        primary: true,
        cell: (row) => (
            <div>
                <p className="font-medium">{row.name}</p>
                <p className="text-ink-muted font-mono text-xs">{row.code}</p>
            </div>
        ),
    },
    { key: 'category', header: 'Kategori', cell: (row) => row.category_label },
    {
        key: 'series',
        header: 'Seri',
        cell: (row) => (row.applicable_series.length === 0 ? 'Semua model' : row.applicable_series.join(', ')),
    },
    { key: 'duration', header: 'Durasi', cell: (row) => formatDurasi(row.estimated_duration_minutes) },
    { key: 'price', header: 'Harga', align: 'right', cell: (row) => formatHargaPaket(row.price, row.is_free) },
    {
        key: 'status',
        header: 'Status',
        cell: (row) => <Badge variant={row.is_active ? 'default' : 'secondary'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
];

export default function ServicePackageIndex({ packages }: Props) {
    const hapus = (paket: ServicePackageRow) => {
        router.delete(route('admin.service-packages.destroy', paket.id), { preserveScroll: true });
    };

    const aksi = (row: ServicePackageRow) => (
        <>
            <Button variant="outline" size="sm" asChild>
                <Link href={route('admin.service-packages.edit', row.id)}>
                    <Pencil className="h-4 w-4" aria-hidden="true" />
                    Ubah
                </Link>
            </Button>

            {/* Tombol hapus hanya muncul bila server mengizinkan — paket yang
                sudah dipakai booking tetap ditolak di sisi server. */}
            {row.can_delete && (
                <ConfirmDialog
                    trigger={
                        <Button variant="outline" size="sm" aria-label={`Hapus paket ${row.name}`}>
                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                            Hapus
                        </Button>
                    }
                    title="Hapus paket layanan ini?"
                    description={`Paket "${row.name}" akan dihapus dari daftar pilihan booking. Tindakan ini tidak bisa dibatalkan.`}
                    confirmLabel="Hapus Paket"
                    onConfirm={() => hapus(row)}
                />
            )}
        </>
    );

    return (
        <AdminLayout
            title="Paket Layanan"
            description="Pilihan pekerjaan yang muncul di form booking dan menjadi dasar estimasi biaya invoice."
            actions={
                <Button asChild>
                    <Link href={route('admin.service-packages.create')}>
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Tambah Paket
                    </Link>
                </Button>
            }
        >
            <Head title="Paket Layanan" />

            {packages.data.length === 0 ? (
                <EmptyState
                    icon={Wrench}
                    title="Belum ada paket layanan"
                    description="Tambahkan paket layanan supaya pelanggan punya pilihan pekerjaan saat memesan servis."
                    action={
                        <Button asChild>
                            <Link href={route('admin.service-packages.create')}>
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                Tambah Paket
                            </Link>
                        </Button>
                    }
                />
            ) : (
                <>
                    <DataTable caption="Daftar paket layanan" columns={KOLOM} rows={packages.data} rowKey={(row) => row.id} actions={aksi} />
                    <Pagination meta={packages} />
                </>
            )}
        </AdminLayout>
    );
}
