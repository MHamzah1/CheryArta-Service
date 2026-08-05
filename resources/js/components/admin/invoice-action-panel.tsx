import ConfirmDialog from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type AdminInvoiceDetail, type SelectOption } from '@/types';
import { router } from '@inertiajs/react';
import { Ban, BadgeCheck, Download, Send } from 'lucide-react';
import { useState } from 'react';

interface Props {
    invoice: AdminInvoiceDetail;
    canIssue: boolean;
    canMarkPaid: boolean;
    canVoid: boolean;
    paymentMethodOptions: SelectOption[];
    errors: Record<string, string>;
}

/**
 * Tiga aksi status invoice + unduh PDF (docs/07 §A8).
 *
 * Setiap tombol dirender hanya bila SERVER mengizinkannya — nilai `can*`
 * dihitung InvoicePolicy dan state machine, bukan disimpulkan dari role di sini
 * (.claude/rules/20). Menyembunyikannya tetap sekadar kenyamanan: rutenya
 * menolak dengan 403 atau 422 bila dipaksakan.
 */
export default function InvoiceActionPanel({
    invoice,
    canIssue,
    canMarkPaid,
    canVoid,
    paymentMethodOptions,
    errors,
}: Props) {
    const [metode, setMetode] = useState(paymentMethodOptions[0]?.value ?? '');
    const [alasan, setAlasan] = useState('');

    return (
        <div className="grid gap-4">
            {canIssue && (
                <ConfirmDialog
                    trigger={
                        <Button className="w-full">
                            <Send className="h-4 w-4" aria-hidden="true" />
                            Terbitkan Invoice
                        </Button>
                    }
                    title="Terbitkan invoice ini?"
                    description="Nomor invoice akan terbit dan rinciannya tidak bisa disunting lagi. Koreksi setelah ini hanya bisa lewat pembatalan (void) oleh Super Admin, lalu membuat invoice pengganti."
                    confirmLabel="Terbitkan Invoice"
                    onConfirm={() => router.put(route('admin.invoices.issue', invoice.id), {}, { preserveScroll: true })}
                />
            )}

            {canMarkPaid && (
                <div className="border-line grid gap-2 rounded-xl border p-3">
                    <Label htmlFor="payment_method">Metode pembayaran</Label>
                    <Select value={metode} onValueChange={setMetode}>
                        <SelectTrigger id="payment_method">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {paymentMethodOptions.map((opsi) => (
                                <SelectItem key={opsi.value} value={opsi.value}>
                                    {opsi.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.payment_method} />

                    <Button
                        variant="secondary"
                        onClick={() =>
                            router.put(route('admin.invoices.paid', invoice.id), { payment_method: metode }, { preserveScroll: true })
                        }
                    >
                        <BadgeCheck className="h-4 w-4" aria-hidden="true" />
                        Tandai Lunas
                    </Button>
                </div>
            )}

            {invoice.invoice_number && (
                <Button variant="outline" asChild>
                    <a href={route('admin.invoices.pdf', invoice.id)}>
                        <Download className="h-4 w-4" aria-hidden="true" />
                        Unduh PDF
                    </a>
                </Button>
            )}

            {canVoid && (
                <div className="border-line grid gap-2 rounded-xl border p-3">
                    <Label htmlFor="void_reason">Alasan pembatalan</Label>
                    <Textarea
                        id="void_reason"
                        value={alasan}
                        onChange={(event) => setAlasan(event.target.value)}
                        placeholder="Mis. salah input harga sparepart"
                        rows={2}
                    />
                    <InputError message={errors.reason} />

                    <ConfirmDialog
                        trigger={
                            <Button variant="destructive" disabled={alasan.trim().length < 5}>
                                <Ban className="h-4 w-4" aria-hidden="true" />
                                Batalkan Invoice
                            </Button>
                        }
                        title="Batalkan invoice ini?"
                        description="Invoice akan berstatus Dibatalkan dan tetap tersimpan sebagai jejak — nomornya tidak dipakai ulang. Pelanggan yang sudah menerimanya akan melihat penanda Dibatalkan. Buat invoice pengganti dari detail booking."
                        confirmLabel="Batalkan Invoice"
                        onConfirm={() =>
                            router.put(route('admin.invoices.void', invoice.id), { reason: alasan }, { preserveScroll: true })
                        }
                    />
                </div>
            )}
        </div>
    );
}
