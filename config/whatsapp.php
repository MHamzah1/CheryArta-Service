<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Notifikasi WhatsApp
|--------------------------------------------------------------------------
|
| Keputusan rancangan #6: TIDAK memakai gateway berbayar. Sistem hanya
| menyiapkan draft pesan dan tautan klik-to-chat (wa.me); pengiriman
| dilakukan admin dengan satu klik dari WhatsApp miliknya sendiri.
|
| Lihat docs/08-notifikasi-whatsapp.md.
|
*/

return [

    /*
    | Driver aktif. 'click_to_chat' = draft manual (default).
    | Menambah driver otomatis kelak cukup dengan membuat implementasi baru
    | dari App\Services\WhatsApp\WhatsAppNotifier — tidak ada controller,
    | template, atau tabel yang perlu diubah (docs/08 §8.7).
    */
    'driver' => env('WHATSAPP_DRIVER', 'click_to_chat'),

    /*
    | Basis tautan klik-to-chat.
    */
    'chat_base_url' => 'https://wa.me/',

    /*
    | Batas panjang pesan agar tautan aman di semua peramban.
    */
    'max_message_length' => 1000,

    /*
    | Placeholder yang sah di dalam template. Placeholder di luar daftar ini
    | ditolak saat menyimpan template, supaya tidak ada "{{typo}}" yang
    | terkirim ke pelanggan.
    */
    'allowed_placeholders' => [
        'nama',
        'kode_booking',
        'tanggal',
        'jam',
        'kendaraan',
        'plat',
        'paket',
        'estimasi_selesai',
        'ringkasan_biaya',
        'alasan',
        'alamat',
    ],

    /*
    | Kunci template yang dikenali sistem (lihat tabel whatsapp_templates).
    */
    'templates' => [
        'booking_created',
        'booking_confirmed',
        'booking_in_progress',
        'booking_completed',
        'booking_cancelled',
        'booking_reminder',
    ],

    /*
    | Pesan awal tombol WhatsApp mengambang di halaman publik.
    */
    'default_inquiry_message' => 'Halo Chery Arta, saya ingin bertanya tentang layanan servis.',

];
