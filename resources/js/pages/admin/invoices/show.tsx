import InvoiceActionPanel from '@/components/admin/invoice-action-panel';
import InvoiceItemRows, { baris, type DraftItem } from '@/components/admin/invoice-item-rows';
import InputError from '@/components/input-error';
import InvoiceItemsTable from '@/components/invoice-items-table';
import InvoiceSummaryCard from '@/components/invoice-summary-card';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { formatJam, formatTanggal, formatTanggalJamIso } from '@/lib/format';
import { type AdminInvoiceDetail, type SelectOption } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';

interface Props {
    invoice: AdminInvoiceDetail;
    canEdit: boolean;
    canIssue: boolean;
    canMarkPaid: boolean;
    canVoid: boolean;
    itemTypeOptions: SelectOption[];
    paymentMethodOptions: SelectOption[];
}

/** Bentuk yang dikirim ke `admin.invoices.update`. Tanpa `subtotal` dan `total`: keduanya milik server. */
interface InvoiceFormData {
    items: DraftItem[];
    discount: string;
    tax: string;
    notes: string;
    /** Dituntut `FormDataConvertible` milik Inertia — pola yang sama dengan CarModelFormData. */
    [key: string]: string | DraftItem[];
}

/**
 * A8 — penyunting invoice, sekaligus halaman baca-saja setelah diterbitkan.
 *
 * `canEdit` datang dari server (InvoicePolicy + InvoiceStatus::isEditable).
 * Ia menentukan mana dari dua bentuk yang dirender — bukan menonaktifkan
 * tombol simpan, karena form yang terlihat bisa diisi tetapi selalu ditolak
 * lebih membingungkan daripada tidak ada form sama sekali.
 */
export default function InvoiceShow({
    invoice,
    canEdit,
    canIssue,
    canMarkPaid,
    canVoid,
    itemTypeOptions,
    paymentMethodOptions,
}: Props) {
    const form = useForm<InvoiceFormData>({
        items: invoice.items.map(
            (item): DraftItem => ({
                key: `item-${item.id}`,
                type: item.type,
                description: item.description,
                qty: item.qty,
                unit_price: item.unit_price,
            }),
        ),
        discount: invoice.discount,
        tax: invoice.tax,
        notes: invoice.notes ?? '',
    });

    // Pratinjau saja — angka yang tersimpan selalu hasil hitungan server
    // (docs/05 §5.6). Lihat catatan di InvoiceItemRows.
    const subtotalPratinjau = form.data.items.reduce(
        (jumlah, item) => jumlah + Number(item.qty || 0) * Number(item.unit_price || 0),
        0,
    );
    const totalPratinjau = subtotalPratinjau - Number(form.data.discount || 0) + Number(form.data.tax || 0);

    const simpan = () => form.put(route('admin.invoices.update', invoice.id), { preserveScroll: true });

    return (
        <AdminLayout
            title={invoice.invoice_number ?? 'Invoice Draft'}
            description={`Booking ${invoice.booking_code} · ${invoice.booking.customer_name}`}
            actions={
                <Button variant="outline" asChild>
                    <Link href={route('admin.invoices.index')}>
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Daftar Invoice
                    </Link>
                </Button>
            }
        >
            <Head title={invoice.invoice_number ?? 'Invoice Draft'} />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="grid gap-6 lg:col-span-2">
                    <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h2 className="text-lg font-semibold">Servis</h2>
                            <StatusBadge status={invoice.status} kind="invoice" />
                        </div>

                        <dl className="mt-3 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                            <div className="flex justify-between gap-4 sm:block">
                                <dt className="text-ink-soft">Kode booking</dt>
                                <dd>
                                    <Link className="text-brand-700 underline" href={route('admin.bookings.show', invoice.booking_code)}>
                                        {invoice.booking_code}
                                    </Link>
                                </dd>
                            </div>
                            <div className="flex justify-between gap-4 sm:block">
                                <dt className="text-ink-soft">Jadwal</dt>
                                <dd>
                                    {formatTanggal(invoice.booking.booking_date)} · {formatJam(invoice.booking.booking_time)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-4 sm:block">
                                <dt className="text-ink-soft">Kendaraan</dt>
                                <dd>
                                    {invoice.booking.vehicle_model} · {invoice.booking.vehicle_plate}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-4 sm:block">
                                <dt className="text-ink-soft">Paket layanan</dt>
                                <dd>{invoice.booking.package_name}</dd>
                            </div>
                        </dl>
                    </section>

                    <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
                        <h2 className="text-lg font-semibold">Rincian Biaya</h2>

                        {canEdit ? (
                            <div className="mt-3 grid gap-4">
                                <InvoiceItemRows
                                    items={form.data.items}
                                    onChange={(items) => form.setData('items', items)}
                                    typeOptions={itemTypeOptions}
                                    errors={form.errors as Record<string, string>}
                                    disabled={form.processing}
                                />

                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <Label htmlFor="discount">Diskon (Rp)</Label>
                                        <Input
                                            id="discount"
                                            type="number"
                                            inputMode="numeric"
                                            step="0.01"
                                            min="0"
                                            value={form.data.discount}
                                            onChange={(event) => form.setData('discount', event.target.value)}
                                        />
                                        <InputError message={form.errors.discount} />
                                    </div>
                                    <div>
                                        <Label htmlFor="tax">Pajak (Rp)</Label>
                                        <Input
                                            id="tax"
                                            type="number"
                                            inputMode="numeric"
                                            step="0.01"
                                            min="0"
                                            value={form.data.tax}
                                            onChange={(event) => form.setData('tax', event.target.value)}
                                        />
                                        <InputError message={form.errors.tax} />
                                    </div>
                                </div>

                                <div>
                                    <Label htmlFor="notes">Catatan untuk pelanggan</Label>
                                    <Textarea
                                        id="notes"
                                        rows={2}
                                        value={form.data.notes}
                                        onChange={(event) => form.setData('notes', event.target.value)}
                                        placeholder="Tampil di invoice dan PDF. Catatan internal tetap di detail booking."
                                    />
                                    <InputError message={form.errors.notes} />
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <Button onClick={simpan} disabled={form.processing}>
                                        <Save className="h-4 w-4" aria-hidden="true" />
                                        Simpan Rincian
                                    </Button>
                                    {form.data.items.length === 0 && (
                                        <Button type="button" variant="outline" onClick={() => form.setData('items', [baris()])}>
                                            Tambah Baris Pertama
                                        </Button>
                                    )}
                                </div>
                            </div>
                        ) : (
                            <div className="mt-3">
                                <InvoiceItemsTable items={invoice.items} />
                                {invoice.notes && <p className="text-ink-soft mt-3 max-w-prose text-sm">{invoice.notes}</p>}
                            </div>
                        )}
                    </section>
                </div>

                <div className="grid content-start gap-6">
                    <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
                        <h2 className="mb-3 text-lg font-semibold">Ringkasan</h2>
                        <InvoiceSummaryCard
                            subtotal={canEdit ? subtotalPratinjau : invoice.subtotal}
                            discount={form.data.discount}
                            tax={form.data.tax}
                            total={canEdit ? totalPratinjau : invoice.total}
                            preview={canEdit}
                        />

                        <dl className="text-ink-soft mt-4 space-y-1 text-xs">
                            {invoice.issued_at && (
                                <div className="flex justify-between gap-3">
                                    <dt>Diterbitkan</dt>
                                    <dd>{formatTanggalJamIso(invoice.issued_at)}</dd>
                                </div>
                            )}
                            {invoice.paid_at && (
                                <div className="flex justify-between gap-3">
                                    <dt>Lunas ({invoice.payment_method_label})</dt>
                                    <dd>{formatTanggalJamIso(invoice.paid_at)}</dd>
                                </div>
                            )}
                            {invoice.created_by_name && (
                                <div className="flex justify-between gap-3">
                                    <dt>Dibuat oleh</dt>
                                    <dd>{invoice.created_by_name}</dd>
                                </div>
                            )}
                        </dl>

                        {invoice.void_reason && (
                            <p className="bg-status-cancel-bg text-status-cancel-fg mt-3 rounded-xl px-3 py-2 text-sm">
                                Dibatalkan: {invoice.void_reason}
                            </p>
                        )}
                    </section>

                    <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
                        <h2 className="mb-3 text-lg font-semibold">Tindakan</h2>
                        <InvoiceActionPanel
                            invoice={invoice}
                            canIssue={canIssue}
                            canMarkPaid={canMarkPaid}
                            canVoid={canVoid}
                            paymentMethodOptions={paymentMethodOptions}
                            errors={form.errors as Record<string, string>}
                        />
                    </section>
                </div>
            </div>
        </AdminLayout>
    );
}
