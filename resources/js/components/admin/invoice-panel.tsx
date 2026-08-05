import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { formatRupiah, formatTanggalSingkat } from '@/lib/format';
import { type InvoiceSummary } from '@/types';
import { Link, router } from '@inertiajs/react';
import { FilePlus2, FileText } from 'lucide-react';

interface Props {
    bookingCode: string;
    /** Invoice yang masih berlaku; null bila belum ada atau semuanya sudah di-void. */
    invoice: InvoiceSummary | null;
    /** Invoice yang sudah dibatalkan — tetap ditampilkan sebagai jejak. */
    history: InvoiceSummary[];
    /** Dihitung server (InvoicePolicy + status booking), bukan disimpulkan di sini. */
    canCreate: boolean;
}

/**
 * Panel invoice di detail booking (roadmap 2.4.2).
 *
 * Inilah **satu-satunya** pintu membuat invoice: daftar A8 sengaja tidak punya
 * tombol "Buat", karena invoice selalu lahir dari sebuah booking yang sudah
 * selesai (keputusan grill Q3).
 */
export default function InvoicePanel({ bookingCode, invoice, history, canCreate }: Props) {
    return (
        <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
            <h2 className="text-lg font-semibold">Invoice</h2>

            {invoice ? (
                <div className="mt-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p className="font-mono font-medium">{invoice.invoice_number ?? 'Draft'}</p>
                            <p className="text-ink-soft text-sm">
                                {formatRupiah(invoice.total)}
                                {invoice.issued_at && ` · terbit ${formatTanggalSingkat(invoice.issued_at)}`}
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            <StatusBadge status={invoice.status} kind="invoice" />
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('admin.invoices.show', invoice.id)}>
                                    <FileText className="h-4 w-4" aria-hidden="true" />
                                    Buka
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="mt-3">
                    {canCreate ? (
                        <>
                            <p className="text-ink-soft max-w-prose text-sm">
                                Booking ini belum punya invoice yang berlaku. Buat draftnya dari paket layanan, lalu
                                lengkapi jasa dan sparepart yang benar-benar dikerjakan.
                            </p>
                            <Button
                                className="mt-3"
                                onClick={() => router.post(route('admin.bookings.invoice.store', bookingCode))}
                            >
                                <FilePlus2 className="h-4 w-4" aria-hidden="true" />
                                Buat Invoice
                            </Button>
                        </>
                    ) : (
                        <p className="text-ink-soft max-w-prose text-sm">
                            Invoice dibuat otomatis begitu booking ditandai <strong>Selesai</strong>.
                        </p>
                    )}
                </div>
            )}

            {history.length > 0 && (
                <div className="border-line mt-4 border-t pt-3">
                    <h3 className="text-ink-soft text-sm font-semibold">Invoice yang dibatalkan</h3>
                    <ul className="mt-2 grid gap-2">
                        {history.map((lama) => (
                            <li key={lama.id} className="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <Link className="text-brand-700 font-mono underline" href={route('admin.invoices.show', lama.id)}>
                                    {lama.invoice_number ?? `Draft #${lama.id}`}
                                </Link>
                                <span className="text-ink-muted">{lama.void_reason}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </section>
    );
}
