<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Aturan Booking Servis
|--------------------------------------------------------------------------
|
| SATU-SATUNYA sumber kebenaran aturan slot booking. Nilai di bawah dikutip
| persis dari sistem lama (index (1).html) sesuai keputusan rancangan #7.
| Jangan pernah menulis angka-angka ini langsung di controller, service,
| atau komponen React — sistem lama menuliskannya di tiga tempat berbeda
| dan akhirnya saling bertabrakan (lihat docs/01-analisis-sistem-lama.md B2).
|
*/

return [

    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

    /*
    | Booking wajib dilakukan minimal H-1 (satu hari sebelum pelaksanaan).
    | Sistem lama: index (1).html baris 2278-2282.
    */
    'lead_time_days' => 1,

    /*
    | Batas terjauh pemesanan ke depan. Aturan baru — sistem lama tidak
    | membatasi, sehingga booking bisa dibuat untuk tahun depan.
    */
    'max_advance_days' => 60,

    /*
    | Kuota maksimal booking per slot jam.
    | Sistem lama: index (1).html baris 2318 (bookingCount >= 2).
    */
    'quota_per_slot' => 2,

    /*
    | Hari bengkel tutup. 0 = Minggu ... 6 = Sabtu (Carbon::dayOfWeek).
    | Sistem lama: index (1).html baris 3130-3135.
    */
    'closed_weekdays' => [0],

    /*
    | Daftar slot waktu. Tidak ada 12:00 & 12:30 karena istirahat siang.
    | Sistem lama: index (1).html baris 1633-1646.
    */
    'slots' => [
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
        '11:00', '11:30', '13:00', '13:30', '14:00',
    ],

    /*
    | Penimpaan slot untuk hari tertentu (0 = Minggu ... 6 = Sabtu).
    |
    | KEPUTUSAN R7 (3 Agustus 2026, docs/05-alur-bisnis.md#catatan-konflik-jam-sabtu):
    | jam operasional Sabtu adalah 08:00-14:00, sedangkan sistem lama tetap
    | menawarkan slot 13:30 dan 14:00 — menjanjikan pekerjaan yang baru dimulai
    | tepat saat bengkel tutup. Slot Sabtu karena itu berhenti di 13:00.
    |
    | Ini SATU-SATUNYA penyimpangan yang disengaja dari keputusan #7 ("aturan
    | slot dipertahankan persis"). Kuota 2/jam, aturan H-1, dan Minggu tutup
    | tidak berubah.
    |
    | SlotService membaca kunci ini bila ada dan jatuh kembali ke `slots` untuk
    | hari lain — tidak ada percabangan hari yang ditulis di kode.
    */
    'slots_by_weekday' => [
        6 => ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00'],
    ],

    /*
    | Batas minimal (hari) sebelum tanggal booking agar customer masih
    | boleh menjadwal ulang atau membatalkan sendiri.
    */
    'reschedule_min_days_before' => 1,

    /*
    | Booking walk-in dibuat admin untuk pelanggan yang datang langsung,
    | sehingga aturan H-1 tidak berlaku. Kuota slot tetap ditegakkan.
    */
    'walk_in_bypasses_lead_time' => true,

    /*
    | Awalan kode booking: CA-YYYYMMDD-NNNN
    */
    'code_prefix' => 'CA',

];
