import { Button } from '@/components/ui/button';
import { type QuickAction } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    bookingCode: string;
    actions: QuickAction[];
    /** Bagian props yang dimuat ulang setelah status berubah. */
    reloadOnly: string[];
}

/**
 * Aksi cepat ubah status di dashboard (docs/07 §A1, roadmap 2.1.2).
 *
 * Menembak rute `admin.bookings.status` yang SUDAH ADA — bukan rute baru
 * (keputusan grill #5). State machine, penulisan riwayat status, dan penolakan
 * transisi tidak sah seluruhnya sudah hidup di sana; menyalinnya ke jalur kedua
 * berarti mengulang cacat B2 sistem lama.
 *
 * Daftar `actions` datang dari server. Komponen ini tidak pernah menyimpulkan
 * sendiri transisi mana yang sah — menyembunyikan tombol bukan pengaman, dan
 * menampilkannya salah membuat orang menekan sesuatu yang pasti ditolak.
 */
export default function QuickStatusActions({ bookingCode, actions, reloadOnly }: Props) {
    const [sedangKirim, setSedangKirim] = useState<string | null>(null);

    if (actions.length === 0) {
        return <span className="text-ink-muted text-xs">Tidak ada aksi</span>;
    }

    const ubah = (status: string) => {
        setSedangKirim(status);

        router.put(
            route('admin.bookings.status', bookingCode),
            { status },
            {
                preserveScroll: true,
                // Hanya bagian yang berubah yang diminta ulang — inilah yang
                // membuat "tanpa berpindah halaman" benar-benar terasa cepat.
                only: reloadOnly,
                onFinish: () => setSedangKirim(null),
            },
        );
    };

    return (
        <div className="flex flex-wrap gap-2">
            {actions.map((aksi) => (
                <Button
                    key={aksi.value}
                    size="sm"
                    variant={aksi.value === 'no_show' ? 'outline' : 'default'}
                    disabled={sedangKirim !== null}
                    onClick={() => ubah(aksi.value)}
                >
                    {sedangKirim === aksi.value ? 'Menyimpan…' : aksi.label}
                </Button>
            ))}
        </div>
    );
}
