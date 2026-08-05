import { Button } from '@/components/ui/button';
import { type WhatsAppDraft, type WhatsAppPanelProps } from '@/types';
import { router } from '@inertiajs/react';
import { AlertTriangle, BellRing, Copy, MessageCircle } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { toast } from 'sonner';

interface Props {
    panel: WhatsAppPanelProps;
    bookingCode: string;
    customerName: string;
}

const ALASAN: Record<NonNullable<WhatsAppPanelProps['reason']>, string> = {
    tanpa_nomor: 'belum punya nomor WhatsApp tersimpan, jadi pesannya tidak bisa disiapkan. Lengkapi nomornya lebih dulu dari halaman customer.',
    template_nonaktif:
        'seharusnya dikabari, tetapi template untuk status ini sedang dinonaktifkan. Aktifkan kembali dari halaman Template WA bila pesannya memang diperlukan.',
};

/**
 * Panel WhatsApp penuh di detail booking (docs/07 §A7, docs/08 §8.2).
 *
 * Teksnya disusun SERVER dan tidak bisa disunting di sini: kotak ketik WhatsApp
 * sendiri sudah menyediakan penyuntingan, dan `rendered_message` di log harus
 * tetap bisa dipercaya sebagai buatan server (keputusan grill Q2).
 *
 * Tombolnya `<a href>` sungguhan, BUKAN window.open() sesudah menunggu respons:
 * peramban memblokir jendela baru yang dibuka di luar tumpukan gestur pengguna.
 * Penekanan yang sama memicu router.post yang mencatat barisnya — pencatatan
 * memang baru terjadi di sini, bukan saat status berubah (keputusan grill Q1).
 */
export default function WhatsAppPanel({ panel, bookingCode, customerName }: Props) {
    const [menyalin, setMenyalin] = useState(false);
    const draft = panel.draft;

    const catat = (templateKey: string) => {
        router.post(
            route('admin.bookings.whatsapp.store', bookingCode),
            { template_key: templateKey },
            { preserveScroll: true },
        );
    };

    const salin = async (pesan: string) => {
        try {
            await navigator.clipboard.writeText(pesan);
            setMenyalin(true);
            toast.success('Pesan disalin ke papan klip.');
            window.setTimeout(() => setMenyalin(false), 2000);
        } catch {
            toast.error('Pesan gagal disalin. Sorot teksnya lalu salin secara manual.');
        }
    };

    const TombolBuka = ({ tautan, label, icon }: { tautan: WhatsAppDraft; label: string; icon: ReactNode }) => (
        <Button asChild>
            <a href={tautan.url} target="_blank" rel="noopener noreferrer" onClick={() => catat(tautan.template_key)}>
                {icon}
                {label}
            </a>
        </Button>
    );

    return (
        <div className="grid gap-3">
            {panel.awaiting && (
                <p className="bg-status-cancel-bg text-status-cancel-fg flex items-start gap-2 rounded-xl px-3 py-2 text-sm font-medium">
                    <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                    Belum dikabari — pelanggan belum menerima pesan “{panel.template_label}”.
                </p>
            )}

            {draft === null ? (
                <p className="text-ink-soft max-w-prose text-sm">
                    {customerName} {panel.reason === null ? 'tidak punya pesan yang bisa dikirim.' : ALASAN[panel.reason]}
                </p>
            ) : (
                <>
                    <p className="text-ink-soft text-sm">
                        Nomor tujuan: <span className="text-ink font-medium">{draft.phone_display}</span>
                    </p>

                    {/* Teks pesan dirender sebagai teks biasa — React meng-escape-nya
                        secara bawaan, dan dangerouslySetInnerHTML dilarang (temuan S5). */}
                    <p className="border-line bg-canvas max-w-prose rounded-xl border p-3 text-sm whitespace-pre-line">
                        {draft.message}
                    </p>

                    <div className="flex flex-wrap gap-2">
                        <TombolBuka
                            tautan={draft}
                            label="Buka WhatsApp"
                            icon={<MessageCircle className="h-4 w-4" aria-hidden="true" />}
                        />

                        <Button type="button" variant="outline" onClick={() => salin(draft.message)}>
                            <Copy className="h-4 w-4" aria-hidden="true" />
                            {menyalin ? 'Tersalin' : 'Salin Pesan'}
                        </Button>
                    </div>
                </>
            )}

            {panel.reminder !== null && (
                <div className="border-line grid gap-2 border-t pt-3">
                    <p className="text-ink-soft max-w-prose text-sm">
                        Booking ini sudah dikonfirmasi dan jadwalnya masih di depan, jadi pengingat bisa dikirim. Untuk
                        mengirim banyak pengingat sekaligus, pakai halaman Jadwal.
                    </p>

                    <div className="flex flex-wrap gap-2">
                        <TombolBuka
                            tautan={panel.reminder}
                            label="Kirim Pengingat"
                            icon={<BellRing className="h-4 w-4" aria-hidden="true" />}
                        />
                    </div>
                </div>
            )}

            <p className="text-ink-muted max-w-prose text-sm">
                Pesan tidak terkirim otomatis — tekan kirim di WhatsApp, lalu tandai di riwayat di bawah. Kalimat yang
                Anda tambahkan di WhatsApp tidak ikut tercatat.
            </p>
        </div>
    );
}
