import ConfirmDialog from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { formatTanggalJamIso } from '@/lib/format';
import { TONE_CLASS, type StatusTone } from '@/lib/status';
import { type WhatsAppMessage } from '@/types';
import { router } from '@inertiajs/react';

interface Props {
    bookingCode: string;
    messages: WhatsAppMessage[];
}

/**
 * Riwayat pesan untuk satu booking (docs/08 §8.8, keputusan grill Q7).
 *
 * Inilah pembaca alami log `whatsapp_messages` — pertanyaan "sudah dikabari
 * belum?" selalu muncul saat menatap satu booking, bukan saat menatap tabel
 * global. Karena itu tidak ada layar log tersendiri.
 *
 * Isinya arsip: menyunting template tidak mengubah pesan yang sudah tercatat
 * di sini, dan barisnya tidak pernah dihapus (.claude/rules/30).
 */
export default function WhatsAppMessageHistory({ bookingCode, messages }: Props) {
    if (messages.length === 0) {
        return <p className="text-ink-soft text-sm">Belum ada pesan yang dibuka untuk booking ini.</p>;
    }

    const tandaiTerkirim = (id: number) => {
        router.put(route('admin.bookings.whatsapp.sent', [bookingCode, id]), {}, { preserveScroll: true });
    };

    const lewati = (id: number) => {
        router.put(route('admin.bookings.whatsapp.skip', [bookingCode, id]), {}, { preserveScroll: true });
    };

    return (
        <ol className="grid gap-4">
            {messages.map((pesan) => (
                <li key={pesan.id} className="border-line border-l-2 pl-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="font-medium">{pesan.template_label}</span>
                        <span
                            className={`rounded-full px-2 py-0.5 text-xs font-medium ${TONE_CLASS[pesan.tone as StatusTone] ?? TONE_CLASS.noshow}`}
                        >
                            {pesan.status_label}
                        </span>
                    </div>

                    <p className="text-ink-muted text-sm">
                        Dibuka {formatTanggalJamIso(pesan.created_at)}
                        {pesan.actor_name && ` · ${pesan.actor_name}`}
                        {pesan.sent_at && ` · ditandai terkirim ${formatTanggalJamIso(pesan.sent_at)}`}
                    </p>

                    {/* Teks arsip dirender sebagai teks biasa — React
                        meng-escape-nya secara bawaan, dan dangerouslySetInnerHTML
                        dilarang (temuan S5). */}
                    <p className="text-ink-soft mt-1 max-w-prose text-sm whitespace-pre-line">{pesan.message}</p>

                    {!pesan.is_settled && (
                        <div className="mt-2 flex flex-wrap gap-2">
                            <Button type="button" size="sm" onClick={() => tandaiTerkirim(pesan.id)}>
                                Tandai Terkirim
                            </Button>

                            <ConfirmDialog
                                trigger={
                                    <Button type="button" size="sm" variant="outline">
                                        Lewati
                                    </Button>
                                }
                                title="Lewati pesan ini?"
                                description="Booking ini akan hilang dari hitungan “Belum dikabari” tanpa pesan yang benar-benar terkirim. Pakai ini bila pelanggan memang tidak perlu dikabari."
                                confirmLabel="Lewati Pesan Ini"
                                onConfirm={() => lewati(pesan.id)}
                            />
                        </div>
                    )}
                </li>
            ))}
        </ol>
    );
}
