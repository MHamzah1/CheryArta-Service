import InputError from '@/components/input-error';
import PhoneInput from '@/components/phone-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface StaffUserFormValue {
    id: number;
    name: string;
    email: string;
    phone_wa: string | null;
    role: string;
    is_active: boolean;
}

interface Props {
    user: StaffUserFormValue | null;
}

export default function UserForm({ user }: Props) {
    const mengubah = user !== null;

    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        phone_wa: user?.phone_wa ?? '',
        role: user?.role ?? 'service_advisor',
        is_active: user?.is_active ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (mengubah) {
            put(route('admin.users.update', user.id));
        } else {
            post(route('admin.users.store'));
        }
    };

    return (
        <AdminLayout
            title={mengubah ? 'Ubah Akun Staf' : 'Tambah Akun Staf'}
            description="Akun staf hanya untuk Super Admin dan Service Advisor. Akun pelanggan dikelola di menu Customer."
        >
            <Head title={mengubah ? 'Ubah Akun Staf' : 'Tambah Akun Staf'} />

            <form onSubmit={submit} className="border-line bg-surface shadow-card max-w-2xl space-y-6 rounded-2xl border p-6">
                <div className="space-y-2">
                    <Label htmlFor="name">Nama</Label>
                    <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} autoFocus required />
                    <InputError message={errors.name} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />
                    <p className="text-ink-soft text-sm">Dipakai untuk masuk ke panel admin.</p>
                    <InputError message={errors.email} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="phone_wa">Nomor WhatsApp</Label>
                    <PhoneInput id="phone_wa" value={data.phone_wa} onChange={(nilai) => setData('phone_wa', nilai)} required />
                    <InputError message={errors.phone_wa} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="role">Peran</Label>
                    <Select value={data.role} onValueChange={(nilai) => setData('role', nilai)}>
                        <SelectTrigger id="role" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="service_advisor">Service Advisor</SelectItem>
                            <SelectItem value="super_admin">Super Admin</SelectItem>
                        </SelectContent>
                    </Select>
                    <p className="text-ink-soft text-sm">
                        Service Advisor mengurus booking dan jadwal. Super Admin juga mengurus master data, konten, dan akun.
                    </p>
                    <InputError message={errors.role} />
                </div>

                <div className="flex items-center gap-3">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(nilai) => setData('is_active', nilai === true)}
                    />
                    <Label htmlFor="is_active">Akun aktif</Label>
                </div>
                <InputError message={errors.is_active} />

                {!mengubah && (
                    <div className="border-line bg-canvas flex items-start gap-3 rounded-xl border p-4">
                        <KeyRound className="text-ink-soft mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                        <p className="text-ink-soft text-sm">
                            <strong className="text-ink">Password tidak diisi di sini.</strong> Sistem membuatnya acak dan
                            menampilkannya satu kali setelah akun tersimpan. Pemiliknya wajib menggantinya saat pertama masuk.
                        </p>
                    </div>
                )}

                <div className="flex flex-wrap gap-3">
                    <Button type="submit" disabled={processing}>
                        {mengubah ? 'Simpan Perubahan' : 'Buat Akun'}
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href={route('admin.users.index')}>Batal</Link>
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
