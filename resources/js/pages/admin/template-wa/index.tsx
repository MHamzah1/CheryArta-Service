import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { formatTanggalJamIso } from '@/lib/format';
import { type WhatsAppTemplateRow } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';

interface Props {
    templates: WhatsAppTemplateRow[];
}

/**
 * A7 — daftar template pesan WhatsApp (docs/07 §A7).
 *
 * Tidak ada tombol "Tambah" maupun "Hapus": setiap kunci punya pemicunya
 * sendiri di dalam kode, jadi template buatan admin tidak akan pernah
 * terpanggil dan template yang dihapus akan mematahkan jalur kirim
 * (keputusan grill Q5). Yang bisa diubah adalah kalimatnya.
 */
export default function WhatsAppTemplateIndex({ templates }: Props) {
    return (
        <AdminLayout
            title="Template WhatsApp"
            description="Teks pesan yang disiapkan untuk pelanggan. Mengubahnya di sini tidak mengubah pesan yang sudah terkirim."
        >
            <Head title="Template WhatsApp" />

            <ul className="grid gap-3">
                {templates.map((template) => (
                    <li key={template.id} className="border-line bg-surface shadow-card rounded-2xl border p-4">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="font-medium">{template.name}</p>
                                    <Badge variant={template.is_active ? 'default' : 'secondary'}>
                                        {template.is_active ? 'Aktif' : 'Nonaktif'}
                                    </Badge>
                                </div>

                                <p className="text-ink-soft mt-1 max-w-prose text-sm">{template.trigger}</p>

                                <p className="text-ink-muted mt-1 text-sm">
                                    Kode: {template.key}
                                    {template.updated_at && ` · diperbarui ${formatTanggalJamIso(template.updated_at)}`}
                                </p>
                            </div>

                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('admin.whatsapp-templates.edit', template.id)}>
                                    <Pencil className="h-4 w-4" aria-hidden="true" />
                                    Ubah
                                </Link>
                            </Button>
                        </div>
                    </li>
                ))}
            </ul>

            <p className="text-ink-muted mt-4 max-w-prose text-sm">
                Template yang dinonaktifkan tidak ditawarkan sama sekali di detail booking — panelnya menjelaskan
                keadaan itu dan tidak menampilkan tombol. Booking yang bersangkutan juga tidak ikut dihitung sebagai
                “belum dikabari”.
            </p>
        </AdminLayout>
    );
}
