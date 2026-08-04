import ConfirmDialog from '@/components/confirm-dialog';
import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { formatTanggalJamIso, formatTelepon } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type ContactMessageRow, type Paginated } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Check, Inbox, MessageCircle, Trash2 } from 'lucide-react';

interface Props {
    messages: Paginated<ContactMessageRow>;
    filters: { status: string | null };
}

const SARINGAN = [
    { value: null, label: 'Semua' },
    { value: 'belum', label: 'Belum dibaca' },
    { value: 'sudah', label: 'Sudah dibaca' },
];

export default function PesanMasukIndex({ messages, filters }: Props) {
    const saring = (status: string | null) => {
        router.get(route('admin.contact-messages.index'), status === null ? {} : { status }, {
            preserveState: true,
            replace: true,
        });
    };

    const tandaiDibaca = (pesan: ContactMessageRow) => {
        router.put(route('admin.contact-messages.read', pesan.id), {}, { preserveScroll: true });
    };

    const hapus = (pesan: ContactMessageRow) => {
        router.delete(route('admin.contact-messages.destroy', pesan.id), { preserveScroll: true });
    };

    return (
        <AdminLayout title="Pesan Masuk" description="Pesan yang dikirim lewat form kontak di halaman publik.">
            <Head title="Pesan Masuk" />

            <div className="mb-6 flex flex-wrap gap-2" role="group" aria-label="Saring menurut status baca">
                {SARINGAN.map((item) => (
                    <Button
                        key={item.label}
                        type="button"
                        variant={filters.status === item.value ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => saring(item.value)}
                    >
                        {item.label}
                    </Button>
                ))}
            </div>

            {messages.data.length === 0 ? (
                <EmptyState
                    icon={Inbox}
                    title="Tidak ada pesan"
                    description="Pesan yang dikirim lewat form kontak akan muncul di sini."
                />
            ) : (
                <div className="space-y-3">
                    {messages.data.map((pesan) => (
                        <article
                            key={pesan.id}
                            className={cn(
                                'border-line bg-surface shadow-card rounded-2xl border p-5',
                                // Belum dibaca ditandai warna DAN lencana teks,
                                // tidak pernah warna saja (aturan 40).
                                !pesan.is_read && 'border-brand-700/40 bg-brand-50/40',
                            )}
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="font-semibold">{pesan.subject}</h2>
                                        {!pesan.is_read && <Badge>Belum dibaca</Badge>}
                                    </div>
                                    <p className="text-ink-soft mt-1 text-sm">
                                        {pesan.name} · {pesan.email}
                                        {pesan.phone && ` · ${formatTelepon(pesan.phone)}`}
                                    </p>
                                </div>

                                {pesan.created_at && (
                                    <p className="text-ink-muted text-xs">{formatTanggalJamIso(pesan.created_at)}</p>
                                )}
                            </div>

                            <p className="mt-3 max-w-prose text-sm whitespace-pre-line">{pesan.message}</p>

                            {pesan.is_read && pesan.read_by_name && (
                                <p className="text-ink-muted mt-3 text-xs">
                                    Dibaca oleh {pesan.read_by_name}
                                    {pesan.read_at && ` · ${formatTanggalJamIso(pesan.read_at)}`}
                                </p>
                            )}

                            <div className="mt-4 flex flex-wrap gap-2">
                                {!pesan.is_read && (
                                    <Button variant="outline" size="sm" onClick={() => tandaiDibaca(pesan)}>
                                        <Check className="h-4 w-4" aria-hidden="true" />
                                        Tandai Dibaca
                                    </Button>
                                )}

                                {/* Tautan disusun server dari nomor ternormalisasi.
                                    Tanpa nomor, tombolnya tidak dirender sama sekali
                                    — bukan tombol mati. */}
                                {pesan.whatsapp_url && (
                                    <Button variant="outline" size="sm" asChild>
                                        <a href={pesan.whatsapp_url} target="_blank" rel="noopener noreferrer">
                                            <MessageCircle className="h-4 w-4" aria-hidden="true" />
                                            Balas via WhatsApp
                                        </a>
                                    </Button>
                                )}

                                <ConfirmDialog
                                    trigger={
                                        <Button variant="outline" size="sm" aria-label={`Hapus pesan dari ${pesan.name}`}>
                                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                                            Hapus
                                        </Button>
                                    }
                                    title="Hapus pesan ini?"
                                    /* Isi pesan ditampilkan di dialog dengan sengaja:
                                       hapus di sini permanen dan tidak bisa dibatalkan,
                                       jadi yang akan hilang harus terlihat SEBELUM
                                       tombolnya ditekan, bukan sesudah. */
                                    description={`Pesan dari ${pesan.name} — "${pesan.message.slice(0, 140)}${pesan.message.length > 140 ? '…' : ''}" akan dihapus permanen dan tidak bisa dikembalikan.`}
                                    confirmLabel="Hapus Pesan"
                                    onConfirm={() => hapus(pesan)}
                                />
                            </div>
                        </article>
                    ))}
                </div>
            )}

            <Pagination meta={messages} />
        </AdminLayout>
    );
}
