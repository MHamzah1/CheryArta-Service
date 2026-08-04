<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Profil Perusahaan
|--------------------------------------------------------------------------
|
| Dikutip PERSIS dari sistem lama (index (1).html baris 1719-1753, 1666-1696).
| Isinya tidak boleh diubah tanpa persetujuan — lihat
| docs/01-analisis-sistem-lama.md §1.5.
|
| Dipakai oleh: landing page, footer, template WhatsApp, PDF invoice,
| dan data terstruktur schema.org. Halaman React membacanya lewat shared
| props Inertia — jangan pernah menulis alamat/telepon langsung di JSX.
|
*/

return [

    'name' => 'Chery Arta',
    'legal_name' => 'Chery Arta',
    'tagline' => 'Servis Chery Anda, tanpa antre.',

    'address' => [
        'street' => 'Jl. Jend. Sudirman No.1, Kranji, Kec. Bekasi Bar.',
        'city' => 'Kota Bekasi',
        'province' => 'Jawa Barat',
        'country' => 'ID',
        'full' => 'Jl. Jend. Sudirman No.1, Kranji, Kec. Bekasi Bar., Kota Bekasi, Jawa Barat',
    ],

    'phones' => [
        'landline' => '(021) 38317201',
        'mobile' => '0895-4045-46904',
    ],

    'email' => 'Cheryaftersales.10254719@gmail.com',

    /*
    | Nomor WhatsApp resmi bengkel untuk tombol klik-to-chat di landing page.
    | Format ternormalisasi tanpa tanda baca: 62xxxxxxxxxx
    | Lihat pertanyaan terbuka Q4 di docs/README.md.
    */
    'wa_number' => env('COMPANY_WA_NUMBER', '6282123872515'),

    /*
    | Teks pesan WhatsApp klik-to-chat, berkunci status booking.
    |
    | BIG FASE 1 SAJA (keputusan R6, docs/10 §Modul admin). Tabel
    | `whatsapp_templates` yang bisa disunting Super Admin, log pengiriman, dan
    | penanda "belum dikirim" baru lahir di F2.2 — sampai saat itu teksnya ada
    | di sini supaya tidak tertanam di JSX seperti sistem lama.
    |
    | Daftar placeholder yang sah ada di docs/08-notifikasi-whatsapp.md §8.4
    | dan diisi App\Services\WhatsAppNotifier. Placeholder yang tidak dikenali
    | dibiarkan apa adanya — advisor akan melihatnya sebelum menekan kirim.
    */
    'wa_messages' => [
        'pending' => 'Halo {{nama}}, booking servis Anda dengan kode *{{kode_booking}}* '
            .'untuk {{kendaraan}} ({{plat}}) sudah kami terima untuk {{tanggal}} pukul {{jam}}. '
            .'Kami akan segera mengonfirmasi. - Chery Arta',

        'confirmed' => 'Halo {{nama}}, booking *{{kode_booking}}* untuk {{kendaraan}} ({{plat}}) '
            .'*dikonfirmasi* pada {{tanggal}} pukul {{jam}}. Layanan: {{paket}}. '
            .'Mohon datang 10 menit lebih awal. Alamat: {{alamat}}. - Chery Arta',

        'in_progress' => 'Halo {{nama}}, kendaraan {{kendaraan}} ({{plat}}) sedang kami kerjakan. '
            .'Estimasi selesai pukul {{estimasi_selesai}}. Kode: {{kode_booking}}. - Chery Arta',

        'completed' => 'Halo {{nama}}, servis kendaraan {{kendaraan}} ({{plat}}) telah *selesai* '
            .'dan siap diambil. Kode: {{kode_booking}}. '
            .'Terima kasih telah mempercayakan perawatan pada Chery Arta.',

        'cancelled' => 'Halo {{nama}}, booking *{{kode_booking}}* pada {{tanggal}} pukul {{jam}} '
            .'telah dibatalkan. Alasan: {{alasan}}. Silakan booking ulang kapan saja. - Chery Arta',

        'no_show' => 'Halo {{nama}}, kami menunggu kendaraan {{kendaraan}} ({{plat}}) pada {{tanggal}} '
            .'pukul {{jam}} namun belum sempat bertemu. Kode: {{kode_booking}}. '
            .'Silakan hubungi kami untuk menjadwalkan ulang. - Chery Arta',
    ],

    /*
    | Jam operasional. 'closed' => true berarti tutup.
    | Sistem lama: index (1).html baris 1751.
    */
    'operating_hours' => [
        'monday' => ['open' => '08:00', 'close' => '16:00'],
        'tuesday' => ['open' => '08:00', 'close' => '16:00'],
        'wednesday' => ['open' => '08:00', 'close' => '16:00'],
        'thursday' => ['open' => '08:00', 'close' => '16:00'],
        'friday' => ['open' => '08:00', 'close' => '16:00'],
        'saturday' => ['open' => '08:00', 'close' => '14:00'],
        'sunday' => ['closed' => true],
    ],

    'operating_hours_text' => [
        'Senin - Jumat: 08:00 - 16:00',
        'Sabtu: 08:00 - 14:00',
        'Minggu: Tutup',
    ],

    /*
    | Klaim & keunggulan. Sistem lama: index (1).html baris 1670-1696.
    */
    'experience_years' => 15,
    'about' => 'Dengan pengalaman lebih dari 15 tahun di industri otomotif, '
        .'Chery Arta telah menjadi pilihan utama untuk perawatan dan '
        .'perbaikan kendaraan Anda.',

    'advantages' => [
        ['icon' => 'award', 'title' => 'Teknisi Bersertifikat'],
        ['icon' => 'wrench', 'title' => 'Peralatan Modern'],
        ['icon' => 'clock', 'title' => 'Layanan Cepat'],
        ['icon' => 'shield-check', 'title' => 'Garansi Kualitas'],
    ],

    'social' => [
        'instagram' => env('COMPANY_INSTAGRAM'),
        'facebook' => env('COMPANY_FACEBOOK'),
        'maps_url' => env('COMPANY_MAPS_URL'),
    ],

];
