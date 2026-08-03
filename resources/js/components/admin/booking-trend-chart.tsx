import { formatTanggalSingkat } from '@/lib/format';
import { type TrendPoint } from '@/types';
import { useId } from 'react';

interface Props {
    points: TrendPoint[];
    /** Jumlah hari yang dicakup, dari config/booking.php — bukan angka di sini. */
    days: number;
}

/**
 * Grafik garis tren booking (docs/07 §A1).
 *
 * SVG yang digambar sendiri, bukan pustaka grafik. Alasannya bukan selera:
 * satu berkas 120 baris menggantikan ±100 KB di bundel, dan `.claude/rules/20`
 * menuntut halaman publik tidak ikut menanggung berat panel admin. Bila kelak
 * dibutuhkan grafik yang benar-benar interaktif, yang diganti hanya berkas ini.
 *
 * Deret tanggalnya dibangun LENGKAP di server, termasuk hari bernilai nol —
 * lihat DashboardService::trend(). Komponen ini tidak boleh menyimpulkan
 * tanggal sendiri: menghitung "kemarin" dari zona waktu peramban adalah bentuk
 * lain dari temuan B7.
 */
export default function BookingTrendChart({ points, days }: Props) {
    const gradientId = useId();

    if (points.length === 0) {
        return <p className="text-ink-muted text-sm">Belum ada data untuk digambar.</p>;
    }

    const total = points.reduce((jumlah, titik) => jumlah + titik.count, 0);
    const tertinggi = Math.max(...points.map((titik) => titik.count));
    const puncak = points.find((titik) => titik.count === tertinggi);

    // Skala Y selalu punya ruang di atas garis tertinggi, dan tidak pernah nol
    // — deret yang seluruhnya nol tetap harus menggambar garis dasar, bukan
    // membagi dengan nol.
    const skalaY = Math.max(tertinggi, 1);

    const lebar = 100;
    const tinggi = 32;

    const koordinat = points.map((titik, index) => {
        const x = points.length === 1 ? lebar / 2 : (index / (points.length - 1)) * lebar;
        const y = tinggi - (titik.count / skalaY) * tinggi;

        return { x, y, titik };
    });

    const garis = koordinat.map(({ x, y }) => `${x.toFixed(2)},${y.toFixed(2)}`).join(' ');
    const area = `0,${tinggi} ${garis} ${lebar},${tinggi}`;

    return (
        <div className="grid gap-3">
            <svg
                viewBox={`0 0 ${lebar} ${tinggi}`}
                preserveAspectRatio="none"
                className="h-40 w-full"
                role="img"
                aria-label={`Grafik tren booking ${days} hari terakhir. Total ${total} booking, tertinggi ${tertinggi} pada ${puncak ? formatTanggalSingkat(puncak.date) : '-'}.`}
            >
                <defs>
                    <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" className="text-brand-500" stopColor="currentColor" stopOpacity="0.28" />
                        <stop offset="100%" className="text-brand-500" stopColor="currentColor" stopOpacity="0" />
                    </linearGradient>
                </defs>

                {/* Garis bantu horizontal — memberi mata acuan tanpa sumbu penuh. */}
                {[0.25, 0.5, 0.75].map((bagian) => (
                    <line
                        key={bagian}
                        x1="0"
                        x2={lebar}
                        y1={tinggi * bagian}
                        y2={tinggi * bagian}
                        className="text-line"
                        stroke="currentColor"
                        strokeWidth="0.15"
                        strokeDasharray="1 1.5"
                    />
                ))}

                <polygon points={area} fill={`url(#${gradientId})`} />

                <polyline
                    points={garis}
                    fill="none"
                    className="text-brand-600"
                    stroke="currentColor"
                    strokeWidth="0.6"
                    strokeLinejoin="round"
                    strokeLinecap="round"
                    vectorEffect="non-scaling-stroke"
                />
            </svg>

            {/*
             * Angka tetap terbaca tanpa mengandalkan warna atau bentuk grafik.
             * Grafik yang hanya bisa dibaca dengan mata awas bukan grafik yang
             * selesai (.claude/rules/20 §Aksesibilitas).
             */}
            <dl className="text-ink-soft grid grid-cols-3 gap-2 text-sm">
                <div>
                    <dt className="text-ink-muted text-xs">Total {days} hari</dt>
                    <dd className="text-ink font-semibold">{total}</dd>
                </div>
                <div>
                    <dt className="text-ink-muted text-xs">Tertinggi</dt>
                    <dd className="text-ink font-semibold">{tertinggi}</dd>
                </div>
                <div>
                    <dt className="text-ink-muted text-xs">Pada</dt>
                    <dd className="text-ink font-semibold">{puncak ? formatTanggalSingkat(puncak.date) : '-'}</dd>
                </div>
            </dl>
        </div>
    );
}
