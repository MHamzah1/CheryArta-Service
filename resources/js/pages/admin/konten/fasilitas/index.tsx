import SortableList from '@/components/admin/sortable-list';
import ConfirmDialog from '@/components/confirm-dialog';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { type FacilityRow } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Building2, Pencil, Plus, Trash2 } from 'lucide-react';

interface Props {
    facilities: FacilityRow[];
}

export default function FasilitasIndex({ facilities }: Props) {
    const hapus = (fasilitas: FacilityRow) => {
        router.delete(route('admin.facilities.destroy', fasilitas.id), { preserveScroll: true });
    };

    return (
        <AdminLayout
            title="Fasilitas"
            description="Fasilitas yang tampil di landing page. Susun urutannya dengan menyeret atau tombol panah."
            actions={
                <Button asChild>
                    <Link href={route('admin.facilities.create')}>
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Tambah Fasilitas
                    </Link>
                </Button>
            }
        >
            <Head title="Fasilitas" />

            {facilities.length === 0 ? (
                <EmptyState
                    icon={Building2}
                    title="Belum ada fasilitas"
                    description="Fasilitas yang Anda tambahkan akan tampil di landing page."
                    action={
                        <Button asChild>
                            <Link href={route('admin.facilities.create')}>Tambah Fasilitas</Link>
                        </Button>
                    }
                />
            ) : (
                <SortableList
                    items={facilities}
                    reorderUrl={route('admin.facilities.reorder')}
                    label={(item) => `fasilitas ${item.title}`}
                >
                    {(item) => (
                        <div className="flex items-start gap-4">
                            {item.image_url && (
                                <img
                                    src={item.image_url}
                                    alt=""
                                    loading="lazy"
                                    className="border-line h-16 w-16 shrink-0 rounded-xl border object-cover"
                                />
                            )}

                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="font-medium">{item.title}</p>
                                    <Badge variant={item.is_active ? 'default' : 'secondary'}>
                                        {item.is_active ? 'Aktif' : 'Nonaktif'}
                                    </Badge>
                                </div>
                                <p className="text-ink-soft mt-1 text-sm">{item.description}</p>

                                <div className="mt-3 flex flex-wrap gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={route('admin.facilities.edit', item.id)}>
                                            <Pencil className="h-4 w-4" aria-hidden="true" />
                                            Ubah
                                        </Link>
                                    </Button>

                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="outline" size="sm" aria-label={`Hapus fasilitas ${item.title}`}>
                                                <Trash2 className="h-4 w-4" aria-hidden="true" />
                                                Hapus
                                            </Button>
                                        }
                                        title="Hapus fasilitas ini?"
                                        description={`Fasilitas "${item.title}" beserta gambarnya akan dihapus dan hilang dari landing page. Tindakan ini tidak bisa dibatalkan.`}
                                        confirmLabel="Hapus Fasilitas"
                                        onConfirm={() => hapus(item)}
                                    />
                                </div>
                            </div>
                        </div>
                    )}
                </SortableList>
            )}
        </AdminLayout>
    );
}
