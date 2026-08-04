import SortableList from '@/components/admin/sortable-list';
import ConfirmDialog from '@/components/confirm-dialog';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { type FaqRow } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { HelpCircle, Pencil, Plus, Trash2 } from 'lucide-react';

interface Props {
    faqs: FaqRow[];
}

export default function FaqIndex({ faqs }: Props) {
    const hapus = (faq: FaqRow) => {
        router.delete(route('admin.faqs.destroy', faq.id), { preserveScroll: true });
    };

    return (
        <AdminLayout
            title="FAQ"
            description="Pertanyaan yang tampil di halaman FAQ publik. Susun urutannya dengan menyeret atau tombol panah."
            actions={
                <Button asChild>
                    <Link href={route('admin.faqs.create')}>
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Tambah Pertanyaan
                    </Link>
                </Button>
            }
        >
            <Head title="FAQ" />

            {faqs.length === 0 ? (
                <EmptyState
                    icon={HelpCircle}
                    title="Belum ada pertanyaan"
                    description="Pertanyaan yang Anda tambahkan akan tampil di halaman FAQ publik."
                    action={
                        <Button asChild>
                            <Link href={route('admin.faqs.create')}>Tambah Pertanyaan</Link>
                        </Button>
                    }
                />
            ) : (
                <SortableList items={faqs} reorderUrl={route('admin.faqs.reorder')} label={(item) => `pertanyaan ${item.question}`}>
                    {(item) => (
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="font-medium">{item.question}</p>
                                {item.category && <Badge variant="outline">{item.category}</Badge>}
                                <Badge variant={item.is_active ? 'default' : 'secondary'}>
                                    {item.is_active ? 'Aktif' : 'Nonaktif'}
                                </Badge>
                            </div>

                            <p className="text-ink-soft mt-1 max-w-prose text-sm">{item.answer}</p>

                            <div className="mt-3 flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('admin.faqs.edit', item.id)}>
                                        <Pencil className="h-4 w-4" aria-hidden="true" />
                                        Ubah
                                    </Link>
                                </Button>

                                <ConfirmDialog
                                    trigger={
                                        <Button variant="outline" size="sm" aria-label={`Hapus pertanyaan ${item.question}`}>
                                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                                            Hapus
                                        </Button>
                                    }
                                    title="Hapus pertanyaan ini?"
                                    description={`"${item.question}" akan hilang dari halaman FAQ publik. Tindakan ini tidak bisa dibatalkan.`}
                                    confirmLabel="Hapus Pertanyaan"
                                    onConfirm={() => hapus(item)}
                                />
                            </div>
                        </div>
                    )}
                </SortableList>
            )}
        </AdminLayout>
    );
}
