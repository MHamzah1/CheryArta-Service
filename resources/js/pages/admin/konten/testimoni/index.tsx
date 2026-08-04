import SortableList from '@/components/admin/sortable-list';
import ConfirmDialog from '@/components/confirm-dialog';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { type TestimonialRow } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { MessageSquareQuote, Pencil, Plus, Star, Trash2 } from 'lucide-react';

interface Props {
    testimonials: TestimonialRow[];
}

/** Bintang selalu disertai teks — warna saja tidak cukup (aturan 40). */
function Rating({ nilai }: { nilai: number }) {
    return (
        <span className="flex items-center gap-1">
            <span className="flex" aria-hidden="true">
                {Array.from({ length: 5 }, (_, i) => (
                    <Star key={i} className={i < nilai ? 'fill-gold-400 text-gold-400 h-4 w-4' : 'text-line h-4 w-4'} />
                ))}
            </span>
            <span className="text-ink-soft text-xs">{nilai} dari 5</span>
        </span>
    );
}

export default function TestimoniIndex({ testimonials }: Props) {
    const hapus = (testimoni: TestimonialRow) => {
        router.delete(route('admin.testimonials.destroy', testimoni.id), { preserveScroll: true });
    };

    return (
        <AdminLayout
            title="Testimoni"
            description="Testimoni yang tampil di landing page. Diketik admin — tidak ada kiriman dari pelanggan."
            actions={
                <Button asChild>
                    <Link href={route('admin.testimonials.create')}>
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Tambah Testimoni
                    </Link>
                </Button>
            }
        >
            <Head title="Testimoni" />

            {testimonials.length === 0 ? (
                <EmptyState
                    icon={MessageSquareQuote}
                    title="Belum ada testimoni"
                    description="Testimoni yang Anda tambahkan akan tampil di landing page."
                    action={
                        <Button asChild>
                            <Link href={route('admin.testimonials.create')}>Tambah Testimoni</Link>
                        </Button>
                    }
                />
            ) : (
                <SortableList
                    items={testimonials}
                    reorderUrl={route('admin.testimonials.reorder')}
                    label={(item) => `testimoni dari ${item.customer_name}`}
                >
                    {(item) => (
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="font-medium">{item.customer_name}</p>
                                {item.car_model && <Badge variant="outline">{item.car_model}</Badge>}
                                <Badge variant={item.is_published ? 'default' : 'secondary'}>
                                    {item.is_published ? 'Terbit' : 'Disembunyikan'}
                                </Badge>
                            </div>

                            <div className="mt-1">
                                <Rating nilai={item.rating} />
                            </div>

                            <p className="text-ink-soft mt-2 max-w-prose text-sm">{item.content}</p>

                            <div className="mt-3 flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('admin.testimonials.edit', item.id)}>
                                        <Pencil className="h-4 w-4" aria-hidden="true" />
                                        Ubah
                                    </Link>
                                </Button>

                                <ConfirmDialog
                                    trigger={
                                        <Button variant="outline" size="sm" aria-label={`Hapus testimoni dari ${item.customer_name}`}>
                                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                                            Hapus
                                        </Button>
                                    }
                                    title="Hapus testimoni ini?"
                                    description={`Testimoni dari ${item.customer_name} akan dihapus dan hilang dari landing page. Tindakan ini tidak bisa dibatalkan.`}
                                    confirmLabel="Hapus Testimoni"
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
