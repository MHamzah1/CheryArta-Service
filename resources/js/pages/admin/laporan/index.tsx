import BreakdownList from '@/components/admin/breakdown-list';
import BookingTrendChart from '@/components/admin/booking-trend-chart';
import ExportButtons from '@/components/admin/export-buttons';
import PeriodPicker from '@/components/admin/period-picker';
import { StatusBadge } from '@/components/status-badge';
import AdminLayout from '@/layouts/admin-layout';
import { formatJam } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
    type ReportNewCustomers,
    type ReportOccupancy,
    type ReportPeriodState,
    type ReportRecap,
    type SelectOption,
} from '@/types';
import { Head, router } from '@inertiajs/react';

interface Props {
    period: ReportPeriodState;
    periodOptions: SelectOption[];
    tab: string;
    recap: ReportRecap;
    occupancy: ReportOccupancy;
    newCustomers: ReportNewCustomers;
}

const TABS = [
    { value: 'rekap', label: 'Rekap Booking' },
    { value: 'okupansi', label: 'Okupansi' },
    { value: 'customer', label: 'Customer Baru' },
];

/**
 * A9 — Laporan (docs/07 §A9, roadmap 2.1.4).
 *
 * Tiga tab dalam satu rute, berbagi satu pemilih periode (keputusan grill #7).
 * Tab aktif dan periode hidup di query string, jadi tautannya bisa dibagikan
 * dan halaman yang di-refresh tidak kehilangan konteks.
 *
 * Tab **Pendapatan tidak ada** — tabel `invoices` baru lahir di F2.4
 * (keputusan grill #1). Sengaja tidak dirender sama sekali, bukan ditampilkan
 * lalu dinonaktifkan: tab yang mati membuat orang menyangka fiturnya rusak.
 */
export default function LaporanIndex({ period, periodOptions, tab, recap, occupancy, newCustomers }: Props) {
    const gantiTab = (nilai: string) => {
        router.get(
            route('admin.reports.index'),
            { tab: nilai, periode: period.periode, dari: period.dari, sampai: period.sampai },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const urlExport = route('admin.reports.export', {
        periode: period.periode,
        dari: period.dari,
        sampai: period.sampai,
    });

    return (
        <AdminLayout
            title="Laporan"
            description="Rekap booking, okupansi slot, dan pertumbuhan customer per periode."
            actions={<ExportButtons baseUrl={urlExport} />}
        >
            <Head title="Laporan" />

            <div className="grid gap-6">
                <PeriodPicker period={period} options={periodOptions} tab={tab} />

                <div className="border-line flex gap-1 overflow-x-auto border-b" role="tablist">
                    {TABS.map((item) => (
                        <button
                            key={item.value}
                            type="button"
                            role="tab"
                            aria-selected={tab === item.value}
                            onClick={() => gantiTab(item.value)}
                            className={cn(
                                'focus-visible:ring-brand-600 -mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-medium transition focus-visible:ring-2 focus-visible:outline-none',
                                tab === item.value
                                    ? 'border-brand-600 text-brand-700'
                                    : 'text-ink-soft hover:text-ink border-transparent',
                            )}
                        >
                            {item.label}
                        </button>
                    ))}
                </div>

                {tab === 'rekap' && (
                    <div className="grid gap-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Angka label="Total Booking" value={recap.total} hint={`Sepanjang ${period.jumlah_hari} hari`} />
                            <Angka
                                label="Selesai"
                                value={recap.total_selesai}
                                hint={recap.total > 0 ? `${Math.round((recap.total_selesai / recap.total) * 100)}% dari total` : 'Belum ada data'}
                            />
                        </div>

                        <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
                            <h3 className="mb-3 font-semibold">Per Status</h3>
                            <ul className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {recap.per_status.map((baris) => (
                                    <li key={baris.value} className="border-line flex items-center justify-between gap-3 rounded-xl border p-3">
                                        <StatusBadge status={baris.value} />
                                        <span className="font-semibold tabular-nums">{baris.jumlah}</span>
                                    </li>
                                ))}
                            </ul>
                        </section>

                        <div className="grid gap-4 lg:grid-cols-2">
                            <BreakdownList
                                title="Per Paket Layanan"
                                rows={recap.per_paket}
                                total={recap.total}
                                emptyText={`Tidak ada booking pada ${period.dari} s.d. ${period.sampai}.`}
                            />
                            <BreakdownList
                                title="Per Model Mobil"
                                rows={recap.per_model}
                                total={recap.total}
                                emptyText={`Tidak ada booking pada ${period.dari} s.d. ${period.sampai}.`}
                            />
                        </div>
                    </div>
                )}

                {tab === 'okupansi' && (
                    <div className="grid gap-4">
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Angka label="Slot Terisi" value={occupancy.total_terisi} hint="Di luar batal & tidak hadir" />
                            <Angka label="Rata-rata per Hari" value={occupancy.rata_rata_per_hari} hint={`${occupancy.hari_beroperasi} hari beroperasi`} />
                            <Angka
                                label="Jam Tersibuk"
                                text={occupancy.jam_tersibuk ? formatJam(occupancy.jam_tersibuk) : '-'}
                                hint="Paling banyak terisi"
                            />
                        </div>

                        <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
                            <h3 className="mb-1 font-semibold">Pemakaian per Jam</h3>
                            <p className="text-ink-soft mb-4 text-sm">
                                Persentase dihitung dari kapasitas {occupancy.quota_per_slot} booking per slot dikali{' '}
                                {occupancy.hari_beroperasi} hari beroperasi.
                            </p>

                            {occupancy.per_jam.length === 0 ? (
                                <p className="text-ink-muted py-4 text-sm">
                                    Tidak ada slot terisi pada {period.dari} s.d. {period.sampai}.
                                </p>
                            ) : (
                                <dl className="grid gap-2.5">
                                    {occupancy.per_jam.map((baris) => (
                                        <div key={baris.time} className="grid grid-cols-[3.5rem_1fr_5.5rem] items-center gap-3 text-sm">
                                            <dt className="text-ink-soft tabular-nums">{formatJam(baris.time)}</dt>
                                            <dd className="bg-canvas h-2 overflow-hidden rounded-full">
                                                <span
                                                    className="bg-brand-500 block h-full rounded-full"
                                                    style={{ width: `${Math.min(baris.persen, 100)}%` }}
                                                />
                                            </dd>
                                            <dd className="text-right tabular-nums">
                                                <span className="font-semibold">{baris.total}</span>
                                                <span className="text-ink-muted ml-1 text-xs">({baris.persen}%)</span>
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            )}
                        </section>
                    </div>
                )}

                {tab === 'customer' && (
                    <div className="grid gap-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Angka label="Customer Baru" value={newCustomers.total} hint={`Sepanjang ${period.jumlah_hari} hari`} />
                            <Angka label="Rata-rata per Hari" value={newCustomers.rata_rata_per_hari} hint="Registrasi baru" />
                        </div>

                        <section className="border-line bg-surface shadow-card rounded-2xl border p-4">
                            <h3 className="mb-3 font-semibold">Registrasi per Hari</h3>
                            <BookingTrendChart points={newCustomers.per_hari} days={period.jumlah_hari} />
                        </section>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

/** Kartu angka ringkas — dipakai berulang di ketiga tab. */
function Angka({ label, value, text, hint }: { label: string; value?: number; text?: string; hint: string }) {
    return (
        <div className="border-line bg-surface shadow-card rounded-2xl border p-4">
            <p className="text-ink-soft text-sm font-medium">{label}</p>
            <p className="mt-1 text-3xl font-bold tabular-nums">{text ?? value}</p>
            <p className="text-ink-muted mt-1 text-xs">{hint}</p>
        </div>
    );
}
