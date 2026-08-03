<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Pertanyaan umum seputar booking servis.
 *
 * Jawaban di sini adalah SALINAN aturan yang sebenarnya hidup di
 * `config/booking.php` — kuota 2 per jam, wajib H-1, Minggu tutup, maksimal
 * 60 hari ke depan. Bila angka di konfigurasi berubah, teks ini harus ikut
 * diperbarui: ia konten yang dibaca manusia, bukan sumber kebenarannya.
 *
 * Seeder produksi — idempoten, aman dijalankan ulang.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->faqs() as $sortOrder => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                [...$faq, 'sort_order' => $sortOrder, 'is_active' => true],
            );
        }
    }

    /** @return list<array<string, string>> */
    private function faqs(): array
    {
        return [
            [
                'question' => 'Kapan saya harus memesan jadwal servis?',
                'answer' => 'Booking dibuat paling lambat satu hari sebelum tanggal servis. Pemesanan untuk hari yang sama tidak bisa diproses karena bengkel perlu menyiapkan suku cadang dan teknisinya. Jadwal bisa dipesan sampai 60 hari ke depan.',
                'category' => 'booking',
            ],
            [
                'question' => 'Apa saja hari dan jam operasional bengkel?',
                'answer' => 'Senin sampai Jumat pukul 08.00–16.00, Sabtu pukul 08.00–14.00. Hari Minggu bengkel tutup. Slot servis tersedia mulai pukul 08.00.',
                'category' => 'booking',
            ],
            [
                'question' => 'Mengapa jam yang saya inginkan tidak bisa dipilih?',
                'answer' => 'Setiap jam hanya menerima dua kendaraan agar pengerjaan tidak menumpuk dan estimasi selesai tetap akurat. Bila satu jam sudah terisi penuh, jam tersebut ditandai "Penuh" dan Anda dapat memilih jam lain pada hari yang sama.',
                'category' => 'booking',
            ],
            [
                'question' => 'Bagaimana cara membatalkan atau mengubah jadwal booking?',
                'answer' => 'Buka menu Riwayat, pilih booking yang bersangkutan, lalu tekan tombol Batalkan atau Ubah Jadwal. Perubahan hanya bisa dilakukan selama booking belum dikerjakan dan paling lambat satu hari sebelum jadwal.',
                'category' => 'booking',
            ],
            [
                'question' => 'Apakah servis berkala benar-benar gratis?',
                'answer' => 'Ya, untuk kendaraan yang masih tercakup program Free Maintenance sesuai serinya. Jasa dan suku cadang yang termasuk paket tidak dipungut biaya. Pekerjaan di luar paket, misalnya penggantian komponen karena keausan, tetap dikenakan biaya dan selalu dikonfirmasikan lebih dulu kepada Anda.',
                'category' => 'layanan',
            ],
            [
                'question' => 'Apakah saya bisa membawa mobil selain Chery?',
                'answer' => 'Bisa. Saat menambahkan kendaraan, kosongkan pilihan model Chery lalu ketik sendiri nama kendaraan Anda. Perlu diketahui bahwa paket perawatan gratis hanya berlaku untuk kendaraan Chery.',
                'category' => 'layanan',
            ],
            [
                'question' => 'Berapa lama servis dikerjakan?',
                'answer' => 'Perawatan pertama umumnya selesai sekitar satu jam, sedangkan servis berkala sekitar satu setengah jam. Estimasi jam selesai ditampilkan saat Anda memilih paket layanan, dan advisor akan mengabari bila pekerjaan memerlukan waktu lebih lama.',
                'category' => 'layanan',
            ],
            [
                'question' => 'Bagaimana saya mengetahui perkembangan servis kendaraan saya?',
                'answer' => 'Status booking dapat dilihat kapan saja di menu Riwayat. Service advisor juga menghubungi Anda melalui WhatsApp pada nomor yang Anda daftarkan bila ada hal yang perlu dikonfirmasi.',
                'category' => 'layanan',
            ],
        ];
    }
}
