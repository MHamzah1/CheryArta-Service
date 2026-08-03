import SlotPicker from '@/components/booking/slot-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatTanggal } from '@/lib/format';
import { type SlotRules } from '@/types';
import { useForm } from '@inertiajs/react';
import { CalendarClock, LoaderCircle } from 'lucide-react';
import { useState, type FormEventHandler } from 'react';

interface Props {
    bookingCode: string;
    rules: SlotRules;
}

/**
 * Jadwal ulang mandiri (US-C6).
 *
 * Booking lama dibatalkan dan booking BARU dibuat di server — karena itu
 * halaman berpindah ke kode booking yang baru setelah berhasil (docs/05 §5.4).
 */
export default function RescheduleDialog({ bookingCode, rules }: Props) {
    const [terbuka, setTerbuka] = useState(false);

    const { data, setData, put, processing, errors, reset } = useForm({
        booking_date: '',
        booking_time: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('customer.booking.reschedule', bookingCode), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setTerbuka(false);
            },
        });
    };

    return (
        <Dialog open={terbuka} onOpenChange={setTerbuka}>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <CalendarClock className="h-4 w-4" aria-hidden="true" />
                    Jadwalkan Ulang
                </Button>
            </DialogTrigger>

            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Pindahkan jadwal servis</DialogTitle>
                    <DialogDescription>Jadwal lama akan dilepas dan Anda mendapat kode booking baru. Riwayatnya tetap tersimpan.</DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="reschedule_date">Tanggal Baru</Label>
                        <Input
                            id="reschedule_date"
                            type="date"
                            value={data.booking_date}
                            min={rules.earliest_date}
                            max={rules.latest_date}
                            onChange={(e) => {
                                setData('booking_date', e.target.value);
                                setData('booking_time', '');
                            }}
                            aria-describedby="reschedule_date-hint"
                        />
                        <p id="reschedule_date-hint" className="text-ink-soft text-sm">
                            Paling cepat {formatTanggal(rules.earliest_date)}.
                        </p>
                        <InputError message={errors.booking_date} />
                    </div>

                    <div className="grid gap-2">
                        <p className="text-sm font-medium">Jam Baru</p>
                        <SlotPicker date={data.booking_date} value={data.booking_time} onChange={(jam) => setData('booking_time', jam)} />
                        <InputError message={errors.booking_time} />
                    </div>

                    <DialogFooter className="gap-2 sm:gap-2">
                        <Button type="button" variant="outline" onClick={() => setTerbuka(false)} disabled={processing}>
                            Batal
                        </Button>

                        <Button type="submit" disabled={processing || !data.booking_date || !data.booking_time}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            Pindahkan Jadwal
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
