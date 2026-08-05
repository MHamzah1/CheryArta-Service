import InvoiceItemsTable from '@/components/invoice-items-table';
import InvoiceSummaryCard from '@/components/invoice-summary-card';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import CustomerLayout from '@/layouts/customer-layout';
import { formatJadwal, formatPlat, formatTanggal } from '@/lib/format';
import { type OwnerInvoiceDetail } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';

interface Props {
    invoice: OwnerInvoiceDetail;
}

/**
 * Invoice yang dilihat pemiliknya (US-C7, roadmap 2.4.4).
 *
 * Halaman ini hanya pernah dicapai untuk invoice yang SUDAH diterbitkan —
 * yang masih draft dijawab 404 oleh controllernya (keputusan grill Q7).
 * Invoice yang kemudian dibatalkan tetap terbuka, dan penanda pembatalannya
 * ditampilkan menonjol: pelanggan yang sudah menyimpan PDF-nya perlu tahu
 * bahwa tagihan itu tidak lagi berlaku.
 */
export default function InvoiceShow({ invoice }: Props) {
    const dibatalkan = invoice.status === 'void';

    return (
        <CustomerLayout>
            <Head title={`Invoice ${invoice.invoice_number}`} />

            <div className="mb-6">
                <Button variant="outline" size="sm" asChild>
                    <Link href={route('customer.booking.show', invoice.booking.booking_code)}>
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Kembali ke Booking
                    </Link>
                </Button>
            </div>

            {dibatalkan && (
                <p className="bg-status-cancel-bg text-status-cancel-fg mb-6 rounded-2xl px-4 py-3 text-sm">
                    <strong>Invoice ini telah dibatalkan</strong> dan tidak perlu dibayar.
                    {invoice.void_reason && ` Alasan: ${invoice.void_reason}.`} Hubungi bengkel bila Anda sudah terlanjur
                    membayar tagihan ini.
                </p>
            )}

            <div className="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
                <div className="grid gap-6">
                    <div className="border-line bg-surface shadow-card rounded-2xl border p-5">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p className="text-brand-700 font-mono text-lg font-bold tracking-wide">
                                    {invoice.invoice_number}
                                </p>
                                <p className="text-ink-soft mt-1 text-sm">
                                    Diterbitkan {formatTanggal(invoice.issued_at)}
                                </p>
                            </div>

                            <StatusBadge status={invoice.status} kind="invoice" />
                        </div>

                        <dl className="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-ink-soft">Kode booking</dt>
                                <dd className="font-medium">{invoice.booking.booking_code}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-soft">Jadwal servis</dt>
                                <dd className="font-medium">
                                    {formatJadwal(invoice.booking.booking_date, invoice.booking.booking_time)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-soft">Kendaraan</dt>
                                <dd className="font-medium">
                                    {invoice.booking.vehicle_model} · {formatPlat(invoice.booking.vehicle_plate)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-soft">Paket layanan</dt>
                                <dd className="font-medium">{invoice.booking.package_name}</dd>
                            </div>
                        </dl>
                    </div>

                    <div className="border-line bg-surface shadow-card rounded-2xl border p-5">
                        <h2 className="mb-4 text-lg font-semibold">Rincian Biaya</h2>
                        <InvoiceItemsTable items={invoice.items} />
                    </div>
                </div>

                <div className="grid content-start gap-6">
                    <div className="border-line bg-surface shadow-card rounded-2xl border p-5">
                        <h2 className="mb-4 text-lg font-semibold">Ringkasan</h2>
                        <InvoiceSummaryCard
                            subtotal={invoice.subtotal}
                            discount={invoice.discount}
                            tax={invoice.tax}
                            total={invoice.total}
                        />

                        {invoice.status === 'paid' && (
                            <p className="bg-status-done-bg text-status-done-fg mt-4 rounded-xl px-3 py-2 text-sm">
                                Lunas
                                {invoice.payment_method_label && ` · ${invoice.payment_method_label}`}
                            </p>
                        )}

                        <Button className="mt-4 w-full" asChild>
                            <a href={route('customer.invoice.pdf', invoice.id)}>
                                <Download className="h-4 w-4" aria-hidden="true" />
                                Unduh PDF
                            </a>
                        </Button>
                    </div>
                </div>
            </div>
        </CustomerLayout>
    );
}
