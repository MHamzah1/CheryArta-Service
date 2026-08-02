/**
 * Pemformatan tampilan — SATU-SATUNYA tempat format rupiah, tanggal, jam,
 * plat, dan telepon ditentukan.
 *
 * Dilarang memformat secara ad-hoc di dalam halaman
 * (lihat .claude/rules/20-frontend-react-inertia.md).
 */

const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

const BULAN = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

/** 450000 → "Rp 450.000" · null → "-" */
export function formatRupiah(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '-';

    const amount = typeof value === 'string' ? Number(value) : value;
    if (Number.isNaN(amount)) return '-';

    return `Rp ${Math.round(amount).toLocaleString('id-ID')}`;
}

/** Paket gratis ditampilkan sebagai "Gratis", bukan "Rp 0". */
export function formatHargaPaket(price: number | string | null, isFree: boolean): string {
    return isFree ? 'Gratis' : formatRupiah(price);
}

/**
 * "2026-08-03" → "Senin, 3 Agustus 2026"
 * Sengaja mem-parse manual agar tidak terpengaruh zona waktu peramban —
 * sistem lama salah satu hari karena memakai toISOString() (temuan B7).
 */
export function formatTanggal(value: string | null | undefined, withDay = true): string {
    if (!value) return '-';

    const [datePart] = value.split('T');
    const [year, month, day] = datePart.split('-').map(Number);
    if (!year || !month || !day) return value;

    const date = new Date(year, month - 1, day);
    const teks = `${day} ${BULAN[month - 1]} ${year}`;

    return withDay ? `${HARI[date.getDay()]}, ${teks}` : teks;
}

/** "2026-08-03" → "3 Agu 2026" (untuk tabel padat) */
export function formatTanggalSingkat(value: string | null | undefined): string {
    if (!value) return '-';

    const [datePart] = value.split('T');
    const [year, month, day] = datePart.split('-').map(Number);
    if (!year || !month || !day) return value;

    return `${day} ${BULAN[month - 1].slice(0, 3)} ${year}`;
}

/** "09:00:00" → "09.00" (konvensi penulisan jam Indonesia) */
export function formatJam(value: string | null | undefined): string {
    if (!value) return '-';

    const [hour, minute] = value.split(':');

    return `${hour}.${minute ?? '00'}`;
}

/** "2026-08-03" + "09:00" → "Senin, 3 Agustus 2026 pukul 09.00" */
export function formatJadwal(date: string | null, time: string | null): string {
    if (!date) return '-';

    return time ? `${formatTanggal(date)} pukul ${formatJam(time)}` : formatTanggal(date);
}

/** "B-1234-ABC" → "B 1234 ABC" */
export function formatPlat(value: string | null | undefined): string {
    return value ? value.replace(/-/g, ' ').toUpperCase() : '-';
}

/** "6281234567890" → "0812-3456-7890" */
export function formatTelepon(value: string | null | undefined): string {
    if (!value) return '-';

    let digits = value.replace(/\D+/g, '');
    if (digits.startsWith('62')) digits = `0${digits.slice(2)}`;
    if (digits.length < 9) return digits;

    return [digits.slice(0, 4), digits.slice(4, 8), digits.slice(8)].join('-');
}

/** 90 → "1 jam 30 menit" */
export function formatDurasi(minutes: number | null | undefined): string {
    if (!minutes) return '-';

    const jam = Math.floor(minutes / 60);
    const menit = minutes % 60;

    if (jam === 0) return `${menit} menit`;
    if (menit === 0) return `${jam} jam`;

    return `${jam} jam ${menit} menit`;
}
