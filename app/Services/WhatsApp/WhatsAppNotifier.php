<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsAppTemplateKey;
use App\Models\Booking;
use App\Support\WhatsAppDraft;

/**
 * Penyusun pesan WhatsApp (docs/08 §8.7).
 *
 * Kode memanggil interface ini, bukan implementasinya, supaya beralih ke
 * gateway otomatis kelak (Fonnte / WhatsApp Cloud API) cukup dengan menambah
 * implementasi baru dan mengubah pengikatan di AppServiceProvider — tanpa
 * menyentuh satu pun controller.
 *
 * **Tidak ada `send()`.** docs/08 §8.7 sempat menuliskannya sebagai method
 * kosong yang tidak melakukan apa-apa pada klik-to-chat; itu kode mati.
 * Jaminan "cukup tambah implementasi baru" datang dari ADANYA interface ini,
 * bukan dari method yang tidak dipanggil siapa pun. Saat gateway benar-benar
 * dipilih, `send()` dirancang terhadap API yang nyata — kembaliannya,
 * antreannya, penanganan gagalnya — bukan ditebak sekarang (keputusan grill Q9).
 */
interface WhatsAppNotifier
{
    /**
     * Pesan siap kirim untuk satu booking, atau `null` bila memang tidak ada
     * yang bisa ditawarkan.
     *
     * `null` bukan kegagalan — ia jawaban yang sah untuk dua keadaan wajar:
     * pelanggan tidak punya nomor WhatsApp, atau templatenya dinonaktifkan
     * Super Admin. Panel menampilkan penjelasan, bukan galat.
     */
    public function draft(Booking $booking, WhatsAppTemplateKey $templateKey): ?WhatsAppDraft;
}
