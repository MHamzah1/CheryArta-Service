import { Skeleton } from '@/components/ui/skeleton';
import { formatJam } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type SlotAvailability, type SlotResponse } from '@/types';
import { useEffect, useState } from 'react';

interface Props {
    /** Tanggal `YYYY-MM-DD`. Kosong berarti pengguna belum memilih tanggal. */
    date: string;
    value: string;
    onChange: (time: string) => void;
    /** Booking yang sedang dijadwal ulang — slotnya sendiri tidak dihitung penuh. */
    ignoreBookingId?: number;
}

/**
 * Kisi slot dengan keadaan tersedia / sisa 1 / penuh (docs/06 §6.6).
 *
 * Ini SATU-SATUNYA tempat di aplikasi yang mengambil data lewat `fetch`;
 * pengecualian ini tertulis di .claude/rules/20 karena ketersediaan slot
 * bergantung pada tanggal yang baru saja dipilih pengguna, bukan pada props
 * halaman.
 *
 * Perbedaan penting dari sistem lama: slot penuh TERLIHAT sebelum dipilih,
 * bukan ditolak lewat alert() setelah tombol ditekan (docs/05 §5.2).
 */
export default function SlotPicker({ date, value, onChange, ignoreBookingId }: Props) {
    const [slots, setSlots] = useState<SlotAvailability[]>([]);
    const [alasan, setAlasan] = useState<string | null>(null);
    const [memuat, setMemuat] = useState(false);
    const [gagal, setGagal] = useState(false);

    useEffect(() => {
        if (!date) {
            setSlots([]);
            setAlasan(null);

            return;
        }

        // Permintaan yang sudah usang dibuang: pengguna bisa mengganti tanggal
        // lebih cepat daripada jaringan menjawab.
        const kontrol = new AbortController();

        setMemuat(true);
        setGagal(false);

        fetch(route('booking.slots', { date }), {
            headers: { Accept: 'application/json' },
            signal: kontrol.signal,
        })
            .then((res) => (res.ok ? (res.json() as Promise<SlotResponse>) : Promise.reject(res)))
            .then((data) => {
                setSlots(data.slots);
                setAlasan(data.reason);
            })
            .catch((error) => {
                if (error instanceof DOMException && error.name === 'AbortError') return;
                setGagal(true);
            })
            .finally(() => setMemuat(false));

        return () => kontrol.abort();
    }, [date, ignoreBookingId]);

    if (!date) {
        return <p className="text-ink-soft text-sm">Pilih tanggal lebih dulu untuk melihat jam yang tersedia.</p>;
    }

    if (memuat) {
        return (
            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                {Array.from({ length: 8 }).map((_, i) => (
                    <Skeleton key={i} className="h-16 rounded-xl" />
                ))}
            </div>
        );
    }

    if (gagal) {
        return <p className="text-danger-600 text-sm">Ketersediaan jam gagal dimuat. Periksa koneksi Anda, lalu pilih ulang tanggalnya.</p>;
    }

    if (alasan) {
        return <p className="text-ink-soft text-sm">{alasan}</p>;
    }

    if (slots.length === 0) {
        return <p className="text-ink-soft text-sm">Tidak ada jam servis pada tanggal tersebut.</p>;
    }

    return (
        <div role="radiogroup" aria-label="Pilih jam servis" className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
            {slots.map((slot) => {
                const terpilih = slot.time === value;

                return (
                    <button
                        key={slot.time}
                        type="button"
                        role="radio"
                        aria-checked={terpilih}
                        // Slot penuh memakai aria-disabled + teks "Penuh",
                        // bukan sekadar warna abu (.claude/rules/20).
                        aria-disabled={slot.is_full}
                        disabled={slot.is_full}
                        onClick={() => onChange(slot.time)}
                        className={cn(
                            'focus-visible:ring-brand-600 flex min-h-16 flex-col items-center justify-center rounded-xl border px-3 py-2 text-sm transition focus-visible:ring-2',
                            slot.is_full && 'border-line bg-canvas text-ink-muted cursor-not-allowed',
                            !slot.is_full && terpilih && 'border-brand-700 bg-brand-700 text-white',
                            !slot.is_full && !terpilih && 'border-line bg-surface hover:border-brand-400 hover:bg-brand-50',
                        )}
                    >
                        <span className="font-semibold">{formatJam(slot.time)}</span>
                        <span className={cn('text-xs', terpilih && !slot.is_full ? 'text-white/80' : 'text-ink-soft')}>
                            {slot.is_full ? 'Penuh' : slot.remaining === 1 ? 'Sisa 1' : 'Tersedia'}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}
