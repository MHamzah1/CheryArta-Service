import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { formatTanggal, formatTanggalJamIso } from '@/lib/format';
import { type ActivityLogFilters, type ActivityLogOption, type ActivityLogRow, type Paginated } from '@/types';
import { Head, router } from '@inertiajs/react';
import { History, X } from 'lucide-react';

interface Props {
    logs: Paginated<ActivityLogRow>;
    filters: ActivityLogFilters;
    causerOptions: ActivityLogOption[];
    subjectOptions: ActivityLogOption[];
    mulaiMencatat: string | null;
}

const SEMUA = '__semua__';

/** Nama kolom database dibuat terbaca — "phone_wa" bukan bahasa manusia. */
const LABEL_KOLOM: Record<string, string> = {
    name: 'Nama',
    email: 'Email',
    phone_wa: 'Nomor WhatsApp',
    role: 'Peran',
    is_active: 'Status aktif',
    is_read: 'Sudah dibaca',
    read_by: 'Dibaca oleh',
    title: 'Judul',
    description: 'Deskripsi',
    question: 'Pertanyaan',
    answer: 'Jawaban',
    category: 'Kategori',
    customer_name: 'Nama pelanggan',
    car_model: 'Model mobil',
    rating: 'Rating',
    content: 'Isi',
    is_published: 'Terbit',
    is_free: 'Gratis',
    price: 'Harga',
    sort_order: 'Urutan',
    booking_date: 'Tanggal booking',
    booking_time: 'Jam booking',
    complaint: 'Keluhan',
    admin_note: 'Catatan admin',
};

const LABEL_AKSI: Record<string, string> = {
    created: 'Dibuat',
    updated: 'Diubah',
    deleted: 'Dihapus',
};

export default function ActivityLogIndex({ logs, filters, causerOptions, subjectOptions, mulaiMencatat }: Props) {
    const saring = (kunci: keyof ActivityLogFilters, nilai: string | null) => {
        router.get(
            route('admin.activity-log.index'),
            { ...filters, [kunci]: nilai ?? undefined },
            { preserveState: true, replace: true },
        );
    };

    const adaSaringan = Object.values(filters).some((nilai) => nilai !== null);

    return (
        <AdminLayout title="Activity Log" description="Siapa mengubah apa dan kapan.">
            <Head title="Activity Log" />

            {/* Kekosongan sebelum tanggal ini bukan berarti tidak ada perubahan
                — hanya berarti belum dicatat. Menyebutkannya mencegah log
                dibaca sebagai bukti bahwa tidak terjadi apa-apa. */}
            <p className="text-ink-soft border-line bg-canvas mb-6 rounded-xl border px-4 py-3 text-sm">
                Pencatatan dimulai {mulaiMencatat ? formatTanggal(mulaiMencatat) : 'sejak fitur ini aktif'}. Perubahan
                sebelum tanggal itu tidak tercatat. Riwayat status booking ada di halaman detail booking, bukan di sini.
            </p>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="space-y-2">
                    <Label htmlFor="causer">Pelaku</Label>
                    <Select
                        value={filters.causer === null ? SEMUA : String(filters.causer)}
                        onValueChange={(nilai) => saring('causer', nilai === SEMUA ? null : nilai)}
                    >
                        <SelectTrigger id="causer">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={SEMUA}>Semua pelaku</SelectItem>
                            {causerOptions.map((opsi) => (
                                <SelectItem key={opsi.value} value={opsi.value}>
                                    {opsi.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="subject">Jenis Objek</Label>
                    <Select
                        value={filters.subject ?? SEMUA}
                        onValueChange={(nilai) => saring('subject', nilai === SEMUA ? null : nilai)}
                    >
                        <SelectTrigger id="subject">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={SEMUA}>Semua objek</SelectItem>
                            {subjectOptions.map((opsi) => (
                                <SelectItem key={opsi.value} value={opsi.value}>
                                    {opsi.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="dari">Dari Tanggal</Label>
                    <Input
                        id="dari"
                        type="date"
                        value={filters.dari ?? ''}
                        onChange={(e) => saring('dari', e.target.value || null)}
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="sampai">Sampai Tanggal</Label>
                    <Input
                        id="sampai"
                        type="date"
                        value={filters.sampai ?? ''}
                        onChange={(e) => saring('sampai', e.target.value || null)}
                    />
                </div>
            </div>

            {adaSaringan && (
                <Button
                    variant="outline"
                    size="sm"
                    className="mb-6"
                    onClick={() => router.get(route('admin.activity-log.index'), {}, { replace: true })}
                >
                    <X className="h-4 w-4" aria-hidden="true" />
                    Hapus Saringan
                </Button>
            )}

            {logs.data.length === 0 ? (
                <EmptyState
                    icon={History}
                    title="Tidak ada catatan"
                    description={
                        adaSaringan
                            ? 'Tidak ada perubahan yang cocok dengan saringan ini. Coba perlebar rentang tanggalnya.'
                            : 'Perubahan pada akun, katalog, paket layanan, dan konten akan tercatat di sini.'
                    }
                />
            ) : (
                <ol className="space-y-3">
                    {logs.data.map((log) => (
                        <li key={log.id} className="border-line bg-surface shadow-card rounded-2xl border p-5">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant={log.description === 'deleted' ? 'destructive' : 'default'}>
                                    {LABEL_AKSI[log.description] ?? log.description}
                                </Badge>
                                <span className="font-medium">{log.subject_label}</span>
                                <span className="text-ink-soft text-sm">
                                    oleh {log.causer_name ?? 'sistem'} · {formatTanggalJamIso(log.created_at)}
                                </span>
                            </div>

                            {log.changes.length > 0 && (
                                <div className="mt-3 overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <caption className="sr-only">Perubahan pada {log.subject_label}</caption>
                                        <thead className="text-ink-soft border-line border-b text-left">
                                            <tr>
                                                <th scope="col" className="py-2 pr-4 font-semibold">
                                                    Kolom
                                                </th>
                                                <th scope="col" className="py-2 pr-4 font-semibold">
                                                    Sebelum
                                                </th>
                                                <th scope="col" className="py-2 font-semibold">
                                                    Sesudah
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {log.changes.map((ubah) => (
                                                <tr key={ubah.field} className="border-line border-b last:border-0">
                                                    <td className="py-2 pr-4">{LABEL_KOLOM[ubah.field] ?? ubah.field}</td>
                                                    <td className="text-ink-soft py-2 pr-4 line-through">{ubah.before}</td>
                                                    <td className="py-2 font-medium">{ubah.after}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </li>
                    ))}
                </ol>
            )}

            <Pagination meta={logs} />
        </AdminLayout>
    );
}
