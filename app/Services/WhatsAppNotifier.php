<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Support\PhoneNumber;
use App\Support\SlotTime;
use Illuminate\Support\Facades\Config;

/**
 * Draft pesan WhatsApp klik-to-chat (docs/08-notifikasi-whatsapp.md).
 *
 * BIG FASE 1 (keputusan R6): hanya menyusun tautan `wa.me` berisi teks dari
 * `config/company.php`. Tidak ada gateway, tidak ada pengiriman otomatis, dan
 * belum ada log pengiriman — `whatsapp_messages`, panel draft, serta penanda
 * "belum dikirim" menyusul di F2.2.
 *
 * Kelas ini sengaja sudah bernama sesuai empat service inti di docs/03 §3.6,
 * sehingga F2.2 mengubah isinya menjadi implementasi interface tanpa
 * menyentuh satu pun controller.
 *
 * Service tidak menyentuh request(), session(), atau auth()
 * (.claude/rules/10-backend-laravel.md).
 */
class WhatsAppNotifier
{
    /** Batas aman agar tautan tidak terpotong di peramban (docs/08 §8.5). */
    private const PANJANG_MAKSIMAL = 1000;

    /**
     * Pesan siap kirim untuk status booking saat ini.
     *
     * Mengembalikan null bila pelanggan tidak punya nomor WhatsApp — tombolnya
     * tidak boleh muncul dengan tautan yang mengarah entah ke mana.
     *
     * @return array{url: string, message: string, phone_display: string}|null
     */
    public function draft(Booking $booking): ?array
    {
        $phone = $booking->user->phone_wa;

        if ($phone === null || $phone === '') {
            return null;
        }

        $message = mb_substr($this->render($booking), 0, self::PANJANG_MAKSIMAL);

        return [
            // rawurlencode, bukan urlencode: spasi harus menjadi %20, bukan '+'
            // yang akan terbaca sebagai tanda plus di WhatsApp (docs/08 §8.5).
            'url' => 'https://wa.me/'.$phone.'?text='.rawurlencode($message),
            'message' => $message,
            'phone_display' => PhoneNumber::forDisplay($phone),
        ];
    }

    private function render(Booking $booking): string
    {
        /** @var array<string, string> $templates */
        $templates = Config::array('company.wa_messages');

        $template = $templates[$booking->status->value] ?? $templates['pending'];

        return strtr($template, $this->placeholders($booking));
    }

    /**
     * Daftar placeholder mengikuti docs/08 §8.4.
     *
     * @return array<string, string>
     */
    private function placeholders(Booking $booking): array
    {
        return [
            '{{nama}}' => $booking->user->name,
            '{{kode_booking}}' => $booking->booking_code,
            '{{tanggal}}' => $this->tanggalIndonesia($booking),
            '{{jam}}' => SlotTime::short($booking->booking_time),
            '{{kendaraan}}' => $booking->vehicle->display_model,
            '{{plat}}' => $booking->vehicle->plate_full,
            '{{paket}}' => $booking->servicePackage->name,
            '{{estimasi_selesai}}' => $booking->estimated_finish_at?->format('H:i') ?? '-',
            '{{alasan}}' => $booking->cancel_reason ?? '-',
            '{{alamat}}' => Config::string('company.address.full'),
        ];
    }

    /** "Senin, 3 Agustus 2026" — zona bengkel, bukan zona server (temuan B7). */
    private function tanggalIndonesia(Booking $booking): string
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
