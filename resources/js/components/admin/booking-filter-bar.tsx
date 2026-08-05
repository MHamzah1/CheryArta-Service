import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type AdminBookingFilters, type SelectOption } from '@/types';
import { router } from '@inertiajs/react';
import { CalendarClock, MessageCircle, Search, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface Props {
    filters: AdminBookingFilters;
    statusOptions: { value: string; label: string }[];
    packageOptions: SelectOption[];
    advisorOptions: SelectOption[];
    /** Hari ini menurut server; dipakai tombol "Mendatang". */
    today: string;
}

type Nilai = string | number | null;

/**
 * Saringan daftar booking (docs/07 §A2).
 *
 * Seluruhnya dijalankan di SERVER dan tersimpan di query string, sehingga
 * hasil saringan bisa di-bookmark dan dibagikan ke rekan lewat tautan.
 * Sistem lama menyaring di browser setelah mengunduh seluruh tabel (temuan S8).
 *
 * `<select>` biasa, bukan komponen shadcn: ini form yang diisi cepat di antara
 * dua telepon, dan pemilih bawaan peramban jauh lebih cepat dinavigasi dengan
 * keyboard di layar kecil.
 */
export default function BookingFilterBar({ filters, statusOptions, packageOptions, advisorOptions, today }: Props) {
    const [cari, setCari] = useState(filters.cari ?? '');

    const kunjungi = (perubahan: Record<string, Nilai>) => {
        const gabungan: Record<string, Nilai> = { ...filters, cari, ...perubahan };

        const bersih = Object.fromEntries(
            Object.entries(gabungan).filter(([, nilai]) => nilai !== null && nilai !== ''),
        );

        router.get(route('admin.bookings.index'), bersih, { preserveState: true, replace: true });
    };

    const kirim = (event: FormEvent) => {
        event.preventDefault();
        kunjungi({});
    };

    const adaSaringan =
        filters.cari !== null ||
        filters.status !== null ||
        filters.dari !== null ||
        filters.sampai !== null ||
        filters.paket !== null ||
        filters.advisor !== null ||
        filters.wa !== null;

    return (
        <form onSubmit={kirim} className="border-line bg-surface shadow-card grid gap-4 rounded-2xl border p-4">
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div className="grid gap-1.5 lg:col-span-2">
                    <Label htmlFor="cari">Cari</Label>
                    <div className="flex gap-2">
                        <Input
                            id="cari"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Nama, plat nomor, atau kode booking"
                            autoComplete="off"
                        />
                        <Button type="submit" variant="outline" aria-label="Terapkan pencarian">
                            <Search className="h-4 w-4" aria-hidden="true" />
                        </Button>
                    </div>
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="status">Status</Label>
                    <select
                        id="status"
                        value={filters.status ?? ''}
                        onChange={(e) => kunjungi({ status: e.target.value || null })}
                        className="border-input bg-background focus-visible:ring-brand-600 h-10 rounded-md border px-3 text-sm focus-visible:ring-2"
                    >
                        <option value="">Semua status</option>
                        {statusOptions.map((opsi) => (
                            <option key={opsi.value} value={opsi.value}>
                                {opsi.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="paket">Paket layanan</Label>
                    <select
                        id="paket"
                        value={filters.paket ?? ''}
                        onChange={(e) => kunjungi({ paket: e.target.value || null })}
                        className="border-input bg-background focus-visible:ring-brand-600 h-10 rounded-md border px-3 text-sm focus-visible:ring-2"
                    >
                        <option value="">Semua paket</option>
                        {packageOptions.map((opsi) => (
                            <option key={opsi.value} value={opsi.value}>
                                {opsi.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="dari">Tanggal mulai</Label>
                    <Input id="dari" type="date" value={filters.dari ?? ''} onChange={(e) => kunjungi({ dari: e.target.value || null })} />
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="sampai">Tanggal akhir</Label>
                    <Input
                        id="sampai"
                        type="date"
                        value={filters.sampai ?? ''}
                        onChange={(e) => kunjungi({ sampai: e.target.value || null })}
                    />
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="advisor">Advisor</Label>
                    <select
                        id="advisor"
                        value={filters.advisor ?? ''}
                        onChange={(e) => kunjungi({ advisor: e.target.value || null })}
                        className="border-input bg-background focus-visible:ring-brand-600 h-10 rounded-md border px-3 text-sm focus-visible:ring-2"
                    >
                        <option value="">Semua advisor</option>
                        {advisorOptions.map((opsi) => (
                            <option key={opsi.value} value={opsi.value}>
                                {opsi.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="urutan">Urutan</Label>
                    <select
                        id="urutan"
                        value={filters.urutan}
                        onChange={(e) => kunjungi({ urutan: e.target.value })}
                        className="border-input bg-background focus-visible:ring-brand-600 h-10 rounded-md border px-3 text-sm focus-visible:ring-2"
                    >
                        <option value="terbaru">Terbaru dulu</option>
                        <option value="terdekat">Jadwal terdekat dulu</option>
                    </select>
                </div>
            </div>

            <div className="flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => kunjungi({ dari: today, sampai: null, urutan: 'terdekat' })}
                >
                    <CalendarClock className="h-4 w-4" aria-hidden="true" />
                    Mendatang
                </Button>

                <Button type="button" variant="outline" size="sm" onClick={() => kunjungi({ dari: today, sampai: today, urutan: 'terdekat' })}>
                    Hari ini
                </Button>

                {/* Tujuan kartu "Belum Dikabari" di dashboard (docs/07 §A1).
                    Ada juga di sini supaya advisor bisa mencapainya tanpa
                    kembali ke dashboard lebih dulu. */}
                <Button
                    type="button"
                    variant={filters.wa === 'belum' ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => kunjungi({ wa: filters.wa === 'belum' ? null : 'belum' })}
                >
                    <MessageCircle className="h-4 w-4" aria-hidden="true" />
                    Belum dikabari
                </Button>

                {adaSaringan && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            setCari('');
                            router.get(route('admin.bookings.index'), {}, { preserveState: true, replace: true });
                        }}
                    >
                        <X className="h-4 w-4" aria-hidden="true" />
                        Bersihkan saringan
                    </Button>
                )}
            </div>
        </form>
    );
}
