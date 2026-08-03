import Seo from '@/components/seo';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicLayout from '@/layouts/public-layout';
import { formatJadwal, formatJamIso } from '@/lib/format';
import { type PublicTracking } from '@/types';
import { Link, router } from '@inertiajs/react';
import { LogIn, SearchX } from 'lucide-react';
import { useState, type FormEventHandler } from 'react';

interface Props {
    kode: string;
    booking: PublicTracking | null;
    sudahDicari: boolean;
}

/**
 * Pelacakan tanpa login (docs/05 §5.7).
 *
 * Yang ditampilkan HANYA kode, jadwal, status, dan perkiraan selesai — server
 * memang tidak mengirim apa pun selain itu. Fitur lama membiarkan siapa pun
 * mengambil seluruh basis data lalu menyaringnya di browser (temuan S8).
 */
export default function CekService({ kode, booking, sudahDicari }: Props) {
    const [input, setInput] = useState(kode);

    const cari: FormEventHandler = (e) => {
        e.preventDefault();

        router.get(route('public.tracking'), { kode: input.trim() }, { preserveState: true });
    };

    return (
        <PublicLayout>
            <Seo
                title="Cek Status Servis"
                description="Lacak status servis kendaraan Anda cukup dengan kode booking — tanpa perlu masuk ke akun."
            />

            <section className="mx-auto w-full max-w-2xl px-4 py-16 md:py-24">
                <h1 className="text-2xl font-bold md:text-3xl">Cek Status Servis</h1>
                <p className="text-ink-soft mt-2 max-w-prose">
                    Masukkan kode booking yang Anda terima saat memesan, misalnya <span className="font-mono">CA-20260803-0001</span>.
                </p>

                <form onSubmit={cari} className="mt-6 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                    <div className="grid gap-2">
                        <Label htmlFor="kode">Kode Booking</Label>
                        <Input
                            id="kode"
                            value={input}
                            onChange={(e) => setInput(e.target.value.toUpperCase())}
                            placeholder="CA-20260803-0001"
                            maxLength={20}
                            className="font-mono"
                            autoComplete="off"
                        />
                    </div>

                    <Button type="submit" disabled={input.trim() === ''}>
                        Cek Status
                    </Button>
                </form>

                {sudahDicari && booking === null && (
                    <div className="border-line bg-surface mt-8 rounded-2xl border border-dashed px-6 py-10 text-center">
                        <SearchX className="text-ink-muted mx-auto mb-3 h-8 w-8" aria-hidden="true" />
                        <p className="font-semibold">Kode booking tidak ditemukan</p>
                        <p className="text-ink-soft mx-auto mt-2 max-w-prose text-sm">
                            Periksa kembali penulisannya. Kode booking berbentuk CA diikuti tanggal dan empat angka.
                        </p>
                    </div>
                )}

                {booking && (
                    <div className="border-line bg-surface shadow-card mt-8 rounded-2xl border p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <p className="text-brand-700 font-mono text-lg font-bold tracking-wide">{booking.booking_code}</p>
                            <StatusBadge status={booking.status} />
                        </div>

                        <dl className="mt-5 grid gap-3 text-sm">
                            <div className="flex flex-wrap justify-between gap-2">
                                <dt className="text-ink-soft">Jadwal</dt>
                                <dd className="text-right font-medium">{formatJadwal(booking.booking_date, booking.booking_time)}</dd>
                            </div>

                            <div className="flex flex-wrap justify-between gap-2">
                                <dt className="text-ink-soft">Perkiraan selesai</dt>
                                <dd className="text-right font-medium">{formatJamIso(booking.estimated_finish_at)}</dd>
                            </div>
                        </dl>

                        <p className="text-ink-soft border-line mt-5 border-t pt-5 text-sm">
                            Rincian kendaraan, keluhan, dan riwayat servis hanya bisa dilihat setelah masuk ke akun Anda.
                        </p>

                        <div className="mt-4">
                            <Button variant="outline" asChild>
                                <Link href={route('login')}>
                                    <LogIn className="h-4 w-4" aria-hidden="true" />
                                    Masuk untuk Rincian Lengkap
                                </Link>
                            </Button>
                        </div>
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
