import SlotPicker from '@/components/booking/slot-picker';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatTanggal } from '@/lib/format';
import { type SlotRules } from '@/types';

interface Props {
    rules: SlotRules;
    date: string;
    time: string;
    onDateChange: (date: string) => void;
    onTimeChange: (time: string) => void;
    errors: Partial<Record<string, string>>;
    /** Booking yang sedang dijadwal ulang — slotnya sendiri tidak dihitung penuh. */
    ignoreBookingId?: number;
}

/**
 * Langkah ③ — memilih tanggal lalu jam.
 *
 * `min` dan `max` pada input tanggal datang dari props server, bukan dihitung
 * di sini: aturan H-1 dan batas 60 hari hanya boleh hidup di
 * `config/booking.php` (.claude/rules/20). Batasan di browser ini kenyamanan;
 * penolakan sesungguhnya tetap di server.
 */
export default function StepSchedule({ rules, date, time, onDateChange, onTimeChange, errors, ignoreBookingId }: Props) {
    return (
        <div className="grid gap-6">
            <div className="grid max-w-xs gap-2">
                <Label htmlFor="booking_date">Tanggal Servis</Label>
                <Input
                    id="booking_date"
                    type="date"
                    value={date}
                    min={rules.earliest_date}
                    max={rules.latest_date}
                    onChange={(e) => {
                        onDateChange(e.target.value);
                        // Jam yang tersedia berbeda tiap tanggal — mempertahankan
                        // pilihan lama membuat pengguna mengira jamnya masih sah.
                        onTimeChange('');
                    }}
                    aria-describedby="booking_date-hint"
                />
                <p id="booking_date-hint" className="text-ink-soft text-sm">
                    Paling cepat {formatTanggal(rules.earliest_date)}. Hari Minggu bengkel tutup.
                </p>
                <InputError message={errors.booking_date} />
            </div>

            <div className="grid gap-2">
                <p className="text-sm font-medium">Jam Servis</p>
                {date && <p className="text-ink-soft -mt-1 text-sm">{formatTanggal(date)}</p>}

                <SlotPicker date={date} value={time} onChange={onTimeChange} ignoreBookingId={ignoreBookingId} />

                <InputError message={errors.booking_time} />
            </div>
        </div>
    );
}
