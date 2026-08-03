/**
 * Bentuk data form booking, dipakai bersama oleh keempat langkahnya.
 *
 * Semua nilai berupa string karena berasal dari input HTML; server yang
 * mengubahnya menjadi angka dan tanggal setelah validasi.
 */
export interface BookingFormData {
    vehicle_id: string;
    service_package_id: string;
    booking_date: string;
    booking_time: string;
    odometer: string;
    complaint: string;
    [key: string]: string;
}

export const LANGKAH = ['Kendaraan', 'Layanan', 'Jadwal', 'Tinjau'] as const;

/**
 * Kolom yang divalidasi di tiap langkah. Dipakai untuk melompat ke langkah
 * yang bermasalah ketika server menolak — pengguna tidak boleh dibiarkan
 * menatap langkah "Tinjau" dengan galat yang tersembunyi dua langkah di belakang.
 */
export const FIELD_PER_LANGKAH: Record<number, (keyof BookingFormData)[]> = {
    1: ['vehicle_id'],
    2: ['service_package_id'],
    3: ['booking_date', 'booking_time'],
    4: ['odometer', 'complaint'],
};
