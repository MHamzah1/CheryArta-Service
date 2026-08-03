import InputError from '@/components/input-error';
import PhoneInput from '@/components/phone-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatTelepon } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type WalkInCustomerResult, type WalkInSelectedCustomer } from '@/types';
import { router } from '@inertiajs/react';
import { Search, UserPlus, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';

export interface CustomerBaru {
    name: string;
    email: string;
    phone_wa: string;
    address: string;
    /** Dituntut `FormDataConvertible` milik Inertia agar bisa ikut di `useForm`. */
    [field: string]: string;
}

interface Props {
    keyword: string | null;
    results: WalkInCustomerResult[];
    selected: WalkInSelectedCustomer | null;
    customerBaru: CustomerBaru;
    onSelect: (id: number | null) => void;
    onCustomerBaruChange: (field: keyof CustomerBaru, value: string) => void;
    errors: Partial<Record<string, string>>;
}

/**
 * Langkah ① walk-in — cari pelanggan, atau buatkan akun baru (docs/07 §A2).
 *
 * Pencariannya memakai kunjungan Inertia parsial ke halaman ini sendiri,
 * BUKAN `fetch` ke endpoint JSON. Aturan 20 hanya mengecualikan ketersediaan
 * slot, dan daftar pelanggan justru data yang paling tidak boleh punya
 * endpoint terbuka sendiri — itu persis temuan S8 sistem lama.
 */
export default function WalkInCustomerPicker({
    keyword,
    results,
    selected,
    customerBaru,
    onSelect,
    onCustomerBaruChange,
    errors,
}: Props) {
    const [kata, setKata] = useState(keyword ?? '');
    const [modeBaru, setModeBaru] = useState(false);

    const cari = (event: FormEvent) => {
        event.preventDefault();

        router.get(
            route('admin.bookings.create'),
            { cari: kata, ...(selected ? { pelanggan: selected.id } : {}) },
            { preserveState: true, replace: true, only: ['customerResults', 'filters'] },
        );
    };

    const pilih = (id: number) => {
        setModeBaru(false);
        onSelect(id);

        router.get(
            route('admin.bookings.create'),
            { pelanggan: id, ...(kata ? { cari: kata } : {}) },
            { preserveState: true, replace: true, only: ['selectedCustomer', 'filters'] },
        );
    };

    const lepas = () => {
        onSelect(null);

        router.get(
            route('admin.bookings.create'),
            kata ? { cari: kata } : {},
            { preserveState: true, replace: true, only: ['selectedCustomer', 'filters'] },
        );
    };

    if (selected) {
        return (
            <div className="border-brand-700 bg-brand-50 rounded-2xl border p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="font-semibold">{selected.name}</p>
                        <p className="text-ink-soft text-sm">{selected.email}</p>
                        <p className="text-ink-soft text-sm">{formatTelepon(selected.phone_wa)}</p>
                        {!selected.is_active && (
                            <p className="text-danger-600 mt-1 text-sm">
                                Akun ini nonaktif — bookingnya tetap bisa dibuat, tetapi pelanggan belum bisa masuk sendiri.
                            </p>
                        )}
                    </div>

                    <Button type="button" variant="outline" size="sm" onClick={lepas}>
                        <X className="h-4 w-4" aria-hidden="true" />
                        Ganti Pelanggan
                    </Button>
                </div>
            </div>
        );
    }

    if (modeBaru) {
        return (
            <div className="border-line bg-surface grid gap-4 rounded-2xl border p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="font-semibold">Pelanggan baru</p>
                    <Button type="button" variant="ghost" size="sm" onClick={() => setModeBaru(false)}>
                        <X className="h-4 w-4" aria-hidden="true" />
                        Batal, cari saja
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-1.5">
                        <Label htmlFor="customer_name">Nama</Label>
                        <Input
                            id="customer_name"
                            value={customerBaru.name}
                            onChange={(e) => onCustomerBaruChange('name', e.target.value)}
                            maxLength={120}
                            autoComplete="off"
                        />
                        <InputError message={errors['customer.name']} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="customer_email">Email</Label>
                        <Input
                            id="customer_email"
                            type="email"
                            value={customerBaru.email}
                            onChange={(e) => onCustomerBaruChange('email', e.target.value.toLowerCase())}
                            maxLength={150}
                            autoComplete="off"
                        />
                        <InputError message={errors['customer.email']} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="customer_phone">Nomor WhatsApp</Label>
                        <PhoneInput
                            id="customer_phone"
                            value={customerBaru.phone_wa}
                            onChange={(value) => onCustomerBaruChange('phone_wa', value)}
                        />
                        <InputError message={errors['customer.phone_wa']} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="customer_address">Alamat (opsional)</Label>
                        <Input
                            id="customer_address"
                            value={customerBaru.address}
                            onChange={(e) => onCustomerBaruChange('address', e.target.value)}
                            maxLength={255}
                            autoComplete="off"
                        />
                        <InputError message={errors['customer.address']} />
                    </div>
                </div>

                <p className="text-ink-muted text-sm">
                    Akunnya dibuat dengan password acak dan ditandai harus diatur ulang. Pelanggan yang ingin masuk sendiri bisa meminta
                    reset password lewat Super Admin.
                </p>
            </div>
        );
    }

    return (
        <div className="grid gap-4">
            <form onSubmit={cari} className="grid gap-1.5">
                <Label htmlFor="cari_pelanggan">Cari pelanggan</Label>
                <div className="flex gap-2">
                    <Input
                        id="cari_pelanggan"
                        value={kata}
                        onChange={(e) => setKata(e.target.value)}
                        placeholder="Nama, email, atau nomor WhatsApp"
                        autoComplete="off"
                    />
                    <Button type="submit" variant="outline" aria-label="Cari pelanggan">
                        <Search className="h-4 w-4" aria-hidden="true" />
                    </Button>
                </div>
                <InputError message={errors.user_id} />
            </form>

            {results.length > 0 && (
                <ul className="grid gap-2">
                    {results.map((customer) => (
                        <li key={customer.id}>
                            <button
                                type="button"
                                onClick={() => pilih(customer.id)}
                                className={cn(
                                    'border-line bg-surface hover:border-brand-400 hover:bg-brand-50 focus-visible:ring-brand-600 w-full rounded-xl border px-4 py-3 text-left transition focus-visible:ring-2',
                                )}
                            >
                                <p className="font-medium">{customer.name}</p>
                                <p className="text-ink-soft text-sm">
                                    {customer.email} · {formatTelepon(customer.phone_wa)}
                                </p>
                                <p className="text-ink-muted text-xs">
                                    {customer.vehicles_count} kendaraan tersimpan
                                    {!customer.is_active && ' · akun nonaktif'}
                                </p>
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {keyword !== null && results.length === 0 && (
                <p className="text-ink-soft text-sm">
                    Tidak ada pelanggan yang cocok dengan "{keyword}". Buatkan akun baru bila ini kunjungan pertamanya.
                </p>
            )}

            <div>
                <Button type="button" variant="outline" onClick={() => setModeBaru(true)}>
                    <UserPlus className="h-4 w-4" aria-hidden="true" />
                    Pelanggan Baru
                </Button>
            </div>
        </div>
    );
}
