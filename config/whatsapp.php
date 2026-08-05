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
    | Batas panjang BADAN TEMPLATE saat disimpan Super Admin.
    |
    | Lebih pendek daripada batas di atas dengan sengaja: placeholder mengembang
    | saat dirender ({{alamat}} sendiri hampir 80 karakter), dan pesan yang
    | melewati 1.000 dipotong DIAM-DIAM sehingga penutup "- Chery Arta" hilang
    | tanpa ada yang tahu. Selisihnya adalah ruang aman untuk pengembangan itu.
    */
    'max_template_body_length' => 700,

    /*
    | Rentang hari yang dipandang kartu "Belum dikabari" di dashboard A1.
    |
    | Tanpa batas ini seluruh booking sejak Tahap 5 ikut terhitung — tabel
    | whatsapp_messages baru lahir di Tahap 10 sehingga tak satu pun punya baris
    | terkirim — dan kartunya tidak akan pernah bisa dikosongkan (grill Q6).
    */
    'pending_window_days' => 7,

    /*
    | Placeholder yang sah di dalam template. Placeholder di luar daftar ini
    | ditolak saat menyimpan template, supaya tidak ada "{{typo}}" yang
    | terkirim ke pelanggan.
    |
    | Ini sumber kebenaran untuk VALIDASI; yang merender nilainya adalah
    | App\Services\WhatsApp\ClickToChatNotifier. Kedua himpunan wajib identik,
    | dan tests/Unit/WhatsAppPlaceholderTest.php yang menjaganya — selisih di
    | antara keduanya pernah nyata terjadi di dokumen (grill K3).
    |
    | `ringkasan_biaya` DIHIDUPKAN di Tahap 11 (roadmap 2.4.8), setelah tabel
    | `invoices` lahir. Ia merender satu baris total; bila invoicenya belum
    | diterbitkan, isinya kalimat netral — bukan angka draft yang masih bisa
    | berubah (keputusan grill Q8).
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
        'alasan',
        'alamat',
        'ringkasan_biaya',
    ],

    /*
    | Daftar kunci template TIDAK ada di sini.
    |
    | Himpunannya milik kode — setiap kunci punya pemicunya sendiri — sehingga
    | ia hidup di App\Enums\WhatsAppTemplateKey. Menaruhnya di dua tempat
    | berarti menunggu keduanya berselisih (cacat B2).
    */

    /*
    | Pesan awal tombol WhatsApp mengambang di halaman publik.
    */
    'default_inquiry_message' => 'Halo Chery Arta, saya ingin bertanya tentang layanan servis.',

];
