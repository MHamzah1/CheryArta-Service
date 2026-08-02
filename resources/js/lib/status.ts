/**
 * Peta status → label & warna.
 *
 * SATU-SATUNYA tempat warna status ditentukan. Dilarang menuliskan ulang
 * peta ini di halaman mana pun (.claude/rules/40-ui-design-system.md).
 *
 * Nilai status harus sama persis dengan App\Enums\BookingStatus
 * dan App\Enums\InvoiceStatus di sisi server.
 */

export type BookingStatusValue = 'pending' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled' | 'no_show';

export type InvoiceStatusValue = 'draft' | 'issued' | 'paid' | 'void';

export type StatusTone = 'pending' | 'confirmed' | 'progress' | 'done' | 'cancel' | 'noshow';

interface StatusMeta {
    label: string;
    tone: StatusTone;
}

export const BOOKING_STATUS: Record<BookingStatusValue, StatusMeta> = {
    pending: { label: 'Menunggu Konfirmasi', tone: 'pending' },
    confirmed: { label: 'Dikonfirmasi', tone: 'confirmed' },
    in_progress: { label: 'Sedang Dikerjakan', tone: 'progress' },
    completed: { label: 'Selesai', tone: 'done' },
    cancelled: { label: 'Dibatalkan', tone: 'cancel' },
    no_show: { label: 'Tidak Hadir', tone: 'noshow' },
};

export const INVOICE_STATUS: Record<InvoiceStatusValue, StatusMeta> = {
    draft: { label: 'Draft', tone: 'noshow' },
    issued: { label: 'Diterbitkan', tone: 'confirmed' },
    paid: { label: 'Lunas', tone: 'done' },
    void: { label: 'Dibatalkan', tone: 'cancel' },
};

/** Kelas Tailwind per nada warna — memakai token dari resources/css/app.css. */
export const TONE_CLASS: Record<StatusTone, string> = {
    pending: 'bg-status-pending-bg text-status-pending-fg',
    confirmed: 'bg-status-confirmed-bg text-status-confirmed-fg',
    progress: 'bg-status-progress-bg text-status-progress-fg',
    done: 'bg-status-done-bg text-status-done-fg',
    cancel: 'bg-status-cancel-bg text-status-cancel-fg',
    noshow: 'bg-status-noshow-bg text-status-noshow-fg',
};

export function bookingStatusMeta(status: string): StatusMeta {
    return BOOKING_STATUS[status as BookingStatusValue] ?? { label: status, tone: 'noshow' };
}

export function invoiceStatusMeta(status: string): StatusMeta {
    return INVOICE_STATUS[status as InvoiceStatusValue] ?? { label: status, tone: 'noshow' };
}
