import ConfirmDialog from '@/components/confirm-dialog';
import DataTable, { type Column } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { formatRupiah } from '@/lib/format';
import { type CarModelRow, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Car, ImageOff, Pencil, Plus, Trash2 } from 'lucide-react';

interface Props {
    carModels: Paginated<CarModelRow>;
}

const KOLOM: Column<CarModelRow>[] = [
    {
        key: 'thumbnail',
        header: 'Gambar',
        hideOnCard: true,
        cell: (row) =>
            row.thumbnail_url ? (
                <img src={row.thumbnail_url} alt={row.thumbnail_alt ?? ''} loading="lazy" className="bg-canvas h-12 w-20 rounded-lg object-cover" />
            ) : (
                <span className="bg-canvas text-ink-muted flex h-12 w-20 items-center justify-center rounded-lg">
                    <ImageOff className="h-4 w-4" aria-hidden="true" />
                    <span className="sr-only">Belum ada gambar</span>
                </span>
            ),
    },
    {
        key: 'name',
        header: 'Model',
        primary: true,
        cell: (row) => (
            <div>
                <p className="font-medium">{row.name}</p>
                <p className="text-ink-muted text-xs">
                    {row.category_label} · {row.fuel_type_label}
                </p>
            </div>
        ),
    },
    { key: 'series', header: 'Kode Seri', cell: (row) => row.series_code ?? '—' },
    {
        key: 'price',
        header: 'Harga Mulai',
        align: 'right',
        cell: (row) => (row.price_start === null ? 'Hubungi kami' : formatRupiah(row.price_start)),
    },
    {
        key: 'isi',
        header: 'Varian / Gambar',
        cell: (row) => `${row.variants_count} varian · ${row.images_count} gambar`,
    },
    {
        key: 'status',
        header: 'Status',
        cell: (row) => <Badge variant={row.is_active ? 'default' : 'secondary'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
];

export default function CarModelIndex({ carModels }: Props) {
    const hapus = (model: CarModelRow) => {
        router.delete(route('admin.car-models.destroy', model.id), { preserveScroll: true });
    };

    const aksi = (row: CarModelRow) => (
        <>
            <Button variant="outline" size="sm" asChild>
                <Link href={route('admin.car-models.edit', row.id)}>
                    <Pencil className="h-4 w-4" aria-hidden="true" />
                    Ubah
                </Link>
            </Button>

            {/* Model yang sudah dipakai kendaraan pelanggan tidak bisa dihapus;
                keputusannya dihitung server dan dikirim sebagai can_delete. */}
            {row.can_delete && (
                <ConfirmDialog
                    trigger={
                        <Button variant="outline" size="sm" aria-label={`Hapus model ${row.name}`}>
                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                            Hapus
                        </Button>
                    }
                    title="Hapus model ini?"
                    description={`Model "${row.name}" beserta varian dan galerinya akan dihapus. Tindakan ini tidak bisa dibatalkan.`}
                    confirmLabel="Hapus Model"
                    onConfirm={() => hapus(row)}
                />
            )}
        </>
    );

    return (
        <AdminLayout
            title="Katalog Mobil"
            description="Model, varian, dan galeri yang tampil di halaman katalog publik."
            actions={
                <Button asChild>
                    <Link href={route('admin.car-models.create')}>
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Tambah Model
                    </Link>
                </Button>
            }
        >
            <Head title="Katalog Mobil" />

            {carModels.data.length === 0 ? (
                <EmptyState
                    icon={Car}
                    title="Belum ada model di katalog"
                    description="Tambahkan model mobil supaya pelanggan bisa melihatnya di halaman katalog dan memilihnya saat mendaftarkan kendaraan."
                    action={
                        <Button asChild>
                            <Link href={route('admin.car-models.create')}>
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                Tambah Model
                            </Link>
                        </Button>
                    }
                />
            ) : (
                <>
                    <DataTable
                        caption="Daftar model mobil di katalog"
                        columns={KOLOM}
                        rows={carModels.data}
                        rowKey={(row) => row.id}
                        actions={aksi}
                    />
                    <Pagination meta={carModels} />
                </>
            )}
        </AdminLayout>
    );
}
