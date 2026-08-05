<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Booking;
use Illuminate\Support\Facades\Config;

/**
 * Nilai placeholder pesan WhatsApp (docs/08 §8.4).
 *
 * Kelas ini sumber kebenaran untuk PERENDERAN. Sumber kebenaran untuk
 * VALIDASI adalah `config('whatsapp.allowed_placeholders')`. Keduanya wajib
 * memuat nama yang sama persis, dan tests/Unit/WhatsAppPlaceholderTest.php
 * yang menjaganya — daftar placeholder pernah nyata berselisih di tiga dokumen
 * sekaligus (grill K3), dan selisih semacam itu hanya ketahuan dari pesan
 * pelanggan yang berisi `{{typo}}`.
 *
 * `{{ringkasan_biaya}}` belum ada di sini: sumbernya tabel `invoices` yang baru
 * lahir di Tahap 11 (keputusan grill Q4b).
 */
final class WhatsAppPlaceholders
{
    /**
     * Nilai sungguhan untuk satu booking.
     *
     * @return array<string, string>
     */
    public static function forBooking(Booking $booking): array
    {
        return [
            '{{nama}}' => $booking->user->name,
            '{{kode_booking}}' => $booking->booking_code,
            '{{tanggal}}' => self::tanggalIndonesia($booking),
            '{{jam}}' => SlotTime::short($booking->booking_time),
            '{{kendaraan}}' => $booking->vehicle->display_model,
            '{{plat}}' => $booking->vehicle->plate_full,
            '{{paket}}' => $booking->servicePackage->name,
            '{{estimasi_selesai}}' => $booking->estimated_finish_at?->format('H:i') ?? '-',
            '{{alasan}}' => $booking->cancel_reason ?? '-',
            '{{alamat}}' => Config::string('company.address.full'),
        ];
    }

    /**
     * Nilai contoh untuk pratinjau di layar penyunting template.
     *
     * Sengaja karangan, bukan booking sungguhan: penyunting template tidak
     * punya alasan menampilkan nama, plat, dan jadwal pelanggan nyata
     * (.claude/rules/50 — data pribadi tidak ikut ke props yang tidak
     * membutuhkannya), dan pratinjau tidak boleh kosong hanya karena belum ada
     * booking di database.
     *
     * @return array<string, string>
     */
    public static function sample(): array
    {
        return [
            '{{nama}}' => 'Budi Santoso',
            '{{kode_booking}}' => 'CA-20260805-0001',
            '{{tanggal}}' => 'Rabu, 5 Agustus 2026',
            '{{jam}}' => '09:00',
            '{{kendaraan}}' => 'Chery Tiggo 8 Pro',
            '{{plat}}' => 'B 1234 ABC',
            '{{paket}}' => 'Servis Berkala 10.000 KM',
            '{{estimasi_selesai}}' => '10:30',
            '{{alasan}}' => 'Kendaraan sedang dipakai ke luar kota',
            '{{alamat}}' => Config::string('company.address.full'),
        ];
    }

    /**
     * Nama placeholder tanpa kurung kurawal — bentuk yang dipakai config dan
     * ditampilkan di layar penyunting.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(
            fn (string $token): string => trim($token, '{}'),
            array_keys(self::sample()),
        );
    }

    /** @param  array<string, string>  $placeholders */
    public static function render(string $body, array $placeholders): string
    {
        return strtr($body, $placeholders);
    }

    /** "Senin, 3 Agustus 2026" — zona bengkel, bukan zona server (temuan B7). */
    private static function tanggalIndonesia(Booking $booking): string
    {
        $date = $booking->booking_date;

        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$date->dayOfWeek];
        $bulan = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ][$date->month - 1];

        return "{$hari}, {$date->day} {$bulan} {$date->year}";
    }
}
