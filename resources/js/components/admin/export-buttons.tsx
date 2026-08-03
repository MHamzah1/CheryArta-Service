import { Button } from '@/components/ui/button';
import { ClipboardCopy, Download } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface Props {
    /** URL export TANPA parameter `format` — saringan aktif sudah menempel. */
    baseUrl: string;
}

/**
 * Dua tombol export (docs/07 §A9), mempertahankan kedua fitur sistem lama.
 *
 * **Unduh CSV** — berkas dibuat server, dibuka Excel secara langsung.
 * **Salin untuk Spreadsheet** — TSV ke clipboard, persis perilaku lama.
 *
 * `fetch` di sini bukan pelanggaran `.claude/rules/20`: yang dilarang adalah
 * mengambil DATA HALAMAN lewat fetch. Ini aksi yang dipicu pengguna, dan
 * datanya memang harus dari server — yang disalin adalah SELURUH hasil
 * saringan, bukan 25 baris halaman berjalan.
 */
export default function ExportButtons({ baseUrl }: Props) {
    const [menyalin, setMenyalin] = useState(false);

    const pisah = baseUrl.includes('?') ? '&' : '?';

    const salin = async () => {
        setMenyalin(true);

        try {
            const respons = await fetch(`${baseUrl}${pisah}format=tsv`, {
                headers: { Accept: 'text/plain' },
                credentials: 'same-origin',
            });

            if (!respons.ok) {
                throw new Error(String(respons.status));
            }

            const teks = await respons.text();

            // Clipboard API hanya jalan di konteks aman (HTTPS) dan menuntut
            // gestur pengguna. Kegagalannya WAJIB terdengar — penyalinan yang
            // gagal diam-diam membuat orang menempel data lama tanpa sadar.
            await navigator.clipboard.writeText(teks);

            toast.success('Data disalin. Tempel di Google Sheets atau Excel.');
        } catch {
            toast.error('Penyalinan gagal. Gunakan tombol Unduh CSV sebagai gantinya.');
        } finally {
            setMenyalin(false);
        }
    };

    return (
        <div className="flex flex-wrap gap-2">
            <Button variant="outline" size="sm" asChild>
                <a href={`${baseUrl}${pisah}format=csv`}>
                    <Download className="h-4 w-4" aria-hidden="true" />
                    Unduh CSV
                </a>
            </Button>

            <Button variant="outline" size="sm" onClick={salin} disabled={menyalin}>
                <ClipboardCopy className="h-4 w-4" aria-hidden="true" />
                {menyalin ? 'Menyalin…' : 'Salin untuk Spreadsheet'}
            </Button>
        </div>
    );
}
