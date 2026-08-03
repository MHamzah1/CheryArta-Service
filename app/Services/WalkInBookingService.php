<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Booking untuk pelanggan yang datang langsung ke bengkel (docs/07 §A2).
 *
 * Satu langkah bagi advisor, tiga tulisan bagi database: akun pelanggan,
 * kendaraannya, lalu bookingnya. Ketiganya dibungkus SATU transaksi — kalau
 * slotnya ternyata sudah penuh, tidak boleh ada akun setengah jadi yang
 * tertinggal dan membuat pencarian pelanggan berikutnya berisi duplikat.
 *
 * Kelas ini ada di luar empat service inti docs/03 §3.6 dengan alasan yang
 * sama seperti VehicleService: BookingService tidak boleh tahu cara membuat
 * akun pengguna, dan VehicleService tidak boleh tahu aturan slot.
 */
class WalkInBookingService
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly VehicleService $vehicles,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Hasil StoreWalkInBookingRequest::validated()
     * @param  User  $actor  Advisor yang mengisikan; menjadi penanggung jawab booking.
     */
    public function create(array $data, User $actor): Booking
    {
        return DB::transaction(function () use ($data, $actor): Booking {
            $customer = $this->pelanggan($data);
            $vehicle = $this->kendaraan($customer, $data);

            return $this->bookings->createWalkIn($customer, [
                'vehicle_id' => $vehicle->getKey(),
                'service_package_id' => $data['service_package_id'],
                'booking_date' => $data['booking_date'],
                'booking_time' => $data['booking_time'],
                'odometer' => $data['odometer'] ?? null,
                'complaint' => $data['complaint'] ?? null,
            ], $actor);
        });
    }

    /**
     * Akun pelanggan: yang sudah ada dipakai kembali, yang belum dibuatkan.
     *
     * Password akun baru diacak dan TIDAK pernah ditampilkan — pelanggan yang
     * dibuatkan di meja servis belum tentu ingin punya akun. `must_reset_password`
     * menandainya sebagai akun yang passwordnya harus diatur ulang sebelum
     * dipakai; jalur resetnya ada di /admin/customers (Super Admin).
     *
     * @param  array<string, mixed>  $data
     */
    private function pelanggan(array $data): User
    {
        if (isset($data['user_id'])) {
            /** @var User */
            return User::query()->findOrFail($data['user_id']);
        }

        /** @var array<string, string> $baru */
        $baru = $data['customer'];

        $customer = new User([
            'name' => $baru['name'],
            'email' => $baru['email'],
            // Dinormalisasi ke 62… oleh accessor User::phoneWa().
            'phone_wa' => $baru['phone_wa'],
            'address' => $baru['address'] ?? null,
            'password' => Str::password(16),
        ]);

        $customer->forceFill(['must_reset_password' => true])->save();

        return $customer;
    }

    /**
     * Kendaraan yang diservis: dipilih dari milik pelanggan, atau didaftarkan
     * sekarang juga.
     *
     * Yang dipilih tetap diambil lewat relasi pemiliknya — kendaraan orang
     * lain tidak boleh bisa dipesankan servis hanya dengan mengganti id di
     * request (.claude/rules/50-keamanan.md).
     *
     * @param  array<string, mixed>  $data
     */
    private function kendaraan(User $customer, array $data): Vehicle
    {
        if (isset($data['vehicle_id'])) {
            /** @var Vehicle */
            return $customer->vehicles()->findOrFail($data['vehicle_id']);
        }

        /** @var array<string, mixed> $baru */
        $baru = $data['vehicle'];

        return $this->vehicles->create($customer, $baru);
    }
}
