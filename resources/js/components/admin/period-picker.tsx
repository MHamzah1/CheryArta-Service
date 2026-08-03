import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type ReportPeriodState, type SelectOption } from '@/types';
import { router } from '@inertiajs/react';

interface Props {
    period: ReportPeriodState;
    options: SelectOption[];
    /** Tab yang sedang aktif — ikut dibawa supaya tidak lompat balik ke tab pertama. */
    tab: string;
}

/**
 * Pemilih periode laporan (docs/07 §A9, keputusan grill #7 & #8).
 *
 * Dipakai bersama SELURUH tab. Itulah alasan laporan berupa satu rute: empat
 * rute terpisah berarti menyalin pemilih ini empat kali, dan empat salinan
 * akan berbeda cepat atau lambat.
 *
 * Preset dan artinya hidup di server (App\Support\ReportPeriod) — komponen ini
 * hanya menampilkan apa yang dikirim. "30 hari" tidak boleh punya definisi
 * kedua di React.
 */
export default function PeriodPicker({ period, options, tab }: Props) {
    const muat = (params: Record<string, string>) => {
        router.get(
            route('admin.reports.index'),
            { tab, ...params },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const kustom = period.periode === 'kustom';

    return (
        <div className="border-line bg-surface shadow-card flex flex-wrap items-end gap-4 rounded-2xl border p-4">
            <div className="grid gap-1.5">
                <Label htmlFor="periode">Periode</Label>
                <Select
                    value={period.periode}
                    onValueChange={(periode) =>
                        muat(periode === 'kustom' ? { periode, dari: period.dari, sampai: period.sampai } : { periode })
                    }
                >
                    <SelectTrigger id="periode" className="w-52">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {options.map((pilihan) => (
                            <SelectItem key={pilihan.value} value={pilihan.value}>
                                {pilihan.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {kustom && (
                <>
                    <div className="grid gap-1.5">
                        <Label htmlFor="dari">Dari</Label>
                        <Input
                            id="dari"
                            type="date"
                            value={period.dari}
                            onChange={(e) => e.target.value && muat({ periode: 'kustom', dari: e.target.value, sampai: period.sampai })}
                        />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="sampai">Sampai</Label>
                        <Input
                            id="sampai"
                            type="date"
                            value={period.sampai}
                            onChange={(e) => e.target.value && muat({ periode: 'kustom', dari: period.dari, sampai: e.target.value })}
                        />
                    </div>
                </>
            )}

            <p className="text-ink-soft ml-auto text-sm">
                {period.jumlah_hari} hari · <span className="text-ink font-medium">{period.dari}</span> s.d.{' '}
                <span className="text-ink font-medium">{period.sampai}</span>
            </p>
        </div>
    );
}
