<?php

declare(strict_types=1);

use App\Models\CarModel;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\ServicePackage;
use App\Models\Testimonial;

/*
| Beranda publik (roadmap 1.6.1).
|
| Yang dijaga di sini bukan tata letaknya, melainkan APA yang boleh muncul:
| hanya baris yang sudah disetujui admin, dan tanggal slot yang dihitung
| SlotService — bukan "besok" versi React.
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

it('membuka beranda tanpa login', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('welcome'));
});

it('menampilkan empat keunggulan dari konfigurasi perusahaan', function () {
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('advantages', 4)
            ->where('advantages.0.title', 'Teknisi Bersertifikat'));
});

it('hanya menampilkan paket layanan yang aktif', function () {
    ServicePackage::factory()->create(['name' => 'Servis Berkala 10.000 KM']);
    ServicePackage::factory()->create(['name' => 'Paket Lama', 'is_active' => false]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('servicePackages', 1)
            ->where('servicePackages.0.name', 'Servis Berkala 10.000 KM'));
});

it('hanya menampilkan model katalog yang aktif', function () {
    CarModel::factory()->create(['name' => 'Tiggo 8 Pro']);
    CarModel::factory()->inactive()->create(['name' => 'Model Ditarik']);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('carModels', 1)
            ->where('carModels.0.name', 'Tiggo 8 Pro'));
});

it('hanya menampilkan testimoni yang sudah disetujui', function () {
    Testimonial::factory()->create(['customer_name' => 'Budi Santoso']);
    Testimonial::factory()->unpublished()->create(['customer_name' => 'Belum Disetujui']);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('testimonials', 1)
            ->where('testimonials.0.customer_name', 'Budi Santoso'));
});

it('hanya menampilkan fasilitas dan faq yang aktif', function () {
    Facility::factory()->create(['title' => 'Ruang Tunggu Premium']);
    Facility::factory()->inactive()->create(['title' => 'Fasilitas Lama']);

    Faq::factory()->create(['question' => 'Berapa lama servis berkala?']);
    Faq::factory()->inactive()->create(['question' => 'Pertanyaan lama']);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('facilities', 1)
            ->where('facilities.0.title', 'Ruang Tunggu Premium')
            ->has('faqs', 1)
            ->where('faqs.0.question', 'Berapa lama servis berkala?'));
});

it('mengirim ketersediaan slot hari buka terdekat, bukan hari ini', function () {
    // Waktu dibekukan pada Senin 3 Agustus 2026, dan aturan H-1 membuat
    // tanggal sah paling awal jatuh pada Selasa 4 Agustus.
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('slotPreview.date', UJI_BESOK)
            ->has('slotPreview.slots')
            ->has('slotPreview.slots.0.time')
            ->has('slotPreview.slots.0.remaining')
            ->has('slotPreview.slots.0.is_full'));
});

it('melompati hari tutup saat menghitung jadwal terdekat', function () {
    // Sabtu 8 Agustus 2026: H-1 menunjuk ke Minggu 9 Agustus yang bengkelnya
    // tutup, jadi kartu hero harus melompat ke Senin 10 Agustus.
    Carbon\CarbonImmutable::setTestNow(
        Carbon\CarbonImmutable::parse(UJI_SABTU.' 09:00:00', config('booking.timezone')),
    );
    Carbon\Carbon::setTestNow(Carbon\CarbonImmutable::getTestNow());

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('slotPreview.date', '2026-08-10'));
});

it('menyisipkan data terstruktur schema.org di beranda', function () {
    $this->get(route('home'))
        ->assertSee('application/ld+json', false)
        ->assertSee('AutoRepair', false)
        ->assertSee('OpeningHoursSpecification', false);
});

it('tidak menyisipkan data terstruktur di halaman lain', function () {
    $this->get(route('public.faq'))->assertDontSee('application/ld+json', false);
});
