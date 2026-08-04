import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Copy, KeyRound } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

/**
 * Password sementara akun staf — tampil SEKALI, lalu tidak pernah lagi.
 *
 * Tanpa notifikasi email (keputusan final #4), tidak ada yang mengirimkan
 * password ini ke pemiliknya; Super Admin menyampaikannya lewat WhatsApp atau
 * lisan. Karena itu ia harus terlihat tepat setelah aksi berhasil, disertai
 * peringatan bahwa ia tidak bisa dilihat lagi.
 *
 * Nilainya datang lewat flash session, jadi ia hilang sendiri begitu halaman
 * dimuat ulang. Ia tidak pernah tersimpan terbaca di mana pun, dan tidak
 * pernah masuk activity log (.claude/rules/50).
 *
 * Masa berlakunya dipersempit `must_reset_password`: password ini hanya sah
 * untuk satu kali masuk, lalu pemiliknya wajib menetapkan sendiri.
 */
export default function TemporaryPasswordPanel() {
    const { flash } = usePage<SharedData>().props;
    const data = flash?.temporaryPassword;
    const [tersalin, setTersalin] = useState(false);

    if (!data) return null;

    const salin = async () => {
        try {
            await navigator.clipboard.writeText(data.password);
            setTersalin(true);
            toast.success('Password disalin.');
        } catch {
            // Clipboard API menolak diam-diam di konteks tidak aman — jangan
            // gagal tanpa suara, passwordnya masih terbaca di layar.
            toast.error('Penyalinan gagal. Salin manual dari layar.');
        }
    };

    return (
        <section
            className="border-gold-400 bg-gold-400/10 mb-6 rounded-2xl border p-5"
            aria-labelledby="judul-password-sementara"
        >
            <div className="flex items-start gap-3">
                <KeyRound className="text-brand-700 mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />

                <div className="min-w-0 flex-1">
                    <h2 id="judul-password-sementara" className="font-semibold">
                        Password sementara untuk {data.name}
                    </h2>

                    <p className="text-ink-soft mt-1 text-sm">
                        Catat sekarang — password ini <strong>tidak bisa dilihat lagi</strong> setelah halaman ini
                        ditutup. Sampaikan ke pemiliknya lewat WhatsApp; ia wajib menggantinya saat pertama masuk.
                    </p>

                    <div className="mt-3 flex flex-wrap items-center gap-2">
                        <code className="border-line bg-surface rounded-xl border px-3 py-2 font-mono text-base tracking-wider">
                            {data.password}
                        </code>

                        <Button type="button" variant="outline" size="sm" onClick={salin}>
                            <Copy className="h-4 w-4" aria-hidden="true" />
                            {tersalin ? 'Tersalin' : 'Salin'}
                        </Button>
                    </div>
                </div>
            </div>
        </section>
    );
}
