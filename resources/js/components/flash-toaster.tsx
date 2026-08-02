import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

/**
 * Menampilkan pesan flash dari server sebagai toast.
 *
 * Menggantikan alert() sistem lama yang memblokir interaksi. Dipasang sekali
 * di setiap layout; tidak perlu dipanggil dari halaman.
 */
export function FlashToaster() {
    const { flash } = usePage<SharedData>().props;
    const terakhir = useRef<string>('');

    useEffect(() => {
        if (!flash) return;

        const pesan = flash.success ?? flash.error ?? flash.info;
        if (!pesan) return;

        // Cegah toast kembar saat komponen dirender ulang tanpa flash baru.
        const sidik = `${flash.success ?? ''}|${flash.error ?? ''}|${flash.info ?? ''}`;
        if (sidik === terakhir.current) return;
        terakhir.current = sidik;

        if (flash.success) toast.success(flash.success);
        else if (flash.error) toast.error(flash.error);
        else toast.info(pesan);
    }, [flash]);

    return null;
}
