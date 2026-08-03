import ConfirmDialog from '@/components/confirm-dialog';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import CustomerLayout from '@/layouts/customer-layout';
import { formatPlat } from '@/lib/format';
import { type Vehicle } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Car, Pencil, Plus, Trash2 } from 'lucide-react';

interface Props {
    vehicles: Vehicle[];
}

function namaModel(vehicle: Vehicle): string {
    return vehicle.car_model?.name ?? vehicle.model_name_manual ?? 'Model tidak diketahui';
}

function keterangan(vehicle: Vehicle): string {
    return [vehicle.year, vehicle.color].filter(Boolean).join(' · ');
}

export default function VehicleIndex({ vehicles }: Props) {
    const hapus = (vehicle: Vehicle) => {
        router.delete(route('customer.vehicles.destroy', vehicle.id), { preserveScroll: true });
    };

    return (
        <CustomerLayout title="Kendaraan Saya" description="Kendaraan yang tersimpan di sini bisa langsung dipilih saat memesan servis.">
            <Head title="Kendaraan Saya" />

            {vehicles.length === 0 ? (
                <EmptyState
                    icon={Car}
                    title="Belum ada kendaraan tersimpan"
                    description="Tambahkan kendaraan Anda supaya tidak perlu mengetik ulang plat nomor setiap kali memesan servis."
                    action={
                        <Button asChild>
                            <Link href={route('customer.vehicles.create')}>
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                Tambah Kendaraan
                            </Link>
                        </Button>
                    }
                />
            ) : (
                <div className="grid gap-4">
                    <div className="flex justify-end">
                        <Button asChild>
                            <Link href={route('customer.vehicles.create')}>
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                Tambah Kendaraan
                            </Link>
                        </Button>
                    </div>

                    <ul className="grid gap-4 sm:grid-cols-2">
                        {vehicles.map((vehicle) => (
                            <li key={vehicle.id}>
                                <Card>
                                    <CardContent className="flex flex-col gap-4 p-5">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="text-brand-700 text-lg font-bold">{formatPlat(vehicle.plate_full)}</p>
                                                <p className="mt-1 font-medium">{namaModel(vehicle)}</p>
                                                {keterangan(vehicle) && <p className="text-ink-soft text-sm">{keterangan(vehicle)}</p>}
                                            </div>

                                            {vehicle.is_primary && <Badge>Kendaraan Utama</Badge>}
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route('customer.vehicles.edit', vehicle.id)}>
                                                    <Pencil className="h-4 w-4" aria-hidden="true" />
                                                    Ubah
                                                </Link>
                                            </Button>

                                            <ConfirmDialog
                                                trigger={
                                                    <Button variant="outline" size="sm" aria-label={`Hapus kendaraan ${vehicle.plate_full}`}>
                                                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                                                        Hapus
                                                    </Button>
                                                }
                                                title="Hapus kendaraan ini?"
                                                description={`Kendaraan ${formatPlat(vehicle.plate_full)} akan dikeluarkan dari daftar Anda. Riwayat servisnya tetap tersimpan.`}
                                                confirmLabel="Hapus Kendaraan"
                                                onConfirm={() => hapus(vehicle)}
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </CustomerLayout>
    );
}
