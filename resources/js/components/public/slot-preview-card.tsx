import { formatJam, formatTanggal } from '@/lib/format';
import { type SlotPreview } from '@/types';
import { CalendarCheck } from 'lucide-react';

interface Props {
    preview: SlotPreview;
}

/**
 * Kartu ketersediaan hari buka terdekat di hero (docs/06 §6.5).
 *
 * Tanggal dan daftar jamnya dihitung SlotService dari `config/booking.php` —
 * tidak ada satu pun angka aturan booking (kuota, H-1, daftar slot) yang
 * ditulis di komponen ini (temuan B2).
 *
 * Slot penuh ditandai teks "Penuh" + `aria-disabled`, bukan sekadar warna abu
 * (aturan 20 §Aksesibilitas).
 */
export default function SlotPreviewCard({ preview }: Props) {
    return (
        <div className="bg-surface/95 rounded-card shadow-pop p-6 backdrop-blur">
            <p className="text-brand-700 flex items-center gap-2 text-sm font-semibold">
                <CalendarCheck className="h-4 w-4" aria-hidden="true" />
                Jadwal terdekat
            </p>

            <p className="text-ink mt-1 text-base font-semibold">{formatTanggal(preview.date)}</p>

            {preview.slots.length === 0 ? (
                <p className="text-ink-soft mt-4 text-sm">
                    Belum ada jam yang bisa ditampilkan. Silakan buka form booking untuk memilih tanggal lain.
                </p>
            ) : (
                <ul className="mt-4 grid grid-cols-3 gap-2">
                    {preview.slots.map((slot) => (
                        <li
                            key={slot.time}
                            aria-disabled={slot.is_full}
                            className={
                                slot.is_full
                                    ? 'border-line bg-canvas text-ink-muted rounded-btn border px-2 py-2 text-center'
                                    : 'border-brand-200 bg-brand-50 text-brand-700 rounded-btn border px-2 py-2 text-center'
                            }
                        >
                            <span className="block text-sm font-semibold">{formatJam(slot.time)}</span>
                            <span className="block text-xs">{slot.is_full ? 'Penuh' : `Sisa ${slot.remaining}`}</span>
                        </li>
                    ))}
                </ul>
            )}

            <p className="text-ink-muted mt-4 text-xs">
                Sisa kuota diperbarui setiap kali halaman dimuat. Kepastian jadwal diberikan saat booking disimpan.
            </p>
        </div>
    );
}
