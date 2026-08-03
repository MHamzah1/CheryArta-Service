import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatJam } from '@/lib/format';
import { TONE_CLASS, type StatusTone } from '@/lib/status';
import { cn } from '@/lib/utils';
import { type AdminBookingDetail, type StatusTransitionOption } from '@/types';
import { useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

interface Props {
    booking: AdminBookingDetail;
    /** Status tujuan yang sah — disusun server dari state machine. */
    options: StatusTransitionOption[];
}

/**
 * Panel ubah status di detail booking (docs/05 §5.3, docs/06 §6.5).
 *
 * Yang ditampilkan hanya transisi yang sah, dan daftarnya datang dari server —
 * komponen ini tidak tahu satu pun aturan state machine. Transisi yang tidak
 * sah tetap ditolak server dengan 422 sekalipun seseorang menembak rutenya
 * langsung (.claude/rules/20).
 */
export default function BookingStatusPanel({ booking, options }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        status: '',
        note: '',
        odometer: booking.odometer !== null ? String(booking.odometer) : '',
    });

    const terpilih = options.find((opsi) => opsi.value === data.status);

    const kirim = (event: FormEvent) => {
        event.preventDefault();

        put(route('admin.bookings.status', booking.booking_code), {
            preserveScroll: true,
            onSuccess: () => setData('note', ''),
        });
    };

    if (options.length === 0) {
        return (
            <p className="text-ink-soft text-sm">
                Booking ini sudah selesai diproses, sehingga statusnya tidak bisa diubah lagi. Untuk servis berikutnya, buatkan booking
                baru.
            </p>
        );
    }

    return (
        <form onSubmit={kirim} className="grid gap-4">
            <fieldset className="grid gap-2">
                <legend className="mb-2 text-sm font-medium">Ubah status menjadi</legend>

                {options.map((opsi) => (
                    <label
                        key={opsi.value}
                        className={cn(
                            'rounded-btn focus-within:ring-brand-600 flex cursor-pointer items-center gap-3 border px-3 py-2.5 text-sm transition focus-within:ring-2',
                            data.status === opsi.value ? 'border-brand-700 bg-brand-50' : 'border-line hover:bg-canvas',
                        )}
                    >
                        <input
                            type="radio"
                            name="status"
                            value={opsi.value}
                            checked={data.status === opsi.value}
                            onChange={(e) => setData('status', e.target.value)}
                            className="text-brand-700 focus:ring-brand-600 h-4 w-4"
                        />
                        <span className={cn('rounded-full px-2.5 py-0.5 text-xs font-semibold', TONE_CLASS[opsi.tone as StatusTone])}>
                            {opsi.label}
                        </span>
                    </label>
                ))}

                <InputError message={errors.status} />
            </fieldset>

            {/* Odometer diminta saat kendaraan masuk dan saat pekerjaan
                selesai — angka inilah yang memperbarui catatan kendaraan. */}
            {(data.status === 'in_progress' || data.status === 'completed') && (
                <div className="grid gap-1.5">
                    <Label htmlFor="odometer">Odometer saat ini (km)</Label>
                    <Input
                        id="odometer"
                        type="number"
                        inputMode="numeric"
                        min={0}
                        value={data.odometer}
                        onChange={(e) => setData('odometer', e.target.value)}
                        placeholder={booking.vehicle.last_odometer !== null ? String(booking.vehicle.last_odometer) : '0'}
                    />
                    {booking.vehicle.last_odometer !== null && (
                        <p className="text-ink-muted text-xs">
                            Catatan terakhir kendaraan ini: {booking.vehicle.last_odometer.toLocaleString('id-ID')} km.
                        </p>
                    )}
                    <InputError message={errors.odometer} />
                </div>
            )}

            <div className="grid gap-1.5">
                <Label htmlFor="note">
                    {terpilih?.requires_note ? 'Alasan pembatalan' : 'Catatan untuk pelanggan'}
                    {terpilih?.requires_note && <span className="text-danger-600"> *</span>}
                </Label>
                <Textarea
                    id="note"
                    value={data.note}
                    onChange={(e) => setData('note', e.target.value)}
                    rows={3}
                    maxLength={255}
                    placeholder={
                        terpilih?.requires_note
                            ? 'Contoh: Pelanggan meminta jadwal digeser ke minggu depan.'
                            : 'Opsional. Contoh: Sampai jumpa besok pukul 09.00.'
                    }
                />
                <p className="text-ink-muted text-xs">Catatan ini ikut tampil di riwayat status yang dilihat pelanggan.</p>
                <InputError message={errors.note} />
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <Button type="submit" disabled={processing || data.status === ''}>
                    Simpan Perubahan
                </Button>

                <p className="text-ink-muted text-sm">
                    Jadwal sekarang: {formatJam(booking.booking_time)}
                </p>
            </div>
        </form>
    );
}
