<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\WhatsAppTemplateKey;
use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;

/**
 * Tujuh template pesan WhatsApp (docs/08 §8.4).
 *
 * Lima teks di bawah dipindahkan apa adanya dari `config('company.wa_messages')`
 * yang sudah berjalan sejak F1.5 — terbukti terbaca pelanggan sungguhan. Dua
 * sisanya (`booking_created` dan `booking_reminder`) mengikuti contoh docs/08
 * §8.4 dan belum pernah dipakai; Super Admin bisa memperbaikinya sendiri sejak
 * hari pertama, dan itu memang inti tahap ini.
 *
 * **Memakai pemeriksaan-lalu-buat, BUKAN `updateOrCreate`.** Aturan seeder
 * idempoten (.claude/rules/30) biasanya dipenuhi `updateOrCreate`, tetapi di
 * sini isinya justru dimaksudkan untuk disunting Super Admin: menimpanya pada
 * setiap `db:seed` akan menghapus hasil kerjanya tanpa jejak. Tetap idempoten —
 * dijalankan sepuluh kali hasilnya sama — hanya tidak merusak.
 *
 * `key` diisi lewat forceFill karena sengaja tidak fillable: himpunan kuncinya
 * milik kode, bukan milik siapa pun yang memanggil `create()`.
 */
class WhatsAppTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (WhatsAppTemplateKey::cases() as $key) {
            if (WhatsAppTemplate::query()->where('key', $key->value)->exists()) {
                continue;
            }

            (new WhatsAppTemplate)->forceFill([
                'key' => $key,
                'name' => $key->label(),
                'body' => $this->body($key),
                'is_active' => true,
            ])->save();
        }
    }

    /**
     * Teks awal. Format tebal WhatsApp memakai *bintang*; tanda hubung biasa
     * dipakai pada "- Chery Arta" karena em-dash tidak selalu terbaca sama di
     * seluruh papan ketik ponsel.
     *
     * `{{ringkasan_biaya}}` dipakai di `booking_completed` sejak Tahap 11
     * (roadmap 2.4.8).
     *
     * **Perubahan ini hanya berlaku untuk instalasi baru.** Seeder ini sengaja
     * hanya membuat baris yang belum ada dan TIDAK PERNAH menyentuh yang sudah
     * ada — satu-satunya seeder produksi yang bukan `updateOrCreate`, dibuat
     * begitu di Tahap 10 supaya suntingan Super Admin tidak hilang. Pada
     * database yang sudah berjalan, placeholder ini harus disisipkan sekali
     * lewat layar A7. Menimpanya otomatis akan mematahkan jaminan itu.
     */
    private function body(WhatsAppTemplateKey $key): string
    {
        return match ($key) {
            WhatsAppTemplateKey::BookingCreated => 'Halo {{nama}}, booking servis Anda dengan kode *{{kode_booking}}* '
                .'untuk {{kendaraan}} ({{plat}}) sudah kami terima untuk {{tanggal}} pukul {{jam}}. '
                .'Kami akan segera mengonfirmasi. - Chery Arta',

            WhatsAppTemplateKey::BookingConfirmed => 'Halo {{nama}}, booking *{{kode_booking}}* untuk {{kendaraan}} ({{plat}}) '
                .'*dikonfirmasi* pada {{tanggal}} pukul {{jam}}. Layanan: {{paket}}. '
                .'Mohon datang 10 menit lebih awal. Alamat: {{alamat}}. - Chery Arta',

            WhatsAppTemplateKey::BookingInProgress => 'Halo {{nama}}, kendaraan {{kendaraan}} ({{plat}}) sedang kami kerjakan. '
                .'Estimasi selesai pukul {{estimasi_selesai}}. Kode: {{kode_booking}}. - Chery Arta',

            WhatsAppTemplateKey::BookingCompleted => 'Halo {{nama}}, servis kendaraan {{kendaraan}} ({{plat}}) telah *selesai* '
                .'dan siap diambil. Kode: {{kode_booking}}. {{ringkasan_biaya}}. '
                .'Terima kasih telah mempercayakan perawatan pada Chery Arta.',

            WhatsAppTemplateKey::BookingCancelled => 'Halo {{nama}}, booking *{{kode_booking}}* pada {{tanggal}} pukul {{jam}} '
                .'telah dibatalkan. Alasan: {{alasan}}. Silakan booking ulang kapan saja. - Chery Arta',

            WhatsAppTemplateKey::BookingNoShow => 'Halo {{nama}}, kami menunggu kendaraan {{kendaraan}} ({{plat}}) pada {{tanggal}} '
                .'pukul {{jam}} namun belum sempat bertemu. Kode: {{kode_booking}}. '
                .'Silakan hubungi kami untuk menjadwalkan ulang. - Chery Arta',

            WhatsAppTemplateKey::BookingReminder => 'Halo {{nama}}, pengingat servis besok {{tanggal}} pukul {{jam}} '
                .'untuk {{kendaraan}} ({{plat}}). Kode: {{kode_booking}}. Sampai jumpa! - Chery Arta',
        };
    }
}
