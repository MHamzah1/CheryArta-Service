import { Button } from '@/components/ui/button';
import { type WhatsAppDraft } from '@/types';
import { Copy, MessageCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface Props {
    draft: WhatsAppDraft | null;
    customerName: string;
}

/**
 * Tombol "Chat via WhatsApp" (docs/07 §A7, keputusan R6).
 *
 * BIG FASE 1 sengaja sesederhana ini: membuka `wa.me` dengan teks yang sudah
 * disusun server dari `config/company.php`. Tidak ada gateway, tidak ada
 * pengiriman otomatis, dan BELUM ada log "sudah dikirim" — panel draft penuh
 * beserta tabel `whatsapp_messages` menyusul di F2.2.
 *
 * Teksnya disusun di server, bukan di sini: nomor yang dipakai harus yang
 * sudah ternormalisasi (62…), dan menyusunnya di React berarti nomor mentah
 * pelanggan bisa masuk ke URL (docs/08 §8.3).
 */
export default function WhatsAppPanel({ draft, customerName }: Props) {
    const [menyalin, setMenyalin] = useState(false);

    if (draft === null) {
        return (
            <p className="text-ink-soft text-sm">
                {customerName} belum punya nomor WhatsApp tersimpan, jadi pesan tidak bisa disiapkan. Lengkapi nomornya lebih dulu dari
                halaman customer.
            </p>
        );
    }

    const salin = async () => {
        try {
            await navigator.clipboard.writeText(draft.message);
            setMenyalin(true);
            toast.success('Pesan disalin ke papan klip.');
            window.setTimeout(() => setMenyalin(false), 2000);
        } catch {
            toast.error('Pesan gagal disalin. Sorot teksnya lalu salin secara manual.');
        }
    };

    return (
        <div className="grid gap-3">
            <p className="text-ink-soft text-sm">
                Nomor tujuan: <span className="text-ink font-medium">{draft.phone_display}</span>
            </p>

            {/* Teks pesan dirender sebagai teks biasa — React meng-escape-nya
                secara bawaan, dan dangerouslySetInnerHTML dilarang (temuan S5). */}
            <p className="border-line bg-canvas max-w-prose rounded-xl border p-3 text-sm whitespace-pre-line">{draft.message}</p>

            <div className="flex flex-wrap gap-2">
                <Button asChild>
                    <a href={draft.url} target="_blank" rel="noopener noreferrer">
                        <MessageCircle className="h-4 w-4" aria-hidden="true" />
                        Chat via WhatsApp
                    </a>
                </Button>

                <Button type="button" variant="outline" onClick={salin}>
                    <Copy className="h-4 w-4" aria-hidden="true" />
                    {menyalin ? 'Tersalin' : 'Salin Pesan'}
                </Button>
            </div>

            <p className="text-ink-muted text-sm">
                Pesan tidak terkirim otomatis — tekan kirim di WhatsApp. Penanda "sudah dikirim" dan riwayat pengiriman dibangun pada
                tahap berikutnya.
            </p>
        </div>
    );
}
