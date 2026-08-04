import TemporaryPasswordPanel from '@/components/admin/temporary-password-panel';
import ConfirmDialog from '@/components/confirm-dialog';
import DataTable, { type Column } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { formatTanggalJamIso, formatTelepon } from '@/lib/format';
import { type Paginated, type SelectOption, type StaffUserFilters, type StaffUserRow } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Power, Users } from 'lucide-react';

interface Props {
    users: Paginated<StaffUserRow>;
    filters: StaffUserFilters;
    roleOptions: SelectOption[];
}

const KOLOM: Column<StaffUserRow>[] = [
    {
        key: 'name',
        header: 'Nama',
        primary: true,
        cell: (row) => (
            <div>
                <p className="font-medium">
                    {row.name}
                    {row.is_self && <span className="text-ink-muted ml-2 text-xs">(Anda)</span>}
                </p>
                <p className="text-ink-muted text-xs">{row.email}</p>
            </div>
        ),
    },
    { key: 'phone', header: 'WhatsApp', cell: (row) => formatTelepon(row.phone_wa) },
    { key: 'role', header: 'Peran', cell: (row) => row.role_label },
    {
        key: 'status',
        header: 'Status',
        cell: (row) => (
            <div className="flex flex-wrap gap-1">
                <Badge variant={row.is_active ? 'default' : 'secondary'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
                {row.must_reset_password && <Badge variant="outline">Belum set password</Badge>}
            </div>
        ),
    },
    {
        key: 'last_login',
        header: 'Login Terakhir',
        cell: (row) => (row.last_login_at ? formatTanggalJamIso(row.last_login_at) : 'Belum pernah'),
    },
];

export default function UsersIndex({ users, filters, roleOptions }: Props) {
    const saring = (kunci: 'role' | 'status', nilai: string | null) => {
        router.get(
            route('admin.users.index'),
            { ...filters, [kunci]: nilai ?? undefined },
            { preserveState: true, replace: true },
        );
    };

    const aksi = (row: StaffUserRow) => (
        <>
            <Button variant="outline" size="sm" asChild>
                <Link href={route('admin.users.edit', row.id)}>
                    <Pencil className="h-4 w-4" aria-hidden="true" />
                    Ubah
                </Link>
            </Button>

            <ConfirmDialog
                trigger={
                    <Button variant="outline" size="sm" aria-label={`Atur ulang password ${row.name}`}>
                        <KeyRound className="h-4 w-4" aria-hidden="true" />
                        Reset Password
                    </Button>
                }
                title="Atur ulang password akun ini?"
                description={`Password ${row.name} akan diganti dengan password sementara yang tampil satu kali. Sesi yang sedang berjalan tetap berlaku sampai ia keluar.`}
                confirmLabel="Atur Ulang Password"
                onConfirm={() => router.put(route('admin.users.reset-password', row.id), {}, { preserveScroll: true })}
            />

            {/* Tombol hanya dirender bila server mengizinkan. Ini kenyamanan,
                bukan pengaman — UserService tetap menolak bila dipaksakan. */}
            {row.can_deactivate && (
                <ConfirmDialog
                    trigger={
                        <Button variant="outline" size="sm" aria-label={`${row.is_active ? 'Nonaktifkan' : 'Aktifkan'} akun ${row.name}`}>
                            <Power className="h-4 w-4" aria-hidden="true" />
                            {row.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                        </Button>
                    }
                    title={row.is_active ? 'Nonaktifkan akun ini?' : 'Aktifkan akun ini?'}
                    description={
                        row.is_active
                            ? `${row.name} tidak akan bisa masuk sampai akunnya diaktifkan kembali. Datanya tetap utuh.`
                            : `${row.name} akan bisa masuk kembali ke panel admin.`
                    }
                    confirmLabel={row.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun'}
                    onConfirm={() => router.put(route('admin.users.toggle-active', row.id), {}, { preserveScroll: true })}
                />
            )}
        </>
    );

    return (
        <AdminLayout
            title="Pengguna Internal"
            description="Akun Super Admin dan Service Advisor. Akun pelanggan dikelola di menu Customer."
            actions={
                <Button asChild>
                    <Link href={route('admin.users.create')}>
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Tambah Akun
                    </Link>
                </Button>
            }
        >
            <Head title="Pengguna Internal" />

            <TemporaryPasswordPanel />

            <div className="mb-6 flex flex-wrap gap-2">
                <div className="flex flex-wrap gap-2" role="group" aria-label="Saring menurut peran">
                    <Button variant={filters.role === null ? 'default' : 'outline'} size="sm" onClick={() => saring('role', null)}>
                        Semua Peran
                    </Button>
                    {roleOptions.map((opsi) => (
                        <Button
                            key={opsi.value}
                            variant={filters.role === opsi.value ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => saring('role', opsi.value)}
                        >
                            {opsi.label}
                        </Button>
                    ))}
                </div>

                <div className="flex flex-wrap gap-2" role="group" aria-label="Saring menurut status">
                    {[
                        { value: null, label: 'Semua Status' },
                        { value: 'aktif', label: 'Aktif' },
                        { value: 'nonaktif', label: 'Nonaktif' },
                    ].map((opsi) => (
                        <Button
                            key={opsi.label}
                            variant={filters.status === opsi.value ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => saring('status', opsi.value)}
                        >
                            {opsi.label}
                        </Button>
                    ))}
                </div>
            </div>

            {users.data.length === 0 ? (
                <EmptyState
                    icon={Users}
                    title="Tidak ada akun yang cocok"
                    description="Ubah saringan, atau tambahkan akun staf baru."
                />
            ) : (
                <DataTable
                    columns={KOLOM}
                    rows={users.data}
                    rowKey={(row) => row.id}
                    actions={aksi}
                    caption="Daftar akun staf internal"
                />
            )}

            <Pagination meta={users} />
        </AdminLayout>
    );
}
