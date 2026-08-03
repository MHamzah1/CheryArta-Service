import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, XCircle } from 'lucide-react';
import { useState, type FormEventHandler } from 'react';

interface Props {
    bookingCode: string;
    /** Pilihan alasan baku, datang dari server agar sama dengan yang divalidasi. */
    reasons: string[];
}

/**
 * Pembatalan mandiri (US-C6).
 *
 * Alasan wajib: tanpa itu advisor tidak bisa membedakan pelanggan yang berubah
 * rencana dari jadwal yang memang salah ditawarkan (docs/05 §5.4).
 */
export default function CancelDialog({ bookingCode, reasons }: Props) {
    const [terbuka, setTerbuka] = useState(false);

    const { data, setData, put, processing, errors, reset } = useForm({
        reason_choice: '',
        reason_note: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('customer.booking.cancel', bookingCode), {
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
                    <XCircle className="h-4 w-4" aria-hidden="true" />
                    Batalkan
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Batalkan booking ini?</DialogTitle>
                    <DialogDescription>
                        Jadwal Anda akan dilepas supaya bisa dipakai pelanggan lain. Tindakan ini tidak bisa dibatalkan — silakan buat booking baru
                        bila berubah pikiran.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-5">
                    <fieldset className="grid gap-2">
                        <legend className="mb-1 text-sm font-medium">Alasan pembatalan</legend>

                        {reasons.map((alasan) => (
                            <label
                                key={alasan}
                                className={cn(
                                    'rounded-btn flex cursor-pointer items-center gap-3 border px-3 py-2 text-sm transition',
                                    data.reason_choice === alasan ? 'border-brand-700 bg-brand-50' : 'border-line hover:border-brand-400',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="reason_choice"
                                    value={alasan}
                                    checked={data.reason_choice === alasan}
                                    onChange={(e) => setData('reason_choice', e.target.value)}
                                    className="text-brand-700 focus-visible:ring-brand-600 h-4 w-4"
                                />
                                {alasan}
                            </label>
                        ))}

                        <InputError message={errors.reason_choice} />
                    </fieldset>

                    <div className="grid gap-2">
                        <Label htmlFor="reason_note">Keterangan {data.reason_choice === 'Lainnya' ? '' : '(opsional)'}</Label>
                        <Textarea
                            id="reason_note"
                            value={data.reason_note}
                            onChange={(e) => setData('reason_note', e.target.value)}
                            maxLength={200}
                            className="min-h-20"
                        />
                        <InputError message={errors.reason_note} />
                    </div>

                    <DialogFooter className="gap-2 sm:gap-2">
                        <Button type="button" variant="outline" onClick={() => setTerbuka(false)} disabled={processing}>
                            Kembali
                        </Button>

                        <Button type="submit" variant="destructive" disabled={processing || !data.reason_choice}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            Batalkan Booking
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
