<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Testimoni CONTOH — hanya untuk pengembangan.
 *
 * PERINGATAN: pemeriksaannya `app()->isLocal()`, yang menilai APP_ENV komputer
 * yang MENJALANKAN perintah, bukan database yang dituju. Selama keputusan R2
 * masih berlaku (satu database dipakai bersama development dan deployment),
 * menjalankan seeder ini dari komputer developer akan menaruh testimoni
 * karangan di database yang dibaca situs live. Jangan dijalankan setelah
 * landing page publik terbit di F1.6.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        foreach ($this->testimonials() as $sortOrder => $testimonial) {
            Testimonial::updateOrCreate(
                ['customer_name' => $testimonial['customer_name']],
                [...$testimonial, 'sort_order' => $sortOrder, 'is_published' => true],
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function testimonials(): array
    {
        return [
            [
                'customer_name' => 'Budi Santoso',
                'car_model' => 'Tiggo 8 Pro',
                'rating' => 5,
                'content' => 'Booking lewat website jauh lebih praktis. Datang sesuai jam yang dipilih, tidak perlu antre lama.',
            ],
            [
                'customer_name' => 'Siti Rahmawati',
                'car_model' => 'Omoda 5',
                'rating' => 5,
                'content' => 'Ruang tunggunya nyaman dan ada area bermain anak, jadi tidak repot menunggu sambil membawa si kecil.',
            ],
            [
                'customer_name' => 'Andi Prasetyo',
                'car_model' => 'Tiggo Cross',
                'rating' => 4,
                'content' => 'Advisor menjelaskan pekerjaan yang perlu dilakukan dengan rinci sebelum dikerjakan. Tidak ada biaya mengejutkan.',
            ],
            [
                'customer_name' => 'Dewi Lestari',
                'car_model' => 'Omoda E5',
                'rating' => 5,
                'content' => 'Servis mobil listrik ditangani teknisi yang paham. Selesai sesuai estimasi waktu yang dijanjikan.',
            ],
        ];
    }
}
